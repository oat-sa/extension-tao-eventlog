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

namespace oat\taoMonitoring\scripts\install;


use oat\oatbox\event\EventManager;
use oat\taoEventLog\model\EventLogService;
use oat\taoEventLog\model\storage\RdsStorage;

class RegisterRdsEventLog extends \common_ext_action_InstallAction
{
    
    public function __invoke($params)
    {
        $persistenceId = count($params) > 0 ? reset($params) : 'default';

        /** Register new service */
        $this->getServiceManager()->register(EventLogService::SERVICE_ID, new EventLogService([RdsStorage::OPTION_PERSISTENCE => $persistenceId]));

        /** @var RdsStorage $storage */
        $storage = new RdsStorage( $persistenceId );
        $storage->createStorage();
        
        $this->appendEvents();
        
        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Registered event log'));
    }
    
    private function appendEvents()
    {
        $eventManager = $this->getServiceManager()->get(EventManager::CONFIG_ID);

        // count executions
        $eventManager->attach(
            'oat\\taoDelivery\\models\\classes\\execution\\event\\DeliveryExecutionCreated',
            array('\\oat\\taoMonitoring\\model\\TestTakerDeliveryLog\\event\\Events', 'deliveryExecutionCreated')
        );

        // finished executions
        $eventManager->attach(
            'oat\\taoDelivery\\models\\classes\\execution\\event\\DeliveryExecutionState',
            array('\\oat\\taoMonitoring\\model\\TestTakerDeliveryLog\\event\\Events', 'deliveryExecutionState')
        );

        // catch switch items - on switching recount all statistic for testtaker
        $eventManager->attach(
            'oat\\taoQtiTest\\models\\event\\QtiMoveEvent',
            array('\\oat\\taoMonitoring\\model\\TestTakerDeliveryLog\\event\\Events', 'qtiMoveEvent')
        );

        $this->getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);
    }
}
