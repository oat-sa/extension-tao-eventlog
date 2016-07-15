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
use oat\tao\model\event\LoginFailedEvent;
use oat\tao\model\event\LoginSucceedEvent;
use oat\tao\model\event\RoleChangedEvent;
use oat\tao\model\event\RoleCreatedEvent;
use oat\tao\model\event\RoleRemovedEvent;
use oat\tao\model\event\UserCreatedEvent;
use oat\tao\model\event\UserRemovedEvent;
use oat\tao\model\event\UserUpdatedEvent;
use oat\taoEventLog\model\LoggerService;

if (!ServiceManager::getServiceManager()->has(EventManager::CONFIG_ID)) {
    return;
}

/** @var EventManager $eventManager */
$eventManager = ServiceManager::getServiceManager()->get(EventManager::CONFIG_ID);

$eventManager->detach(LoginSucceedEvent::class, [LoggerService::class, 'logEvent']);
$eventManager->detach(LoginFailedEvent::class, [LoggerService::class, 'logEvent']);
$eventManager->detach(RoleRemovedEvent::class, [LoggerService::class, 'logEvent']);
$eventManager->detach(RoleCreatedEvent::class, [LoggerService::class, 'logEvent']);
$eventManager->detach(RoleChangedEvent::class, [LoggerService::class, 'logEvent']);
$eventManager->detach(UserCreatedEvent::class, [LoggerService::class, 'logEvent']);
$eventManager->detach(UserUpdatedEvent::class, [LoggerService::class, 'logEvent']);
$eventManager->detach(UserRemovedEvent::class, [LoggerService::class, 'logEvent']);

if (\common_ext_ExtensionsManager::singleton()->getExtensionById('taoDeliveryRdf')) {
    $eventManager->detach(\oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent::class, [LoggerService::class, 'logEvent']);
    $eventManager->detach(\oat\taoDeliveryRdf\model\event\DeliveryRemovedEvent::class, [LoggerService::class, 'logEvent']);
    $eventManager->detach(\oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent::class, [LoggerService::class, 'logEvent']);
}


ServiceManager::getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);
