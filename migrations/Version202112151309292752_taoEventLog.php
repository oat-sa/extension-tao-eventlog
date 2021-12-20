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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA.
 */

declare(strict_types=1);

namespace oat\taoEventLog\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\oatbox\event\EventManager;
use oat\generis\model\data\event\ResourceDeleted;
use oat\taoEventLog\model\eventLog\LoggerService;
use oat\generis\model\data\event\ClassDeletedEvent;
use oat\tao\scripts\tools\migrations\AbstractMigration;

final class Version202112151309292752_taoEventLog extends AbstractMigration
{
    private const EVENTS = [
        ResourceDeleted::class,
        ClassDeletedEvent::class,
    ];

    public function getDescription(): string
    {
        return sprintf(
            'Register events to log: "%s"',
            implode('", "', self::EVENTS)
        );
    }

    public function up(Schema $schema): void
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceLocator()->getContainer()->get(EventManager::SERVICE_ID);

        foreach (self::EVENTS as $event) {
            $eventManager->attach($event, [LoggerService::class, 'logEvent']);
        }

        $this->getServiceLocator()->register(EventManager::SERVICE_ID, $eventManager);
    }

    public function down(Schema $schema): void
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceLocator()->getContainer()->get(EventManager::SERVICE_ID);

        foreach (self::EVENTS as $event) {
            $eventManager->detach($event, [LoggerService::class, 'logEvent']);
        }

        $this->getServiceLocator()->register(EventManager::SERVICE_ID, $eventManager);
    }
}
