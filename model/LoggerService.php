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

use common_Logger;
use common_session_Session;
use common_session_SessionManager;
use Context;
use DateTime;
use DateTimeImmutable;
use JsonSerializable;
use oat\dtms\DateInterval;
use oat\oatbox\event\Event;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\user\User;
use oat\taoEventLog\model\storage\RdsStorage;

/**
 * Class LoggerService
 * @package oat\taoEventLog\model
 */
class LoggerService extends ConfigurableService
{
    const SERVICE_ID = 'taoEventLog/logger';
    
    const OPTION_ROTATION_PERIOD = 'rotation_period';

    /** @var StorageInterface */
    private $storage;

    /**
     * @param StorageInterface $storage
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param Event $event
     */
    public function logEvent(Event $event)
    {
        if (!is_subclass_of($event, JsonSerializable::class)) {
            common_Logger::d(sprintf('Event "%s" should implements JsonSerializable interface for to be logged by EventLog extension', $event->getName()));
            return;
        }

        /** @var Context $context */
        $context = Context::getInstance();

        /** @var common_session_Session $session */
        $session = common_session_SessionManager::getSession();

        /** @var User $currentUser */
        $currentUser = $session->getUser();

        $this->getStorage()->log(
            $event->getName(),
            $context->getRequest()->getRequestURI(),
            $currentUser->getIdentifier(),
            join(',', $currentUser->getRoles()),
            (new DateTime())->format(DateTime::ISO8601),
            json_encode($event, JSON_PRETTY_PRINT)
        );
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
     * @param array $params
     * @return array
     */
    public function searchInstances(array $params = [])
    {
        return $this->getStorage()->searchInstances($params);
    }

    /**
     * @return RdsStorage|StorageInterface
     */
    private function getStorage()
    {
        if (!isset($this->storage)) {
            $this->storage = new RdsStorage();
        }

        return $this->storage;
    }
}
