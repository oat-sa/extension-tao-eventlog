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

use common_exception_Error;
use common_ext_action_InstallAction;
use common_persistence_SqlPersistence;
use common_report_Report;
use oat\taoEventLog\model\storage\RdsStorage;

/**
 * Class RegisterRdsStorage
 * @package oat\taoEventLog\scripts\install
 */
class RegisterRdsStorage extends common_ext_action_InstallAction
{
    /**
     * @param $params
     * @return common_report_Report
     * @throws common_exception_Error
     */
    public function __invoke($params)
    {
        $persistenceId = count($params) > 0 ? reset($params) : 'default';
        $storageService = new RdsStorage([RdsStorage::OPTION_PERSISTENCE => $persistenceId]);
        $this->registerService(RdsStorage::SERVICE_ID, $storageService);
        $this->createTable($storageService->getPersistence());
        return new common_report_Report(common_report_Report::TYPE_SUCCESS, __('Registered and created EventLog Rds Storage'));
    }

    /**
     * @param common_persistence_SqlPersistence $persistence
     */
    public function createTable(\common_persistence_SqlPersistence $persistence)
    {
        RdsStorage::install($persistence);
    }
}
