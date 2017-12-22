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

namespace oat\taoEventLog\model\requestLog\fluentd;

use oat\taoEventLog\model\requestLog\AbstractRequestLogStorage;
use oat\oatbox\user\User;
use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface;

/**
 * Class TinCanStorage
 *
 * Configuration example (RequestLogStorage.conf.php):
 * ```php
 * use oat\taoEventLog\model\requestLog\fluentd\FluentdStorage;
 * use oat\taoEventLog\model\requestLog\RequestLogService;
 *
 * return new RequestLogService([
 *   RequestLogService::OPTION_STORAGE => FluentdStorage::class,
 *   RequestLogService::OPTION_STORAGE_PARAMETERS => [
 *      FluentdStorage::OPTION_TAG => 'tao.frontend',
 *      FluentdStorage::OPTION_HOST => 'tao.frontend',
 *      FluentdStorage::OPTION_TAG => 'tao.frontend',
 *   ]
 * ]);
 *
 * ```
 *
 * @package oat\taoEventLog\model\storage
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class FluentdStorage extends AbstractRequestLogStorage
{
    const OPTION_TAG  = 'tag';
    const OPTION_HOST  = 'host';
    const OPTION_PORT  = 'port';

    private $resource;
    private $host;
    private $port;
    private $tag;

    /**
     * FluentdStorage constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->resource = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        $this->host = $this->getOption(self::OPTION_HOST);
        $this->port = $this->getOption(self::OPTION_PORT);
        $this->tag = $this->getOption(self::OPTION_TAG);
        socket_set_nonblock($this->resource);
    }

    /**
     * @param RequestInterface $request
     * @param User $user
     * @return bool|void
     */
    public function log(RequestInterface $request, User $user)
    {
        $this->sendData($this->prepareData($request, $user));
    }

    /**
     * @inheritdoc
     */
    public function bulkLog(array $data)
    {
        $this->sendData($data);
    }

    /**
     * @param array $data
     */
    private function sendData(array $data)
    {
        $message = 'json='.json_encode($data);
        try{
            socket_sendto($this->resource, $message, strlen($message), 0, $this->host, $this->port);
        } catch (\Exception $e) {
            \common_Logger::e('Error logging to Fluentd ' . $e->getMessage());
        }

//        try {
//            $request = $this->client->post(
//                '/'.$this->getOption(self::OPTION_TAG),
//                [
//                    'form_params' => [
//                        'json' => json_encode($data)
//                    ]
//                ]
//            );
//        } catch (\Exception $e) {
//            \common_Logger::e('Error logging to Fluentd ' . $e->getMessage());
//        }
    }
}