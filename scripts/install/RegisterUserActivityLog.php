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
use oat\taoEventLog\model\userActivityLog\rds\UserActivityLogStorage;

/**
 * Class RegisterUserActivityLog
 * @package oat\taoEventLog\scripts\install
 */
class RegisterUserActivityLog extends AbstractAction
{
    /**
     * @param $params
     * @return common_report_Report
     * @throws \common_Exception
     */
    public function __invoke($params)
    {
        try {
            $storageService = $this->getServiceManager()->get(UserActivityLogStorage::SERVICE_ID);
        } catch (ServiceNotFoundException $e) {
            $storageService = new UserActivityLogStorage([UserActivityLogStorage::OPTION_PERSISTENCE => 'default']);
        }

        $persistenceManager = $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID);
        $persistence = $persistenceManager->getPersistenceById($storageService->getOption(UserActivityLogStorage::OPTION_PERSISTENCE));

        UserActivityLogStorage::install($persistence);

        $this->getServiceManager()->register(UserActivityLogStorage::SERVICE_ID, $storageService);


        return new common_report_Report(common_report_Report::TYPE_SUCCESS, __('User activity log storage successfully created'));
    }
}
