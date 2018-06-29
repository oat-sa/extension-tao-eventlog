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

use oat\oatbox\event\EventManager;
use oat\oatbox\service\ServiceManager;
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
use oat\taoQtiItem\model\event\QtiItemExportEvent;
use oat\taoQtiItem\model\event\QtiItemImportEvent;
use oat\taoQtiItem\model\event\QtiItemMetadataExportEvent;
use oat\taoQtiTest\models\event\QtiTestExportEvent;
use oat\taoQtiTest\models\event\QtiTestImportEvent;
use oat\taoQtiTest\models\event\QtiTestMetadataExportEvent;

if (!ServiceManager::getServiceManager()->has(EventManager::SERVICE_ID)) {
    return;
}

/** @var EventManager $eventManager */
$eventManager = ServiceManager::getServiceManager()->get(EventManager::SERVICE_ID);

/** @var \common_ext_ExtensionsManager $extensionManager */
$extensionManager = ServiceManager::getServiceManager()->get(\common_ext_ExtensionsManager::SERVICE_ID);

$eventManager->detach(LoginSucceedEvent::class, [LoggerService::class, 'logEvent']);
$eventManager->detach(LoginFailedEvent::class, [LoggerService::class, 'logEvent']);
$eventManager->detach(RoleRemovedEvent::class, [LoggerService::class, 'logEvent']);
$eventManager->detach(RoleCreatedEvent::class, [LoggerService::class, 'logEvent']);
$eventManager->detach(RoleChangedEvent::class, [LoggerService::class, 'logEvent']);
$eventManager->detach(UserCreatedEvent::class, [LoggerService::class, 'logEvent']);
$eventManager->detach(UserUpdatedEvent::class, [LoggerService::class, 'logEvent']);
$eventManager->detach(UserRemovedEvent::class, [LoggerService::class, 'logEvent']);
$eventManager->detach(RdfImportEvent::class, [LoggerService::class, 'logEvent']);
$eventManager->detach(CsvImportEvent::class, [LoggerService::class, 'logEvent']);
$eventManager->detach(RdfExportEvent::class, [LoggerService::class, 'logEvent']);

if ($extensionManager->getExtensionById('funcAcl')) {
    $eventManager->detach('oat\\funcAcl\\model\\event\\AccessRightAddedEvent', [LoggerService::class, 'logEvent']);
    $eventManager->detach('oat\\funcAcl\\model\\event\\AccessRightRemovedEvent', [LoggerService::class, 'logEvent']);
}

if ($extensionManager->isEnabled('taoDeliveryRdf')) {
    $eventManager->detach('oat\\taoDeliveryRdf\\model\\event\\DeliveryCreatedEvent', [LoggerService::class, 'logEvent']);
    $eventManager->detach('oat\\taoDeliveryRdf\\model\\event\\DeliveryRemovedEvent', [LoggerService::class, 'logEvent']);
    $eventManager->detach('oat\\taoDeliveryRdf\\model\\event\\DeliveryUpdatedEvent', [LoggerService::class, 'logEvent']);
}

if ($extensionManager->isEnabled('taoTests')) {
    $eventManager->detach('oat\\taoTests\\models\\event\\TestCreatedEvent', [LoggerService::class, 'logEvent']);
    $eventManager->detach('oat\\taoTests\\models\\event\\TestUpdatedEvent', [LoggerService::class, 'logEvent']);
    $eventManager->detach('oat\\taoTests\\models\\event\\TestRemovedEvent', [LoggerService::class, 'logEvent']);
    $eventManager->detach('oat\\taoTests\\models\\event\\TestDuplicatedEvent', [LoggerService::class, 'logEvent']);
}

if ($extensionManager->isEnabled('taoDacSimple')) {
    $eventManager->detach('oat\\taoDacSimple\\model\\event\\DacAddedEvent', [LoggerService::class, 'logEvent']);
    $eventManager->detach('oat\\taoDacSimple\\model\\event\\DacRemovedEvent', [LoggerService::class, 'logEvent']);
}

if ($extensionManager->isEnabled('taoTestTaker')) {
    $eventManager->detach('oat\\taoTestTaker\\models\\events\\TestTakerClassCreatedEvent', [LoggerService::class, 'logEvent']);
    $eventManager->detach('oat\\taoTestTaker\\models\\events\\TestTakerClassRemovedEvent', [LoggerService::class, 'logEvent']);
    $eventManager->detach('oat\\taoTestTaker\\models\\events\\TestTakerCreatedEvent', [LoggerService::class, 'logEvent']);
    $eventManager->detach('oat\\taoTestTaker\\models\\events\\TestTakerUpdatedEvent', [LoggerService::class, 'logEvent']);
    $eventManager->detach('oat\\taoTestTaker\\models\\events\\TestTakerRemovedEvent', [LoggerService::class, 'logEvent']);
}

if ($extensionManager->isEnabled('taoItems')) {
    $eventManager->detach('oat\\taoItems\\model\\event\\ItemCreatedEvent', [LoggerService::class, 'logEvent']);
    $eventManager->detach('oat\\taoItems\\model\\event\\ItemUpdatedEvent', [LoggerService::class, 'logEvent']);
    $eventManager->detach('oat\\taoItems\\model\\event\\ItemRemovedEvent', [LoggerService::class, 'logEvent']);
    $eventManager->detach('oat\\taoItems\\model\\event\\ItemDuplicatedEvent', [LoggerService::class, 'logEvent']);
}

if ($extensionManager->isEnabled('taoQtiItem')) {
    $eventManager->detach(QtiItemImportEvent::class, [LoggerService::class, 'logEvent']);
    $eventManager->detach(QtiItemMetadataExportEvent::class, [LoggerService::class, 'logEvent']);
    $eventManager->detach(QtiItemExportEvent::class, [LoggerService::class, 'logEvent']);
}

if ($extensionManager->isEnabled('taoQtiTest')) {
    $eventManager->detach(QtiTestImportEvent::class, [LoggerService::class, 'logEvent']);
    $eventManager->detach(QtiTestMetadataExportEvent::class, [LoggerService::class, 'logEvent']);
    $eventManager->detach(QtiTestExportEvent::class, [LoggerService::class, 'logEvent']);
}

ServiceManager::getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
