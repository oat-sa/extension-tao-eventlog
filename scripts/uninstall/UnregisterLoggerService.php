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

namespace oat\taoEventLog\scripts\uninstall;

use common_ext_action_InstallAction;
use common_Logger;
use common_report_Report;
use oat\oatbox\action\Action;
use oat\taoEventLog\model\LoggerService;

/**
 * Class UnregisterRdsStorage
 * @package oat\taoEventLog\scripts\uninstall
 */
class UnregisterRdsStorage extends common_ext_action_InstallAction implements Action {

    public function __invoke($params)
    {
        if (!$this->getServiceManager()->has(LoggerService::SERVICE_ID)) {
            common_Logger::i(sprintf("Service '%s' not found and can not be dropped", LoggerService::SERVICE_ID));
        }

        $this->registerService(LoggerService::SERVICE_ID, null);

        return new common_report_Report(common_report_Report::TYPE_SUCCESS, __('Unregistered EventLog Logger Service'));
    }
}
