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

namespace oat\taoEventLog\scripts\install;

use common_ext_action_InstallAction;
use common_report_Report;
use oat\oatbox\action\Action;
use oat\tao\model\event\LoginEvent;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoEventLog\model\LoggerService;
use oat\taoEventLog\model\storage\RdsStorage;
use oat\taoMonitoring\model\TestTakerDeliveryLog\event\Events;
use oat\taoQtiTest\models\event\QtiMoveEvent;

/**
 * Class RegisterRdsEventLog
 * @package oat\taoMonitoring\scripts\install
 */
class RegisterRdsEventLog extends common_ext_action_InstallAction implements Action
{
    /**
     * @param $params
     * @return common_report_Report
     */
    public function __invoke($params)
    {
        $persistenceId = count($params) > 0 ? reset($params) : 'default';

        /** Register new service */
        $this->registerService(LoggerService::SERVICE_ID, new LoggerService([RdsStorage::OPTION_PERSISTENCE => $persistenceId]));

        /** @var RdsStorage $storage */
        $storage = new RdsStorage($persistenceId);
        $storage->createStorage();

        $this->registerEvent(LoginEvent::class, [$this->getServiceManager()->get(EventLogService::SERVICE_ID), 'logEvent']);

        $this->registerEvent(DeliveryExecutionCreated::class, [Events::class, 'deliveryExecutionCreated']);
        $this->registerEvent(DeliveryExecutionState::class, [Events::class, 'deliveryExecutionState']);
        $this->registerEvent(QtiMoveEvent::class, [Events::class, 'qtiMoveEvent']);

        return new common_report_Report(common_report_Report::TYPE_SUCCESS, __('Registered EventLog Service'));
    }
}
