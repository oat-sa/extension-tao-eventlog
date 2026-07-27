<?php

declare(strict_types=1);

namespace oat\taoEventLog\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoEventLog\model\eventLog\LoggerService;
use oat\taoTests\models\event\TestContentViewEvent;

final class Version202607271024000000_taoEventLog extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Attach test content view event to LoggerService';
    }

    public function up(Schema $schema): void
    {
        $eventManager = $this->getEventManager();
        $eventManager->attach(TestContentViewEvent::class, [LoggerService::class, 'logEvent']);
        $this->getServiceLocator()->register(EventManager::SERVICE_ID, $eventManager);
    }

    public function down(Schema $schema): void
    {
        $eventManager = $this->getEventManager();
        $eventManager->detach(TestContentViewEvent::class, [LoggerService::class, 'logEvent']);
        $this->getServiceLocator()->register(EventManager::SERVICE_ID, $eventManager);
    }

    private function getEventManager(): EventManager
    {
        return $this->getServiceLocator()->get(EventManager::SERVICE_ID);
    }
}
