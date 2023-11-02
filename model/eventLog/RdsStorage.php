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

namespace oat\taoEventLog\model\eventLog;

use oat\taoEventLog\model\LogEntity;
use Doctrine\DBAL\Schema\SchemaException;
use oat\taoEventLog\model\storage\AbstractRdsStorage;
use Throwable;

/**
 * Class RdsStorage
 * @package oat\taoEventLog\model\storage
 */
class RdsStorage extends AbstractRdsStorage
{
    public const EVENT_LOG_TABLE_NAME = 'event_log';

    public const SERVICE_ID = 'taoEventLog/eventLogStorage';

    public const OPTION_INSERT_CHUNK_SIZE = 'insertChunkSize';

    public const EVENT_LOG_ID = self::ID;
    public const EVENT_LOG_EVENT_NAME = 'event_name';
    public const EVENT_LOG_ACTION = 'action';
    public const EVENT_LOG_USER_ID = 'user_id';
    public const EVENT_LOG_USER_ROLES = 'user_roles';
    public const EVENT_LOG_OCCURRED = 'occurred';
    public const EVENT_LOG_PROPERTIES = 'properties';

    private const DEFAULT_INSERT_CHUNK_SIZE = 100;

    /**
     * @return string
     */
    public function getTableName()
    {
        return self::EVENT_LOG_TABLE_NAME;
    }

    /**
     * @param LogEntity $logEntity
     * @return bool
     */
    public function log(LogEntity $logEntity)
    {
        $result = $this->getPersistence()->insert(
            $this->getTableName(),
            [
                self::EVENT_LOG_EVENT_NAME => $logEntity->getEvent()->getName(),
                self::EVENT_LOG_ACTION => $logEntity->getAction(),
                self::EVENT_LOG_USER_ID => $logEntity->getUser()->getIdentifier(),
                self::EVENT_LOG_USER_ROLES => join(',', $logEntity->getUser()->getRoles()),
                self::EVENT_LOG_OCCURRED => $logEntity->getTime()->format(self::DATE_TIME_FORMAT),
                self::EVENT_LOG_PROPERTIES => json_encode($logEntity->getData()),
            ]
        );

        return $result === 1;
    }

    public function logMultiple(LogEntity ...$logEntities): bool
    {
        $inserts = array_map(
            static fn (LogEntity $logEntity): array => [
                self::EVENT_LOG_EVENT_NAME => $logEntity->getEvent()->getName(),
                self::EVENT_LOG_ACTION => $logEntity->getAction(),
                self::EVENT_LOG_USER_ID => $logEntity->getUser()->getIdentifier(),
                self::EVENT_LOG_USER_ROLES => implode(',', $logEntity->getUser()->getRoles()),
                self::EVENT_LOG_OCCURRED => $logEntity->getTime()->format(self::DATE_TIME_FORMAT),
                self::EVENT_LOG_PROPERTIES => json_encode($logEntity->getData()),
            ],
            $logEntities
        );

        try {
            $persistence = $this->getPersistence();

            $persistence->transactional(function () use ($inserts, $persistence) {
                $insertCount = count($inserts);
                $insertChunkSize = $this->getInsertChunkSize();

                foreach (array_chunk($inserts, $insertChunkSize) as $index => $chunk) {
                    $this->logDebug(
                        sprintf(
                            'Processing chunk %d/%d with %d log entries',
                            $index + 1,
                            ceil($insertCount / $insertChunkSize),
                            count($chunk)
                        )
                    );

                    $persistence->insertMultiple($this->getTableName(), $chunk);
                }
            });

            return true;
        } catch (Throwable $exception) {
            $this->logError('Error when inserting log entries: ' . $exception->getMessage());

            return false;
        }
    }

    /**
     * @param array $params
     * @deprecated use $this->search() instead
     * @return array
     */
    public function searchInstances(array $params = [])
    {
        return $this->search($params);
    }

    /**
     * @inheritdoc
     */
    public static function tableColumns()
    {
        return [
            self::EVENT_LOG_ID,
            self::EVENT_LOG_USER_ID,
            self::EVENT_LOG_USER_ROLES,
            self::EVENT_LOG_EVENT_NAME,
            self::EVENT_LOG_ACTION,
            self::EVENT_LOG_OCCURRED,
            self::EVENT_LOG_PROPERTIES
        ];
    }

    /**
     * @inheritdoc
     */
    public static function install($persistence)
    {
        /** @var AbstractSchemaManager $schemaManager */
        $schemaManager = $persistence->getDriver()->getSchemaManager();

        /** @var Schema $schema */
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $table = $schema->createTable(self::EVENT_LOG_TABLE_NAME);
            $table->addOption('engine', 'MyISAM');

            $table->addColumn(
                self::EVENT_LOG_ID,
                "integer",
                ["notnull" => true, "autoincrement" => true, 'unsigned' => true]
            );
            $table->addColumn(
                self::EVENT_LOG_EVENT_NAME,
                "string",
                ["notnull" => true, "length" => 255, 'comment' => 'Event name']
            );
            $table->addColumn(
                self::EVENT_LOG_ACTION,
                "string",
                ["notnull" => false, "length" => 1000, 'comment' => 'Current action']
            );
            $table->addColumn(
                self::EVENT_LOG_USER_ID,
                "string",
                ["notnull" => false, "length" => 255, 'default' => '', 'comment' => 'User identifier']
            );
            $table->addColumn(
                self::EVENT_LOG_USER_ROLES,
                "text",
                ["notnull" => true, 'default' => '', 'comment' => 'User roles']
            );
            $table->addColumn(self::EVENT_LOG_OCCURRED, "datetime", ["notnull" => true]);
            $table->addColumn(
                self::EVENT_LOG_PROPERTIES,
                "text",
                ["notnull" => false, 'default' => '', 'comment' => 'Event properties in json']
            );

            $table->setPrimaryKey([self::EVENT_LOG_ID]);
            $table->addIndex([self::EVENT_LOG_EVENT_NAME], 'idx_event_name');
            $table->addIndex([self::EVENT_LOG_ACTION], 'idx_action', [], ['lengths' => [164]]);
            $table->addIndex([self::EVENT_LOG_USER_ID], 'idx_user_id');
            $table->addIndex([self::EVENT_LOG_OCCURRED], 'idx_occurred');
        } catch (SchemaException $e) {
            \common_Logger::i('Database Schema for EventLog already up to date.');
        }

        $queries = $persistence->getPlatForm()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }

    private function getInsertChunkSize(): int
    {
        return $this->getOption(self::OPTION_INSERT_CHUNK_SIZE, self::DEFAULT_INSERT_CHUNK_SIZE);
    }
}
