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
 * Copyright (c) 2014-2016 (original work) Open Assessment Technologies SA;
 *
 * @author Ivan Klimchuk <klimchuk@1pt.com>
 */

namespace oat\taoEventLog\scripts\update;

use common_ext_ExtensionsManager;
use common_ext_ExtensionUpdater;
use oat\oatbox\event\EventManager;
use oat\tao\model\event\CsvImportEvent;
use oat\tao\model\event\RdfExportEvent;
use oat\tao\model\event\RdfImportEvent;
use oat\taoEventLog\model\eventLog\LoggerService;
use oat\taoEventLog\model\StorageInterface;
use oat\taoEventLog\model\requestLog\noStorage\NoStorage;
use oat\taoEventLog\model\userLastActivityLog\rds\UserLastActivityLogStorage;
use oat\taoEventLog\model\eventLog\RdsStorage;
use oat\tao\model\event\BeforeAction;
use oat\taoEventLog\model\requestLog\RequestLogService;
use oat\taoEventLog\model\requestLog\rds\RdsRequestLogStorage;
use oat\taoQtiItem\model\event\QtiItemExportEvent;
use oat\taoQtiItem\model\event\QtiItemImportEvent;
use oat\taoQtiItem\model\event\QtiItemMetadataExportEvent;
use oat\taoQtiTest\models\event\QtiTestExportEvent;
use oat\taoQtiTest\models\event\QtiTestImportEvent;
use oat\taoQtiTest\models\event\QtiTestMetadataExportEvent;

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

        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceManager()->get(EventManager::CONFIG_ID);


        if ($this->isVersion('0.1.1')) {
            /** @var LoggerService $loggerService */
            $loggerService = $this->getServiceManager()->get(LoggerService::SERVICE_ID);
            $currentConfig = $loggerService->getOptions();

            $currentConfig[LoggerService::OPTION_EXPORTABLE_QUANTITY] = 10000;
            $currentConfig['exportable_period'] = 'PT24H';

            $this->getServiceManager()->register(LoggerService::SERVICE_ID, new LoggerService($currentConfig));

            $this->setVersion('0.2.0');
        }

        $this->skip('0.2.0', '0.3.0');

        if ($this->isVersion('0.3.0')) {

            if (common_ext_ExtensionsManager::singleton()->getExtensionById('taoDeliveryRdf')
            ) {
                $eventManager->attach('oat\\taoDeliveryRdf\\model\\event\\DeliveryCreatedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoDeliveryRdf\\model\\event\\DeliveryRemovedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoDeliveryRdf\\model\\event\\DeliveryUpdatedEvent', [LoggerService::class, 'logEvent']);

                $this->getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);
            }
            $this->setVersion('0.3.1');
        }

        if ($this->isVersion('0.3.1')) {

            if (common_ext_ExtensionsManager::singleton()->getExtensionById('funcAcl')) {

                $eventManager->attach('oat\\funcAcl\\model\\event\\AccessRightAddedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\funcAcl\\model\\event\\AccessRightRemovedEvent', [LoggerService::class, 'logEvent']);
                $this->getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);

            }

            $this->setVersion('0.3.2');
        }

        if ($this->isVersion('0.3.2')) {

            if (common_ext_ExtensionsManager::singleton()->getExtensionById('taoTests')) {

                $eventManager->attach('oat\\taoTests\\models\\event\\TestExportEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoTests\\models\\event\\TestImportEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoTests\\models\\event\\TestCreatedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoTests\\models\\event\\TestUpdatedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoTests\\models\\event\\TestRemovedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoTests\\models\\event\\TestDuplicatedEvent', [LoggerService::class, 'logEvent']);

                $this->getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);

            }

            if (common_ext_ExtensionsManager::singleton()->getExtensionById('taoDacSimple')) {

                $eventManager->attach('oat\\taoDacSimple\\model\\event\\DacAddedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoDacSimple\\model\\event\\DacRemovedEvent', [LoggerService::class, 'logEvent']);
                $this->getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);

            }

            $storageService = $this->getServiceManager()->get(StorageInterface::SERVICE_ID);

            /** @var \common_persistence_SqlPersistence $persistence */
            $persistence = $storageService->getPersistence();

            $schemaManager = $persistence->getDriver()->getSchemaManager();
            $schema = $schemaManager->createSchema();
            $fromSchema = clone $schema;

            /** @var \Doctrine\DBAL\Schema\Table $tableResults */
            $tableResults = $schema->getTable(StorageInterface::EVENT_LOG_TABLE_NAME);

            $tableResults->changeColumn(StorageInterface::EVENT_LOG_ACTION, ["length" => 1000]);

            $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);

            foreach ($queries as $query) {
                $persistence->exec($query);
            }

            $this->setVersion('0.3.3');
        }

        if ($this->isVersion('0.3.3')) {

            if (common_ext_ExtensionsManager::singleton()->getExtensionById('taoTestTaker')) {

                $eventManager->attach('oat\\taoTestTaker\\models\\events\\TestTakerClassCreatedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoTestTaker\\models\\events\\TestTakerClassRemovedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoTestTaker\\models\\events\\TestTakerCreatedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoTestTaker\\models\\events\\TestTakerUpdatedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoTestTaker\\models\\events\\TestTakerRemovedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoTestTaker\\models\\events\\TestTakerExportedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoTestTaker\\models\\events\\TestTakerImportedEvent', [LoggerService::class, 'logEvent']);

                $this->getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);
            }

            $this->setVersion('0.3.4');
        }

        if ($this->isVersion('0.3.4')) {
            if (common_ext_ExtensionsManager::singleton()->getExtensionById('taoItems')) {
                $eventManager->attach('oat\\taoItems\\model\\event\\ItemExportEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoItems\\model\\event\\ItemImportEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoItems\\model\\event\\ItemCreatedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoItems\\model\\event\\ItemUpdatedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoItems\\model\\event\\ItemRemovedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->attach('oat\\taoItems\\model\\event\\ItemDuplicatedEvent', [LoggerService::class, 'logEvent']);

                $this->getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);
            }
            $this->setVersion('0.4.0');
        }

        $this->skip('0.4.0', '0.5.3');

        if ($this->isVersion('0.5.3')) {
            RdsRequestLogStorage::install('default');
            $this->setVersion('0.6.0');
        }

        $this->skip('0.6.0', '0.7.0');

        if ($this->isVersion('0.7.0')) {
            if (common_ext_ExtensionsManager::singleton()->isInstalled('taoProctoring')) {
                $eventManager->attach('oat\\taoProctoring\\model\\event\\DeliveryExecutionFinished', [LoggerService::class, 'logEvent']);
                $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
            }
            $this->setVersion('0.8.0');
        }
        $this->skip('0.8.0', '1.0.0');

        if ($this->isVersion('1.0.0')) {
            if (!common_ext_ExtensionsManager::singleton()->isInstalled('taoProctoring')) {
                $eventManager->detach('oat\\taoProctoring\\model\\event\\DeliveryExecutionFinished', [LoggerService::class, 'logEvent']);
                $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
            }
            $this->setVersion('1.0.1');
        }

        $this->skip('1.0.1', '1.1.3');

        if ($this->isVersion('1.1.3')) {
            $eventManager->attach('oat\\tao\\model\\event\\ClassFormUpdatedEvent', [LoggerService::class, 'logEvent']);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
            $this->setVersion('1.2.0');
        }

        $this->skip('1.2.0', '1.3.0');

        if ($this->isVersion('1.3.0')) {
            $eventLogStorage = $this->getServiceManager()->get('taoEventLog/storage');
            $eventLogService = $this->getServiceManager()->get('taoEventLog/logger');

            $this->getServiceManager()->unregister('taoEventLog/storage');
            $this->getServiceManager()->unregister('taoEventLog/logger');

            $eventLogService->setOption(LoggerService::OPTION_STORAGE, RdsStorage::SERVICE_ID);

            $this->getServiceManager()->register(LoggerService::SERVICE_ID, new LoggerService($eventLogService->getOptions()));

            $eventLogStorage = new RdsStorage($eventLogStorage->getOptions());

            $this->getServiceManager()->register(RdsStorage::SERVICE_ID, $eventLogStorage);
            $this->setVersion('1.4.0');
        }

        if ($this->isVersion('1.4.0')) {
            $service = new UserLastActivityLogStorage([UserLastActivityLogStorage::OPTION_PERSISTENCE => 'default']);
            $service->setOption(UserLastActivityLogStorage::OPTION_ACTIVE_USER_THRESHOLD, 300);
            $persistenceManager = $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID);
            $persistence = $persistenceManager->getPersistenceById($service->getOption(UserLastActivityLogStorage::OPTION_PERSISTENCE));

            UserLastActivityLogStorage::install($persistence);

            $this->getServiceManager()->register(UserLastActivityLogStorage::SERVICE_ID, $service);

            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);

            $eventManager->attach(
                BeforeAction::class,
                [UserLastActivityLogStorage::SERVICE_ID, 'catchEvent']
            );

            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

            $this->setVersion('1.5.0');
        }

        if ($this->isVersion('1.5.0')) {

            $requestLogStorage = $this->getServiceManager()->get(RequestLogService::SERVICE_ID);
            $requestLogService = new RequestLogService([
                RequestLogService::OPTION_STORAGE => RdsRequestLogStorage::class,
                RequestLogService::OPTION_STORAGE_PARAMETERS => $requestLogStorage->getOptions()
            ]);
            $this->getServiceManager()->register(RequestLogService::SERVICE_ID, $requestLogService);

            $this->setVersion('1.6.0');
        }

        $this->skip('1.6.0', '1.6.4');

        if ($this->isVersion('1.6.4')) {
            $service = $this->getServiceManager()->get(RequestLogService::SERVICE_ID);
            if (trim($service->getOption(RequestLogService::OPTION_STORAGE), '\\') === RdsRequestLogStorage::class) {
                $service->setOption(RequestLogService::OPTION_STORAGE, NoStorage::class);
                $this->getServiceManager()->register(RequestLogService::SERVICE_ID, $service);
            }

            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->attach(
                BeforeAction::class,
                [RequestLogService::SERVICE_ID, 'catchEvent']
            );
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

            $this->setVersion('1.7.0');
        }

        if ($this->isVersion('1.7.0')) {
            /** @var LoggerService $service */
            $service = $this->getServiceManager()->get(LoggerService::SERVICE_ID);
            $options = $service->getOptions();
            unset($options['exportable_period']);
            $service->setOptions($options);
            $this->getServiceManager()->register(LoggerService::SERVICE_ID, $service);

            $this->setVersion('1.8.0');
        }

        $this->skip('1.8.0', '1.9.0');

        if ($this->isVersion('1.9.0')) {
            /** @var \common_ext_ExtensionsManager $extensionManager */
            $extensionManager = $this->getServiceManager()->get(\common_ext_ExtensionsManager::SERVICE_ID);

            $eventManager->attach(RdfImportEvent::class, [LoggerService::class, 'logEvent']);
            $eventManager->attach(CsvImportEvent::class, [LoggerService::class, 'logEvent']);
            $eventManager->attach(RdfExportEvent::class, [LoggerService::class, 'logEvent']);

            if ($extensionManager->isEnabled('taoItems')) {
                $eventManager->detach('oat\\taoItems\\model\\event\\ItemExportEvent', [LoggerService::class, 'logEvent']);
                $eventManager->detach('oat\\taoItems\\model\\event\\ItemImportEvent', [LoggerService::class, 'logEvent']);
            }

            if ($extensionManager->isEnabled('taoTestTaker')) {
                $eventManager->detach('oat\\taoTestTaker\\models\\events\\TestTakerExportedEvent', [LoggerService::class, 'logEvent']);
                $eventManager->detach('oat\\taoTestTaker\\models\\events\\TestTakerImportedEvent', [LoggerService::class, 'logEvent']);
            }

            if ($extensionManager->isEnabled('taoQtiItem')) {
                $eventManager->attach(QtiItemImportEvent::class, [LoggerService::class, 'logEvent']);
                $eventManager->attach(QtiItemMetadataExportEvent::class, [LoggerService::class, 'logEvent']);
                $eventManager->attach(QtiItemExportEvent::class, [LoggerService::class, 'logEvent']);
            }

            if ($extensionManager->isEnabled('taoTests')) {
                $eventManager->detach('oat\\taoTests\\models\\event\\TestExportEvent', [LoggerService::class, 'logEvent']);
                $eventManager->detach('oat\\taoTests\\models\\event\\TestImportEvent', [LoggerService::class, 'logEvent']);
            }

            if ($extensionManager->isEnabled('taoQtiTest')) {
                $eventManager->attach(QtiTestImportEvent::class, [LoggerService::class, 'logEvent']);
                $eventManager->attach(QtiTestMetadataExportEvent::class, [LoggerService::class, 'logEvent']);
                $eventManager->attach(QtiTestExportEvent::class, [LoggerService::class, 'logEvent']);
            }

            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

            $this->setVersion('1.10.0');
        }


        $this->skip('1.10.0', '2.1.3');
    }
}
