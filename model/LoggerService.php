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
use DateTime;
use DateTimeImmutable;
use JsonSerializable;
use oat\dtms\DateInterval;
use oat\oatbox\event\Event;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManager;
use oat\taoEventLog\model\storage\RdsStorage;

/**
 * Class LoggerService
 * @package oat\taoEventLog\model
 */
class LoggerService extends ConfigurableService
{
    const SERVICE_ID = 'taoEventLog/logger';

    const OPTION_STORAGE = 'storage';
    const OPTION_ROTATION_PERIOD = 'rotation_period';
    const OPTION_EXPORTABLE_PERIOD = 'exportable_period';
    const OPTION_EXPORTABLE_QUANTITY = 'exportable_quantity';

    /**
     * @param Event $event
     */
    public static function logEvent(Event $event)
    {
        $action = 'cli' === php_sapi_name()
            ? $_SERVER['PHP_SELF']
            : Context::getInstance()->getRequest()->getRequestURI();

        /** @var common_session_Session $session */
        $session = common_session_SessionManager::getSession();

        /** @var common_user_User $currentUser */
        $currentUser = $session->getUser();

        $data = is_subclass_of($event, JsonSerializable::class) ? $event : [];

        try {
            static::getStorage()->log(
                $event->getName(),
                $action,
                $currentUser->getIdentifier(),
                join(',', $currentUser->getPropertyValues(PROPERTY_USER_ROLES)),
                // time in the unix timestamp (like a utc)
                (new DateTime('now', new \DateTimeZone('UTC')))->format(DateTime::ISO8601),
                json_encode($data)
            );
        } catch (\Exception $e) {
            \common_Logger::e('Error logging to DB ' . $e->getMessage());
        }

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
     * @return array
     */
    public function searchInstances(array $filters = [], array $options = [])
    {
        return $this->getStorage()->search($this->prepareParams($filters), $options);
    }

    /**
     * Count records in log which are meet the search criteria
     * @param array $filters
     * @param array $options
     * @return integer
     */
    public function count(array $filters = [], array $options = [])
    {
        return $this->getStorage()->count($this->prepareParams($filters, $options));
    }

    /**
     * @param array $params
     * @return array
     */
    protected function prepareParams(array $params)
    {
        return $this->getStorage()->search($this->prepareParams($params));
    }

    /**
     * @param array $params
     * @return int
     */
    public function count(array $params = [])
    {
        return self::getStorage()->count($this->prepareParams($params));
    }

    /**
     * @param $params
     * @return mixed
     */
    protected function prepareParams(array $params)
    {
        /** @var common_session_Session $session */
        $session = common_session_SessionManager::getSession();

        $timeZone = $session->getTimeZone();
        $utc = new \DateTimeZone('UTC');

        if ((isset($params['periodStart']) && !empty($params['periodStart']))) {
            $params['periodStart'] = (new DateTime($params['periodStart'], new \DateTimeZone($timeZone)))->setTimezone($utc)->format(DateTime::ISO8601);
        }

        if ((isset($params['periodEnd']) && !empty($params['periodEnd']))) {
            $params['periodEnd'] = (new DateTime($params['periodEnd'], new \DateTimeZone($timeZone)))->setTimezone($utc)->format(DateTime::ISO8601);
        }
        return $params;
    }

    /**
     * @return RdsStorage|StorageInterface
     */
    private static function getStorage()
    {
        $storage = ServiceManager::getServiceManager()->get(self::SERVICE_ID)->getOption(self::OPTION_STORAGE);

        return ServiceManager::getServiceManager()->get($storage);
    }
}
