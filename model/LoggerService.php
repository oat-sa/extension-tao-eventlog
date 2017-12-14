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
 * Copyright (c) 2016  (original work) Open Assessment Technologies SA;
 * 
 * @author Alexander Zagovorichev <zagovorichev@1pt.com>
 */

namespace oat\taoEventLog\model;

use common_session_Session;
use common_session_SessionManager;
use common_user_User;
use Context;
use DateTimeImmutable;
use JsonSerializable;
use oat\dtms\DateInterval;
use oat\oatbox\event\Event;
use oat\oatbox\service\ServiceManager;
use oat\taoEventLog\model\storage\RdsStorage;
use oat\dtms\DateTime;

/**
 * Class LoggerService
 * @package oat\taoEventLog\model
 */
class LoggerService extends AbstractLog
{
    const SERVICE_ID = 'taoEventLog/logger';

    const OPTION_ROTATION_PERIOD = 'rotation_period';
    const OPTION_EXPORTABLE_PERIOD = 'exportable_period';
    const OPTION_EXPORTABLE_QUANTITY = 'exportable_quantity';

    /**
     * @param Event $event
     */
    public function log(Event $event)
    {
        $action = 'cli' === php_sapi_name()
            ? $_SERVER['PHP_SELF']
            : Context::getInstance()->getRequest()->getRequestURI();

        /** @var common_session_Session $session */
        $session = common_session_SessionManager::getSession();

        /** @var common_user_User $currentUser */
        $currentUser = $session->getUser();

        $data = is_subclass_of($event, JsonSerializable::class) ? $event : [];

        $logEntity = new LogEntity(
            $event,
            $action,
            $currentUser,
            (new DateTime('now', new \DateTimeZone('UTC'))),
            $data
        );

        try {
            $this->getStorage()->log($logEntity);
        } catch (\Exception $e) {
            \common_Logger::e('Error logging to DB ' . $e->getMessage());
        }
    }

    /**
     * @deprecated use $this->log()
     * @param Event $event
     */
    public static function logEvent(Event $event)
    {
        ServiceManager::getServiceManager()->get(self::SERVICE_ID)->log($event);
    }

    /**
     * @return mixed
     */
    public function rotate()
    {
        $period = new DateInterval($this->getOption(self::OPTION_ROTATION_PERIOD));
        $beforeDate = (new DateTimeImmutable())->sub($period);
        
        return $this->getStorage()->removeOldLogEntries($beforeDate);
    }

    /**
     * @param array $filters
     * @param array $options
     * @deprecated use LoggerService::search() instead
     * @return array
     */
    public function searchInstances(array $filters = [], array $options = [])
    {
        return $this->search($filters, $options);
    }

    /**
     * @return mixed
     */
    public function flush()
    {
        return $this->getStorage()->flush();
    }

    /**
     * @return RdsStorage|StorageInterface
     */
    protected function getStorage()
    {
        $storage = $this->getServiceManager()->get(self::SERVICE_ID)->getOption(self::OPTION_STORAGE);
        return $this->getServiceManager()->get($storage);
    }
}
