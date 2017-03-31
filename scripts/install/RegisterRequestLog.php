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
 * @author Ivan Klimchuk <klimchuk@1pt.com>
 */

namespace oat\taoEventLog\scripts\install;

use oat\oatbox\extension\AbstractAction;
use common_report_Report;
use oat\tao\model\event\BeforeAction;
use oat\oatbox\event\EventManager;
use oat\taoEventLog\model\requestLog\rds\RdsRequestLogStorage;

/**
 * Class RegisterRequestLog
 * @package oat\taoEventLog\scripts\install
 */
class RegisterRequestLog extends AbstractAction
{
    /**
     * @param $params
     * @return common_report_Report
     */
    public function __invoke($params)
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
        RdsRequestLogStorage::install('default');
//        $eventManager->attach(BeforeAction::class, [RdsRequestLogStorage::SERVICE_ID, 'catchEvent']);
//        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
        return new common_report_Report(common_report_Report::TYPE_SUCCESS, __('Request log storage successfully created'));
    }
}
