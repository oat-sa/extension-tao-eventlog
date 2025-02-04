<?php

declare(strict_types=1);

namespace oat\taoEventLog\migrations;

use common_persistence_Persistence;
use Doctrine\DBAL\Schema\Schema;
use oat\generis\persistence\PersistenceManager;
use oat\oatbox\event\EventManager;
use oat\oatbox\reporting\Report;
use oat\tao\model\Translation\Event\TranslationActionEvent;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoEventLog\model\eventLog\LoggerService;
use oat\taoEventLog\model\eventLog\RdsStorage;

/**
 * phpcs:disable Squiz.Classes.ValidClassName
 */
final class Version202502040031062752_taoEventLog extends AbstractMigration
{
    public function getDescription(): string
    {
        return sprintf(
            'Add column %s to table %s and register translation event',
            RdsStorage::EVENT_LOG_USER_LOGIN,
            RdsStorage::EVENT_LOG_TABLE_NAME
        );
    }

    public function up(Schema $schema): void
    {
        $originalSchema = clone $schema;
        $table = $schema->getTable(RdsStorage::EVENT_LOG_TABLE_NAME);

        $table->addColumn(
            RdsStorage::EVENT_LOG_USER_LOGIN,
            'string',
            ['notnull' => false, 'length' => 255, 'default' => null, 'comment' => 'User login']
        );
        $table->addIndex([RdsStorage::EVENT_LOG_USER_LOGIN], 'idx_user_login');

        $this->migrate($originalSchema, $schema);

        $this->addReport(
            Report::createSuccess(
                sprintf(
                    'Column %s added to table %s',
                    RdsStorage::EVENT_LOG_USER_LOGIN,
                    RdsStorage::EVENT_LOG_TABLE_NAME
                )
            )
        );

        $eventManager = $this->getEventManager();
        $eventManager->attach(TranslationActionEvent::class, [LoggerService::class, 'log']);
        $this->getServiceLocator()->register(EventManager::SERVICE_ID, $eventManager);

        $this->addReport(
            Report::createSuccess(
                sprintf(
                    'Event %s successfully attached',
                    TranslationActionEvent::class
                )
            )
        );
    }

    public function down(Schema $schema): void
    {
        $originalSchema = clone $schema;
        $table = $schema->getTable(RdsStorage::EVENT_LOG_TABLE_NAME);

        $table
            ->dropColumn(RdsStorage::EVENT_LOG_USER_LOGIN)
            ->dropIndex('idx_user_login');

        $this->migrate($originalSchema, $schema);

        $this->addReport(
            Report::createSuccess(
                sprintf(
                    'Column %s removed from table %s',
                    RdsStorage::EVENT_LOG_USER_LOGIN,
                    RdsStorage::EVENT_LOG_TABLE_NAME
                )
            )
        );

        $eventManager = $this->getEventManager();
        $eventManager->detach(TranslationActionEvent::class, [LoggerService::class, 'log']);
        $this->getServiceLocator()->register(EventManager::SERVICE_ID, $eventManager);

        $this->addReport(
            Report::createSuccess(
                sprintf(
                    'Event %s successfully detached',
                    TranslationActionEvent::class
                )
            )
        );
    }

    private function migrate(Schema $originalSchema, Schema $schema): void
    {
        $persistence = $this->getPersistence();
        $queries = $persistence->getPlatForm()->getMigrateSchemaSql($originalSchema, $schema);

        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }

    private function getPersistence(): common_persistence_Persistence
    {
        $persistenceManager = $this->getServiceLocator()->get(PersistenceManager::SERVICE_ID);

        return $persistenceManager->getPersistenceById('default');
    }

    private function getEventManager(): EventManager
    {
        return $this->getServiceLocator()->get(EventManager::SERVICE_ID);
    }
}
