<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoEventLog\model\storage;

use oat\oatbox\service\ConfigurableService;
use oat\taoEventLog\model\StorageInterface;
use oat\generis\model\OntologyAwareTrait;
use oat\taoEventLog\model\LogEntity;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * Class TinCanStorage
 *
 * Configuration example:
 * ```php
 * use \oat\taoEventLog\model\storage\FluentdStorage;
 *
 * return new FluentdStorage([
 *    FluentdStorage::OPTION_ENDPOINT => 'http://192.168.202.192:8888',
 *    FluentdStorage::OPTION_TAG => 'tao.frontend',
 * ]);
 *
 * ```
 *
 * @package oat\taoEventLog\model\storage
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class FluentdStorage extends ConfigurableService implements StorageInterface
{
    use OntologyAwareTrait;

    const OPTION_ENDPOINT = 'endpoint';
    const OPTION_TAG = 'tag';

    /** @var Client */
    private $client;

    public function __construct(array $options = array())
    {
        parent::__construct($options);
        $this->client = new Client([
            'base_uri' => $this->getOption(self::OPTION_ENDPOINT)
        ]);
    }

    /**
     * @param LogEntity $logEntity
     */
    public function log(LogEntity $logEntity)
    {
        try {
            $data = [
                'actor' => $logEntity->getUser()->getIdentifier(),
                'action'  => $logEntity->getAction(),
                'data' => $logEntity->getData(),
                'timestamp' => microtime(true),
            ];

            $response = $this->client->post(
                '/'.$this->getOption(self::OPTION_TAG),
                [
                    'form_params' => [
                        'json' => json_encode($data)
                    ]
                ]
            );

//            if (!$response->success) {
//                \common_Logger::e($response->content);
//            }

        } catch (\Exception $e) {
            \common_Logger::e('Error logging to Fluentd ' . $e->getMessage());
        }
    }

    /**
     * @param array $logEntities
     * @return boolean
     */
    public function bulkLog(array $logEntities)
    {
        $lrs = $this->getLrs();

        $statements = [];
        foreach ($logEntities as $logEntity) {
            $statements[] = new \TinCan\Statement([
                'actor' => $this->getActor($logEntity),
                'verb'  => $this->getVerb($logEntity),
                'object' => $this->getObject($logEntity),
                'context' => $this->getContext($logEntity),
                'timestamp' => $logEntity->getTime()->format('Y-m-d\TH:i:s.uP'),
            ]);
        }
        try {
            $response = $lrs->saveStatements($statements);
            if (!$response->success) {
                \common_Logger::e($response->content);
            }
        } catch (\Exception $e) {
            \common_Logger::e('Error logging to LRS ' . $e->getMessage());
            return false;
        }

        return $response->success;
    }

    /**
     * @param array $filters
     * @param array $options
     * @return array
     */
    public function search(array $filters = [], array $options = [])
    {
        $options = $this->prepareOptions($options);
        $filters = $this->prepareFilters($filters);
        $result = [];

        $query = array_merge($filters, $options);
        $statements = $this->getLrs()->queryStatements($query);
        /** @var \TinCan\Statement $statement */
        if ($statements->success) {
            foreach ($statements->content->getStatements() as $statement) {
                $extensions = $statement->getContext()->getExtensions()->asVersion();
                $result[] = [
                    self::EVENT_LOG_ID => $statement->getId(),
                    self::EVENT_LOG_ACTION => $statement->getObject()->getId(),
                    self::EVENT_LOG_EVENT_NAME => $statement->getVerb()->getId(),
                    self::EVENT_LOG_OCCURRED => $statement->getTimestamp(),
                    self::EVENT_LOG_USER_ROLES => isset($extensions[PROPERTY_USER_ROLES]) ? $extensions[PROPERTY_USER_ROLES] : null,
                    self::EVENT_LOG_USER_ID => $statement->getActor()->getAccount()->getName(),
                    self::EVENT_LOG_PROPERTIES => isset($extensions[GENERIS_NS . '#eventData']) ?
                        json_encode($extensions[GENERIS_NS . '#eventData']) : null,
                ];
            }
        }
        if (isset($options['offset'])) {
            $result = array_slice($result, $options['offset']);
        }
        return $result;
    }

    /**
     * @param array $filters
     * @param array $options
     * @return array
     */
    public function count(array $filters = [], array $options = [])
    {
        $options = $this->prepareOptions($options);
        $filters = $this->prepareFilters($filters);

        $result = 0;
        $statements = $this->getLrs()->queryStatements(array_merge($filters, $options));
        /** @var \TinCan\Statement $statement */
        if ($statements->success) {
            $result = count($statements->content->getStatements());
        }
        return $result;
    }

    /**
     * @param array $options
     * @return array
     */
    protected function prepareOptions(array $options)
    {
        $result = [
            'offset' => 0,
        ];
        if (isset($options['offset'])) {
            $result['offset'] = intval($options['offset']);
        }
        if (isset($options['limit'])) {
            $result['limit'] = $result['offset'] + intval($options['limit']);
        }
        if (isset($options['sort'])) {
            //not supported by xAPI
        }
        $order = isset($options['order']) ? strtoupper($options['order']) : 'ASC';
        if ($order === 'ASC') {
            $result['ascending'] = true;
        }
        return $result;
    }

    /**
     * @param array $filters
     * @return array
     */
    protected function prepareFilters(array $filters)
    {
        $result = [];
        foreach ($filters as $filter) {
            $propName = strtolower($filter[0]);
            $operation = strtolower($filter[1]);
            $val = trim($filter[2], '%');
            $val2 = isset($filter[3]) ? trim($filter[3], '%') : null;

            if ($propName === self::EVENT_LOG_ID) {
                $result['statementId'] = $val;
            }
            if ($propName === self::EVENT_LOG_USER_ID) {
                $userUri = LOCAL_NAMESPACE . '#' . $val;
                $result['agent'] = new \TinCan\Agent([
                    'account' => [
                        'name' => $userUri,
                        'homePage' => _url('index', 'Main', 'tao', ['structure'=>'users', 'ext' => 'tao', 'section' => 'list_users']),
                    ]
                ]);;
            }
            if ($propName === self::EVENT_LOG_USER_ROLES) {
                //can't filter by extensions
            }
            if ($propName === self::EVENT_LOG_EVENT_NAME) {
                $result['verb'] = new \TinCan\Verb([
                    'id' => $this->getRootUrl() . 'events#' . str_replace('\\', '/', $val)
                ]);
            }
            if ($propName === self::EVENT_LOG_ACTION) {
                //todo
            }
            if ($propName === self::EVENT_LOG_OCCURRED) {
                $val = \DateTime::createFromFormat(\DateTime::ISO8601, $val);
                $val2 = \DateTime::createFromFormat(\DateTime::ISO8601, $val2);
                if ($operation === 'between') {
                    $result['since'] = $val->format(self::DATE_TIME_FORMAT);
                    $result['until'] = $val2->format(self::DATE_TIME_FORMAT);
                }
                if ($operation === '>') {
                    $result['since'] = $val;
                }
                if ($operation === '<') {
                    $result['until'] = $val;
                }
            }
        }
        return $result;
    }


}
