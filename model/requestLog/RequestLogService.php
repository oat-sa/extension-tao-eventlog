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

namespace oat\taoEventLog\model\requestLog;

use GuzzleHttp\Psr7\ServerRequest;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\event\Event;
use Psr\Http\Message\RequestInterface;
use oat\tao\model\event\BeforeAction;

/**
 * Class RequestLogService
 * @package oat\taoEventLog\model\requestLog
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RequestLogService extends ConfigurableService
{

    const SERVICE_ID = 'taoEventLog/RequestLogStorage';

    const OPTION_STORAGE = 'storage';
    const OPTION_STORAGE_PARAMETERS = 'storage_parameters';

    const USER_ID = 'user_id';
    const USER_ROLES = 'user_role';
    const ACTION = 'action';
    const EVENT_TIME = 'event_time';
    const DETAILS = 'details';

    /** @var bool whether request has been already logged during current php process */
    private $fulfilled = false;

    /** @var RequestLogStorageReadable|RequestLogStorageWritable */
    private $storage;

    /**
     * @see \oat\taoEventLog\model\requestLog\RequestLogStorageWritable::log
     *
     * @param RequestInterface|null $request
     * @param User|null $user
     * @return boolean
     * @throws \common_exception_Error
     * @throws RequestLogException
     */
    public function log(RequestInterface $request = null, User $user = null)
    {
        if ($request === null) {
            $request = ServerRequest::fromGlobals();
        }

        if ($user === null) {
            $user = \common_session_SessionManager::getSession()->getUser();
        }
       return  $this->getStorage()->log($request, $user);
    }

    /**
     * @see \oat\taoEventLog\model\requestLog\RequestLogStorageReadable::find()
     * @param array $filters
     * @param array $options
     * @return \Iterator
     * @throws RequestLogException
     */
    public function find(array $filters = [], array $options = [])
    {
        if (!$this->getStorage() instanceof RequestLogStorageReadable) {
            throw new RequestLogException('Request log storage is not readable');
        }
        return  $this->getStorage()->find($filters, $options);
    }

    /**
     * @see \oat\taoEventLog\model\requestLog\RequestLogStorageReadable::count()
     * @param array $filters
     * @param array $options
     * @return integer
     * @throws RequestLogException
     */
    public function count(array $filters = [], array $options = [])
    {
        if (!$this->getStorage() instanceof RequestLogStorageReadable) {
            throw new RequestLogException('Request log storage is not readable');
        }
        return $this->getStorage()->count($filters, $options);
    }

    /**
     * @return RequestLogStorageReadable|RequestLogStorageWritable
     * @throws
     */
    protected function getStorage()
    {
        if ($this->storage === null) {
            $storageClass = $this->getOption(self::OPTION_STORAGE);
            if (!class_exists($storageClass)) {
                throw new RequestLogException('Storage class does not exist');
            }
            $storageParams = $this->getOption(self::OPTION_STORAGE_PARAMETERS)?:[];
            $this->storage = new $storageClass($storageParams);
            $this->getServiceManager()->propagate($this->storage);
        }
        return $this->storage;
    }

    /**
     * @param Event $event
     * @throws 
     */
    public function catchEvent(Event $event)
    {
        if ($event instanceof BeforeAction && $this->fulfilled) {
            return;
        }
        $this->fulfilled = true;
        $this->log();
    }
}