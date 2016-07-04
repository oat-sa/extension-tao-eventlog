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

    private function cleanStorage($dateRange = '-90 days')
    {
        $sql = "DELETE FROM " . self::TABLE_NAME . " WHERE " . self::OCCURRED . " <= ?";

        $parameters = [date('Y-m-d H:i:s', strtotime($dateRange))];
        $this->getPersistence()->query($sql, $parameters);

        return true;
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
            $tableLog->addColumn(self::USER_ID,     "string",   ["notnull" => false, "length" => 255, 'default' => '', 'comment' => 'User identifier']);
            $tableLog->addColumn(self::USER_ROLE,   "string",   ["notnull" => true, "length" => 255, 'comment' => 'User role']);
            $tableLog->addColumn(self::OCCURRED,    "datetime", ["notnull" => true]);
            $tableLog->addColumn(self::PROPERTIES,  "text",     ["notnull" => true, 'comment' => 'Event properties in json']);

            $tableLog->setPrimaryKey(array(self::ID));
            $tableLog->addIndex([self::EVENT_NAME], 'idx_event_name');
            $tableLog->addIndex([self::ACTION], 'idx_action');
            $tableLog->addIndex([self::USER_ID], 'idx_user_id');
            $tableLog->addIndex([self::USER_ROLE], 'idx_user_role');
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

    // todo: move to config period for keeping log data

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

    public function searchInstances(array $params = [])
    {
        $sql = 'SELECT * FROM ' . self::TABLE_NAME;

        $parameters = [];

        if (isset($params['filterquery']) && isset($params['filtercolumns']) && count($params['filtercolumns']) 
                && in_array(current($params['filtercolumns']), $this->tableColumns())) {
            
            $sql .= ' WHERE ' . current($params['filtercolumns']) . " LIKE ?";
            $parameters[] = '%' . $params['filterquery'] . '%';
        } elseif (isset($params['filterquery']) && !empty($params['filterquery'])) {
            $sql .= " WHERE "
                . self::EVENT_NAME . " LIKE ? OR "
                . self::ACTION . " LIKE ? OR "
                . self::USER_ID . " LIKE ? OR "
                . self::USER_ROLE . " LIKE ?"
            ;
            
            for ($i = 0; $i < 4; $i++) {
                $parameters[] = '%' . $params['filterquery'] . '%';
            }
        }

        $orderBy = isset($params['sortby']) ? $params['sortby'] : '';
        $orderDir = isset($params['sortorder']) ? strtoupper($params['sortorder']) : ' ASC';

        $sql .= ' ORDER BY ';
        $orderSep = '';

        if (in_array($orderBy, $this->tableColumns()) && in_array($orderDir, ['ASC', 'DESC'])) {
            $sql .= $orderBy . ' ' . $orderDir;
            $orderSep = ', ';
        }

        if ($orderBy != 'id') {
            $sql .= $orderSep . 'id DESC';
        }
        
        $page = isset($params['page']) ? (intval($params['page']) - 1) : 0;
        $rows = isset($params['rows']) ? intval($params['rows']) : 25;

        if ($page < 0) {
            $page = 0;
        }

        $sql .= ' LIMIT ? OFFSET ?';
        $parameters[] = $rows;
        $parameters[] = $page * $rows;
        
        $stmt = $this->getPersistence()->query($sql, $parameters);
        
        $ret = [];
        $ret['data'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $countSql = str_replace('SELECT *', 'SELECT COUNT(id)', $sql);
        $countSql = mb_strcut($countSql, 0, mb_strpos($countSql, 'ORDER BY'));
        $parameters = array_slice($parameters, 0, count($parameters)-2);
        
        $stmt = $this->getPersistence()->query($countSql, $parameters);
        $total = current($stmt->fetchAll(\PDO::FETCH_ASSOC));
        $ret['records'] = array_shift($total);
        
        return $ret;
    }

    public function tableColumns()
    {
        return [
            self::ID,
            self::USER_ID,
            self::USER_ROLE,
            self::EVENT_NAME,
            self::ACTION,
            self::OCCURRED,
            self::PROPERTIES
        ];
    }

}
