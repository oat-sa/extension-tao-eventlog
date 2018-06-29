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

use common_exception_Error;
use oat\oatbox\extension\InstallAction;
use common_ext_ExtensionsManager;
use common_report_Report;
use oat\tao\model\event\ClassFormUpdatedEvent;
use oat\tao\model\event\CsvImportEvent;
use oat\tao\model\event\LoginFailedEvent;
use oat\tao\model\event\LoginSucceedEvent;
use oat\tao\model\event\RdfExportEvent;
use oat\tao\model\event\RdfImportEvent;
use oat\tao\model\event\RoleChangedEvent;
use oat\tao\model\event\RoleCreatedEvent;
use oat\tao\model\event\RoleRemovedEvent;
use oat\tao\model\event\UserCreatedEvent;
use oat\tao\model\event\UserRemovedEvent;
use oat\tao\model\event\UserUpdatedEvent;
use oat\taoEventLog\model\eventLog\LoggerService;
use oat\taoEventLog\model\eventLog\RdsStorage;
use oat\taoQtiItem\model\event\QtiItemExportEvent;
use oat\taoQtiItem\model\event\QtiItemImportEvent;
use oat\taoQtiItem\model\event\QtiItemMetadataExportEvent;
use oat\taoQtiTest\models\event\QtiTestExportEvent;
use oat\taoQtiTest\models\event\QtiTestImportEvent;
use oat\taoQtiTest\models\event\QtiTestMetadataExportEvent;

/**
 * Class RegisterLoggerService
 * @package oat\taoEventLog\scripts\install
 */
class RegisterLoggerService extends InstallAction
{
    /**
     * @param $params
     * @return common_report_Report
     * @throws common_exception_Error
     */
    public function __invoke($params)
    {
        /** @var common_ext_ExtensionsManager $extensionManager */
        $extensionManager = $this->getServiceManager()->get(common_ext_ExtensionsManager::SERVICE_ID);

        $this->registerService(LoggerService::SERVICE_ID, new LoggerService([
            LoggerService::OPTION_STORAGE => RdsStorage::SERVICE_ID,
            LoggerService::OPTION_ROTATION_PERIOD => 'P90D',
            LoggerService::OPTION_EXPORTABLE_QUANTITY => 10000
        ]));

        $this->registerEvent(LoginFailedEvent::class, [LoggerService::class, 'logEvent']);
        $this->registerEvent(LoginSucceedEvent::class,[LoggerService::class, 'logEvent']);
        $this->registerEvent(RoleRemovedEvent::class, [LoggerService::class, 'logEvent']);
        $this->registerEvent(RoleCreatedEvent::class, [LoggerService::class, 'logEvent']);
        $this->registerEvent(RoleChangedEvent::class, [LoggerService::class, 'logEvent']);
        $this->registerEvent(UserCreatedEvent::class, [LoggerService::class, 'logEvent']);
        $this->registerEvent(UserUpdatedEvent::class, [LoggerService::class, 'logEvent']);
        $this->registerEvent(UserRemovedEvent::class, [LoggerService::class, 'logEvent']);
        $this->registerEvent(ClassFormUpdatedEvent::class, [LoggerService::class, 'logEvent']);
        $this->registerEvent(RdfImportEvent::class, [LoggerService::class, 'logEvent']);
        $this->registerEvent(CsvImportEvent::class, [LoggerService::class, 'logEvent']);
        $this->registerEvent(RdfExportEvent::class, [LoggerService::class, 'logEvent']);

        if ($extensionManager->isEnabled('taoDeliveryRdf')) {
            $this->registerEvent('oat\\taoDeliveryRdf\\model\\event\\DeliveryCreatedEvent', [LoggerService::class, 'logEvent']);
            $this->registerEvent('oat\\taoDeliveryRdf\\model\\event\\DeliveryRemovedEvent', [LoggerService::class, 'logEvent']);
            $this->registerEvent('oat\\taoDeliveryRdf\\model\\event\\DeliveryUpdatedEvent', [LoggerService::class, 'logEvent']);
        }

        if ($extensionManager->isEnabled('funcAcl')) {
            $this->registerEvent('oat\\funcAcl\\model\\event\\AccessRightAddedEvent', [LoggerService::class, 'logEvent']);
            $this->registerEvent('oat\\funcAcl\\model\\event\\AccessRightRemovedEvent', [LoggerService::class, 'logEvent']);
        }

        if ($extensionManager->isEnabled('taoTests')) {
            $this->registerEvent('oat\\taoTests\\models\\event\\TestExportEvent', [LoggerService::class, 'logEvent']);
            $this->registerEvent('oat\\taoTests\\models\\event\\TestCreatedEvent', [LoggerService::class, 'logEvent']);
            $this->registerEvent('oat\\taoTests\\models\\event\\TestUpdatedEvent', [LoggerService::class, 'logEvent']);
            $this->registerEvent('oat\\taoTests\\models\\event\\TestRemovedEvent', [LoggerService::class, 'logEvent']);
            $this->registerEvent('oat\\taoTests\\models\\event\\TestDuplicatedEvent', [LoggerService::class, 'logEvent']);
        }

        if ($extensionManager->isEnabled('taoDacSimple')) {
            $this->registerEvent('oat\\taoDacSimple\\model\\event\\DacAddedEvent', [LoggerService::class, 'logEvent']);
            $this->registerEvent('oat\\taoDacSimple\\model\\event\\DacRemovedEvent', [LoggerService::class, 'logEvent']);
        }

        if ($extensionManager->isEnabled('taoTestTaker')) {
            $this->registerEvent('oat\\taoTestTaker\\models\\events\\TestTakerClassCreatedEvent', [LoggerService::class, 'logEvent']);
            $this->registerEvent('oat\\taoTestTaker\\models\\events\\TestTakerClassRemovedEvent', [LoggerService::class, 'logEvent']);
            $this->registerEvent('oat\\taoTestTaker\\models\\events\\TestTakerCreatedEvent', [LoggerService::class, 'logEvent']);
            $this->registerEvent('oat\\taoTestTaker\\models\\events\\TestTakerUpdatedEvent', [LoggerService::class, 'logEvent']);
            $this->registerEvent('oat\\taoTestTaker\\models\\events\\TestTakerRemovedEvent', [LoggerService::class, 'logEvent']);
        }

        if ($extensionManager->isEnabled('taoItems')) {
            $this->registerEvent('oat\\taoItems\\model\\event\\ItemCreatedEvent', [LoggerService::class, 'logEvent']);
            $this->registerEvent('oat\\taoItems\\model\\event\\ItemUpdatedEvent', [LoggerService::class, 'logEvent']);
            $this->registerEvent('oat\\taoItems\\model\\event\\ItemRemovedEvent', [LoggerService::class, 'logEvent']);
            $this->registerEvent('oat\\taoItems\\model\\event\\ItemDuplicatedEvent', [LoggerService::class, 'logEvent']);
        }

        if ($extensionManager->isEnabled('taoQtiItem')) {
            $this->registerEvent(QtiItemImportEvent::class, [LoggerService::class, 'logEvent']);
            $this->registerEvent(QtiItemMetadataExportEvent::class, [LoggerService::class, 'logEvent']);
            $this->registerEvent(QtiItemExportEvent::class, [LoggerService::class, 'logEvent']);
        }

        if ($extensionManager->isEnabled('taoQtiTest')) {
            $this->registerEvent(QtiTestImportEvent::class, [LoggerService::class, 'logEvent']);
            $this->registerEvent(QtiTestMetadataExportEvent::class, [LoggerService::class, 'logEvent']);
            $this->registerEvent(QtiTestExportEvent::class, [LoggerService::class, 'logEvent']);
        }

        return new common_report_Report(common_report_Report::TYPE_SUCCESS, __('Registered EventLog Logger Service'));
    }
}
