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
 * @author Alexander Zagovorichev <zagovorichev@1pt.com>
 */

namespace oat\taoEventLog\model\storage;

use common_persistence_Manager;
use common_persistence_Persistence;
use common_persistence_SqlPersistence;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use oat\taoEventLog\model\StorageInterface;

/**
 * Class RdsStorage
 * @package oat\taoEventLog\model\storage
 */
class RdsStorage implements StorageInterface
{
    const TABLE_NAME = 'event_log';
    const OPTION_PERSISTENCE = 'persistence';

    /**
     * Persistence for DB
     * @var common_persistence_Persistence
     */
    private $persistence;

    /**
     * RdsStorage constructor.
     * @param string $persistence
     */
    public function __construct($persistence = '')
    {
        $this->persistence = $persistence;
    }

    /**
     * @param string $eventName
     * @param string $currentAction
     * @param string $userIdentifier
     * @param string $userRole
     * @param string $occurred
     * @param array $data
     * @return bool
     */
    public function log($eventName = '', $currentAction = '', $userIdentifier = '', $userRole = '', $occurred = '', $data = [])
    {
        $result = $this->getPersistence()->insert(
            self::TABLE_NAME, [
                self::EVENT_NAME => $eventName,
                self::ACTION => $currentAction,
                self::USER_ID => $userIdentifier,
                self::USER_ROLE => $userRole,
                self::OCCURRED => $occurred,
                self::PROPERTIES => json_encode($data)
            ]
        );

        $id = $this->getPersistence()->lastInsertId(self::TABLE_NAME);

        // todo clean data older than 90 days
        if ($id % 1000) {
            //every 1000 inserts try to delete obsolete data from log
            $this->cleanStorage();
        }

        return $result === 1;
    }

    /**
     * @return common_persistence_SqlPersistence
     */
    private function getPersistence()
    {
        return common_persistence_Manager::getPersistence($this->persistence);
    }

    /**
     * @inheritdoc
     */
    public function createStorage()
    {
        /** @var common_persistence_SqlPersistence $persistence */
        $persistence = $this->getPersistence();
        /** @var AbstractSchemaManager $schemaManager */
        $schemaManager = $persistence->getDriver()->getSchemaManager();

        /** @var Schema $schema */
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $tableLog = $schema->createTable(self::TABLE_NAME);
            $tableLog->addOption('engine', 'MyISAM');

            $tableLog->addColumn(self::ID,          "integer",  ["notnull" => true, "autoincrement" => true, 'unsigned' => true]);
            $tableLog->addColumn(self::EVENT_NAME,  "string",   ["notnull" => true, "length" => 255, 'comment' => 'Event name']);
            $tableLog->addColumn(self::ACTION,      "string",   ["notnull" => true, "length" => 255, 'comment' => 'Current action']);
            $tableLog->addColumn(self::USER_ID,     "string",   ["notnull" => true, "length" => 255, 'comment' => 'User identifier']);
            $tableLog->addColumn(self::USER_ROLE,   "string",   ["notnull" => true, "length" => 255, 'comment' => 'User role']);
            $tableLog->addColumn(self::OCCURRED,    "datetime", ["notnull" => true]);
            $tableLog->addColumn(self::PROPERTIES,  "text",     ["notnull" => true, 'comment' => 'Event properties in json']);

            $tableLog->setPrimaryKey(array(self::ID));
            $tableLog->addIndex([self::EVENT_NAME], 'idx_event_name');
            $tableLog->addIndex([self::USER_ID], 'idx_user_id');
            $tableLog->addIndex([self::USER_ID], 'idx_user_role');
            $tableLog->addIndex([self::OCCURRED], 'idx_occurred');
        } catch (SchemaException $e) {
            \common_Logger::i('Database Schema for EventLog already up to date.');
            return false;
        }

        $queries = $persistence->getPlatForm()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }

        return self::TABLE_NAME;
    }

    /**
     * @inheritdoc
     */
    public function dropStorage()
    {
        /** @var common_persistence_SqlPersistence $persistence */
        $persistence = $this->getPersistence();
        /** @var AbstractSchemaManager $schemaManager */
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $schema->dropTable(self::TABLE_NAME);
        } catch (SchemaException $e) {
            \common_Logger::i('Database Schema for EventLog can\'t be dropped.');
        }

        $queries = $persistence->getPlatForm()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }

    // todo: move to config period for keeping log data
    private function cleanStorage($dateRange = '-90 days')
    {
        $sql = "DELETE FROM " . self::TABLE_NAME . " WHERE " . self::OCCURRED . " <= ?";

        $parameters = [date('Y-m-d H:i:s', strtotime($dateRange))];
        $this->getPersistence()->query($sql, $parameters);

        return true;
    }

}
