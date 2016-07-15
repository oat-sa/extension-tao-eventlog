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

use common_ext_ExtensionsManager;
use common_ext_ExtensionUpdater;
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

        $this->skip('0.2.0', '0.3.0');

        if ($this->isVersion('0.3.0')) {

            if (common_ext_ExtensionsManager::singleton()->getExtensionById('taoDeliveryRdf')
            ) {
                /** @var EventManager $eventManager */
                $eventManager = $this->getServiceManager()->get(EventManager::CONFIG_ID);

                $eventManager->attach('oat\\taoDeliveryRdf\\model\\event\\DeliveryCreatedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoDeliveryRdf\\model\\event\\DeliveryRemovedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoDeliveryRdf\\model\\event\\DeliveryUpdatedEvent', [LoggerService::class, 'logEvent']);

                $this->getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);
            }
            $this->setVersion('0.3.1');
        }


        if ($this->isVersion('0.3.1')) {


            if (common_ext_ExtensionsManager::singleton()->getExtensionById('funcAcl')) {
                /** @var EventManager $eventManager */
                $eventManager = $this->getServiceManager()->get(EventManager::CONFIG_ID);

                $eventManager->attach('oat\\funcAcl\\model\\event\\AccessRightAddedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\funcAcl\\model\\event\\AccessRightRemovedEvent', [LoggerService::class, 'logEvent']);
                $this->getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);

            }

            $this->setVersion('0.3.2');
        }
    }
}
