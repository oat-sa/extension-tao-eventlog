<?php

declare(strict_types=1);

namespace oat\taoEventLog\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoEventLog\model\eventLog\LoggerService;
use oat\taoItems\model\event\ItemContentViewEvent;
use oat\taoItems\model\event\ItemPrintAttemptEvent;
use oat\taoTests\models\event\TestContentViewEvent;

final class Version202607281623000000_taoEventLog extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Attach preview events to LoggerService';
    }

    public function up(Schema $schema): void
    {
        $eventManager = $this->getEventManager();
        $eventManager->attach(ItemContentViewEvent::class, [LoggerService::class, 'logEvent']);
        $eventManager->attach(TestContentViewEvent::class, [LoggerService::class, 'logEvent']);
        $eventManager->attach(ItemPrintAttemptEvent::class, [LoggerService::class, 'logEvent']);
        $this->getServiceLocator()->register(EventManager::SERVICE_ID, $eventManager);
    }

    public function down(Schema $schema): void
    {
        $eventManager = $this->getEventManager();
        $eventManager->detach(ItemContentViewEvent::class, [LoggerService::class, 'logEvent']);
        $eventManager->detach(TestContentViewEvent::class, [LoggerService::class, 'logEvent']);
        $eventManager->detach(ItemPrintAttemptEvent::class, [LoggerService::class, 'logEvent']);
        $this->getServiceLocator()->register(EventManager::SERVICE_ID, $eventManager);
    }

    private function getEventManager(): EventManager
    {
        return $this->getServiceLocator()->get(EventManager::SERVICE_ID);
    }
}
