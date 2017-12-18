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
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */

namespace oat\taoEventLog\scripts\install;

use oat\oatbox\extension\AbstractAction;
use common_report_Report;
use oat\oatbox\service\ServiceNotFoundException;
use oat\taoEventLog\model\userLastActivityLog\rds\UserLastActivityLogStorage;
use oat\oatbox\event\EventManager;
use oat\tao\model\event\BeforeAction;

/**
 * Class RegisterUserLastActivityLog
 * @package oat\taoEventLog\scripts\install
 */
class RegisterUserLastActivityLog extends AbstractAction
{
    /**
     * @param $params
     * @return common_report_Report
     * @throws \common_Exception
     */
    public function __invoke($params)
    {
        try {
            $service = $this->getServiceManager()->get(UserLastActivityLogStorage::SERVICE_ID);
        } catch (ServiceNotFoundException $e) {
            $service = new UserLastActivityLogStorage([UserLastActivityLogStorage::OPTION_PERSISTENCE => 'default']);
        }
        $service->setOption(UserLastActivityLogStorage::OPTION_ACTIVE_USER_THRESHOLD, 300);
        $persistenceManager = $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID);
        $persistence = $persistenceManager->getPersistenceById($service->getOption(UserLastActivityLogStorage::OPTION_PERSISTENCE));

        UserLastActivityLogStorage::install($persistence);

        $this->getServiceManager()->register(UserLastActivityLogStorage::SERVICE_ID, $service);

        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
        $eventManager->attach(
            BeforeAction::class,
            [UserLastActivityLogStorage::SERVICE_ID, 'catchEvent']
        );

        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
        return new common_report_Report(common_report_Report::TYPE_SUCCESS, __('User activity log storage successfully created'));
    }
}
