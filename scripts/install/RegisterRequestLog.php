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

use oat\oatbox\extension\InstallAction;
use common_report_Report;
use oat\oatbox\service\ServiceNotFoundException;
use oat\taoEventLog\model\requestLog\noStorage\NoStorage;
use oat\taoEventLog\model\requestLog\RequestLogService;

/**
 * Class RegisterRequestLog
 * @package oat\taoEventLog\scripts\install
 */
class RegisterRequestLog extends InstallAction
{
    /**
     * @param $params
     * @return common_report_Report
     * @throws \common_Exception
     */
    public function __invoke($params)
    {
        try {
            $this->getServiceManager()->get(RequestLogService::SERVICE_ID);
        } catch (ServiceNotFoundException $e) {
            $service = new RequestLogService([
                RequestLogService::OPTION_STORAGE => NoStorage::class,
            ]);
            $this->getServiceManager()->register(RequestLogService::SERVICE_ID, $service);
        }

        $this->registerEvent(
            'oat\\tao\\model\\event\\BeforeAction',
            [RequestLogService::SERVICE_ID, 'catchEvent']
        );

        return new common_report_Report(common_report_Report::TYPE_SUCCESS, __('Request log storage successfully created'));
    }
}
