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
 *      FluentdStorage::OPTION_ENDPOINT => 'http://192.168.202.192:8888',
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
    const OPTION_ENDPOINT  = 'endpoint';
    const OPTION_TAG  = 'tag';

    /** @var Client */
    private $client;

    /**
     * FluentdStorage constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->client = new Client([
            'base_uri' => $this->getOption(self::OPTION_ENDPOINT)
        ]);
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
        try {
            $request = $this->client->post(
                '/'.$this->getOption(self::OPTION_TAG),
                [
                    'form_params' => [
                        'json' => json_encode($data)
                    ]
                ]
            );
        } catch (\Exception $e) {
            \common_Logger::e('Error logging to Fluentd ' . $e->getMessage());
        }
    }

    public static function install()
    {
        // Nothing to do
    }

}