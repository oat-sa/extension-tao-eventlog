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

namespace oat\taoEventLog\model\eventLog;

use common_exception_Error;
use common_session_Session;
use common_session_SessionManager;
use Context;
use DateTimeImmutable;
use JsonSerializable;
use oat\dtms\DateInterval;
use oat\oatbox\event\BulkEvent;
use oat\oatbox\event\Event;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\user\User;
use oat\taoEventLog\model\storage\RdsStorage as DeprecatedRdsStorage;
use oat\dtms\DateTime;
use oat\taoEventLog\model\AbstractLog;
use oat\taoEventLog\model\StorageInterface;

/**
 * Class LoggerService
 * @package oat\taoEventLog\model\eventLog
 */
class LoggerService extends AbstractLog
{
    public const SERVICE_ID = 'taoEventLog/eventLogger';

    public const OPTION_ROTATION_PERIOD = 'rotation_period';
    public const OPTION_EXPORTABLE_QUANTITY = 'exportable_quantity';
    public const OPTION_FETCH_LIMIT = 'fetch_limit';

    /**
     * @var string
     */
    private $action = '';

    public function getAction()
    {
        if (!$this->action) {
            $this->action = 'cli' === php_sapi_name()
                ? $_SERVER['PHP_SELF']
                : Context::getInstance()->getRequest()->getRequestURI();
        }
        return $this->action;
    }

    public function setAction($action = '')
    {
        $this->action = $action;
    }

    /**
     * @param Event $event
     * @throws common_exception_Error
     */
    public function log(Event $event)
    {
        $currentUser = $this->getUser();

        try {
            if ($event instanceof BulkEvent) {
                $this->getStorage()->logMultiple(
                    array_map(
                        fn (array $eventData): EventLogEntity => $this->createEventLogEntity(
                            $event,
                            $currentUser,
                            $eventData
                        ),
                        $event->getValues()
                    )
                );

                return;
            }

            $data = is_subclass_of($event, JsonSerializable::class) ? $event : [];
            $this->getStorage()->log($this->createEventLogEntity($event, $currentUser, $data));
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

        return $this->delete([], $beforeDate);
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
     * @return DeprecatedRdsStorage|StorageInterface
     */
    protected function getStorage()
    {
        $storage = $this->getServiceManager()->get(self::SERVICE_ID)->getOption(self::OPTION_STORAGE);
        return $this->getServiceManager()->get($storage);
    }

    private function getUser(): User
    {
        /** @var common_session_Session $session */
        $session = common_session_SessionManager::getSession();

        return $session->getUser();
    }

    private function createEventLogEntity(Event $event, User $user, array $data): EventLogEntity
    {
        return new EventLogEntity(
            $event,
            $this->getAction(),
            $user,
            (new DateTime('now', new \DateTimeZone('UTC'))),
            $data
        );
    }
}
