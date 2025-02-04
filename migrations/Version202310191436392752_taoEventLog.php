<?php

declare(strict_types=1);

namespace oat\taoEventLog\migrations;

use common_ext_ExtensionsManager;
use Doctrine\DBAL\Schema\Schema;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoEventLog\model\eventLog\LoggerService;

final class Version202310191436392752_taoEventLog extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Attach DacChangedEvent to LoggerService';
    }

    public function up(Schema $schema): void
    {
        if ($this->getExtensionsManager()->isEnabled('taoDacSimple')) {
            $eventManager = $this->getEventManager();
            $eventManager->attach(
                'oat\taoDacSimple\model\event\DacChangedEvent',
                [LoggerService::class, 'log']
            );
            $this->getServiceLocator()->register(EventManager::SERVICE_ID, $eventManager);
        }
    }

    public function down(Schema $schema): void
    {
        $eventManager = $this->getEventManager();
        $eventManager->detach(
            'oat\taoDacSimple\model\event\DacChangedEvent',
            [LoggerService::class, 'log']
        );
        $this->getServiceLocator()->register(EventManager::SERVICE_ID, $eventManager);
    }

    private function getExtensionsManager(): common_ext_ExtensionsManager
    {
        return $this->getServiceLocator()->get(common_ext_ExtensionsManager::SERVICE_ID);
    }

    private function getEventManager(): EventManager
    {
        return $this->getServiceLocator()->get(EventManager::SERVICE_ID);
    }
}
