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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 * @author Ivan Klimchuk <klimchuk@1pt.com>
 */

namespace oat\taoEventLog\scripts\update;

use common_ext_ExtensionUpdater;
use oat\funcAcl\model\event\AccessRightAddedEvent;
use oat\funcAcl\model\event\AccessRightRemovedEvent;
use oat\oatbox\event\EventManager;
use oat\taoEventLog\model\LoggerService;

/**
 * Class Updater
 * @package oat\taoEventLog\scripts\update
 */
class Updater extends common_ext_ExtensionUpdater
{
    /**
     * @param $initialVersion
     * @return string $versionUpdatedTo
     */
    public function update($initialVersion)
    {
        $this->skip('0.1.0', '0.1.1');

        if ($this->isVersion('0.1.1')) {
            /** @var LoggerService $loggerService */
            $loggerService = $this->getServiceManager()->get(LoggerService::SERVICE_ID);
            $currentConfig = $loggerService->getOptions();

            $currentConfig[LoggerService::OPTION_EXPORTABLE_QUANTITY] = 10000;
            $currentConfig[LoggerService::OPTION_EXPORTABLE_PERIOD] = 'PT24H';

            $this->getServiceManager()->register(LoggerService::SERVICE_ID, new LoggerService($currentConfig));

            $this->setVersion('0.2.0');
        }

        if ($this->isVersion('0.2.0')) {

            /** @var EventManager $eventManager */
            $eventManager = $this->getServiceManager()->get(EventManager::CONFIG_ID);

            $eventManager->attach(AccessRightAddedEvent::class, [LoggerService::class, 'logEvent']);
            $eventManager->attach(AccessRightRemovedEvent::class, [LoggerService::class, 'logEvent']);

            $this->getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);

            $this->setVersion('0.2.1');
        }
    }
}
