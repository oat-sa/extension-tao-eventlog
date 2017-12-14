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

/**
 * Class TinCanStorage
 *
 * Configuration example:
 * ```php
 * use \oat\taoEventLog\model\storage\TinCanStorage;
 *
 * return new TinCanStorage([
 *    TinCanStorage::OPTION_VERSION => '1.0.1',
 *    TinCanStorage::OPTION_ENDPOINT => 'http://ll.com/data/xAPI/',
 *    TinCanStorage::OPTION_AUTH => [
 *        '8991ed3f721cfb5bbf57d8e0a3e448e1f56114c6',
 *        '07e6e0fe16dd07018ba327efcebe8cbfbb73d08b',
 *    ],
 * ]);
 *
 * ```
 *
 * @package oat\taoEventLog\model\storage
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class TinCanStorage extends ConfigurableService implements StorageInterface
{
    use OntologyAwareTrait;

    const OPTION_PERSISTENCE = 'persistence';
    const OPTION_ENDPOINT = 'endpoint';
    const OPTION_VERSION = 'version';
    const OPTION_AUTH = 'auth';

    const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * @param LogEntity $logEntity
     * @return bool
     */
    public function log(LogEntity $logEntity)
    {
        try {
            $lrs = $this->getLrs();

            $statement = new \TinCan\Statement([
                'actor' => $this->getActor($logEntity),
                'verb'  => $this->getVerb($logEntity),
                'object' => $this->getObject($logEntity),
                'context' => $this->getContext($logEntity),
                'timestamp' => $logEntity->getTime()->format('Y-m-d\TH:i:s.uP'),
            ]);

            $response = $lrs->saveStatement($statement);

            if (!$response->success) {
                \common_Logger::e($response->content);
            }

        } catch (\Exception $e) {
            \common_Logger::e('Error logging to LRS ' . $e->getMessage());
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

    /**
     * @return string
     */
    protected function getRootUrl()
    {
        return \tao_helpers_Uri::getRootUrl();
    }

    /**
     * @return \TinCan\RemoteLRS
     */
    protected function getLrs()
    {
        $lrs = new \TinCan\RemoteLRS();
        $lrs->setEndpoint($this->getOption(self::OPTION_ENDPOINT));
        $lrs->setVersion($this->getOption(self::OPTION_VERSION));
        $auth = $this->getOption(self::OPTION_AUTH);
        if (!is_array($auth)) {
            $auth = [$auth];
        }
        call_user_func_array([$lrs, 'setAuth'], $auth);
        return $lrs;
    }

    /**
     * @param LogEntity $logEntity
     * @return \TinCan\Agent
     */
    protected function getActor(LogEntity $logEntity)
    {
        $actor = new \TinCan\Agent([
            'name' => \oat\tao\helpers\UserHelper::getUserName($logEntity->getUser(), true),
            'account' => [
                'name' => $logEntity->getUser()->getIdentifier(),
                'homePage' => _url('index', 'Main', 'tao', ['structure'=>'users', 'ext' => 'tao', 'section' => 'list_users']),
            ]
        ]);

        return $actor;
    }

    /**
     * @param LogEntity $logEntity
     * @return mixed
     */
    protected function getVerb(LogEntity $logEntity)
    {
        $event = $logEntity->getEvent();
        if ($event instanceof TinCanEvent) {
            $verb = $event->getVerb();
        } else {
            $verb = new \TinCan\Verb(['id' => $this->getRootUrl() . 'events#' . str_replace('\\', '/', $event->getName())]);
        }
        return $verb;
    }

    /**
     * @param LogEntity $logEntity
     * @return \TinCan\Activity
     */
    protected function getObject(LogEntity $logEntity)
    {
        $event = $logEntity->getEvent();
        if ($event instanceof TinCanEvent) {
            $activity = $event->getActivity();
        } else {
            $activity = new \TinCan\Activity([
                'id' => $this->getRootUrl() . 'activities#' . $logEntity->getAction()
            ]);
        }
        return $activity;
    }

    /**
     * @param LogEntity $logEntity
     * @return \TinCan\Context
     */
    protected function getContext(LogEntity $logEntity)
    {
        $event = $logEntity->getEvent();
        if ($event instanceof TinCanEvent) {
            $context = $event->getContext();
        } else {
            $context = new \TinCan\Context([
                'platform' => GENERIS_INSTANCE_NAME,
            ]);
        }

        $extensions = $context->getExtensions();
        $extensions->set(PROPERTY_USER_ROLES, join(',', $logEntity->getUser()->getRoles()));
        $extensions->set(GENERIS_NS . '#eventData', json_encode($logEntity->getData()));

        $context->setExtensions($extensions);

        return $context;
    }
}
