<?php

declare(strict_types=1);

namespace oat\taoEventLog\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoEventLog\model\eventLog\LoggerService;
use oat\taoItems\model\event\ItemContentViewEvent;

final class Version202607262047002752_taoEventLog extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Attach itemContentViewEvent to LoggerService';
    }

    public function up(Schema $schema): void
    {
        $eventManager = $this->getEventManager();
        $eventManager->attach(ItemContentViewEvent::class, [LoggerService::class, 'logEvent']);
        $this->getServiceLocator()->register(EventManager::SERVICE_ID, $eventManager);
    }

    public function down(Schema $schema): void
    {
        $eventManager = $this->getEventManager();
        $eventManager->detach(ItemContentViewEvent::class, [LoggerService::class, 'logEvent']);
        $this->getServiceLocator()->register(EventManager::SERVICE_ID, $eventManager);
    }

    private function getEventManager(): EventManager
    {
        return $this->getServiceLocator()->get(EventManager::SERVICE_ID);
    }
}
