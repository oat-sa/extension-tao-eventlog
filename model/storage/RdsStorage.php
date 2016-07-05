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

use common_Logger;
use common_persistence_Manager;
use common_persistence_Persistence;
use common_persistence_SqlPersistence;
use DateTimeImmutable;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use oat\oatbox\service\ConfigurableService;
use oat\taoEventLog\model\StorageInterface;

/**
 * Class RdsStorage
 * @package oat\taoEventLog\model\storage
 */
class RdsStorage extends ConfigurableService implements StorageInterface
{
    /**
     * Persistence for DB
     * @var common_persistence_Persistence
     */
    private $persistence;

    /**
     * @param string $eventName
     * @param string $currentAction
     * @param string $userIdentifier
     * @param string $userRoles
     * @param string $occurred
     * @param array $data
     * @return bool
     */
    public function log($eventName = '', $currentAction = '', $userIdentifier = '', $userRoles = '', $occurred = '', $data = [])
    {
        $result = $this->getPersistence()->insert(
            self::EVENT_LOG_TABLE_NAME, [
                self::EVENT_LOG_EVENT_NAME => $eventName,
                self::EVENT_LOG_ACTION => $currentAction,
                self::EVENT_LOG_USER_ID => $userIdentifier,
                self::EVENT_LOG_USER_ROLES => $userRoles,
                self::EVENT_LOG_OCCURRED => $occurred,
                self::EVENT_LOG_PROPERTIES => json_encode($data)
            ]
        );

        return $result === 1;
    }

    /**
     * @param DateTimeImmutable $beforeDate
     * @return mixed
     */
    public function removeOldLogEntries(DateTimeImmutable $beforeDate)
    {
        $sql = "DELETE FROM " . self::EVENT_LOG_TABLE_NAME . " WHERE " . self::EVENT_LOG_OCCURRED . " <= ?";

        return $this->getPersistence()->query($sql, [$beforeDate->format('Y-m-d H:i:s')]);
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
            $tableLog = $schema->createTable(self::EVENT_LOG_TABLE_NAME);
            $tableLog->addOption('engine', 'MyISAM');

            $tableLog->addColumn(self::EVENT_LOG_ID,          "integer",  ["notnull" => true, "autoincrement" => true, 'unsigned' => true]);
            $tableLog->addColumn(self::EVENT_LOG_EVENT_NAME,  "string",   ["notnull" => true, "length" => 255, 'comment' => 'Event name']);
            $tableLog->addColumn(self::EVENT_LOG_ACTION,      "string",   ["notnull" => true, "length" => 255, 'comment' => 'Current action']);
            $tableLog->addColumn(self::EVENT_LOG_USER_ID,     "string",   ["notnull" => false, "length" => 255, 'default' => '', 'comment' => 'User identifier']);
            $tableLog->addColumn(self::EVENT_LOG_USER_ROLES,  "string",   ["notnull" => true, "length" => 255, 'comment' => 'User roles']);
            $tableLog->addColumn(self::EVENT_LOG_OCCURRED,    "datetime", ["notnull" => true]);
            $tableLog->addColumn(self::EVENT_LOG_PROPERTIES,  "text",     ["notnull" => false, 'default' => '', 'comment' => 'Event properties in json']);

            $tableLog->setPrimaryKey(array(self::EVENT_LOG_ID));
            $tableLog->addIndex([self::EVENT_LOG_EVENT_NAME], 'idx_event_name');
            $tableLog->addIndex([self::EVENT_LOG_ACTION], 'idx_action');
            $tableLog->addIndex([self::EVENT_LOG_USER_ID], 'idx_user_id');
            $tableLog->addIndex([self::EVENT_LOG_USER_ROLES], 'idx_user_roles');
            $tableLog->addIndex([self::EVENT_LOG_OCCURRED], 'idx_occurred');
        } catch (SchemaException $e) {
            common_Logger::i('Database Schema for EventLog already up to date.');

            return false;
        }

        $queries = $persistence->getPlatForm()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }

        return self::EVENT_LOG_TABLE_NAME;
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
            $schema->dropTable(self::EVENT_LOG_TABLE_NAME);
        } catch (SchemaException $e) {
            \common_Logger::i('Database Schema for EventLog can\'t be dropped.');
        }

        $queries = $persistence->getPlatForm()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }

    /**
     * @param array $params
     * @return array
     */
    public function searchInstances(array $params = [])
    {
        $sql = 'SELECT * FROM ' . self::EVENT_LOG_TABLE_NAME;

        $parameters = [];

        if (isset($params['filterquery']) && isset($params['filtercolumns']) && count($params['filtercolumns']) 
                && in_array(current($params['filtercolumns']), self::tableColumns())) {
            
            $sql .= ' WHERE ' . current($params['filtercolumns']) . " LIKE ?";
            $parameters[] = '%' . $params['filterquery'] . '%';
        } elseif (isset($params['filterquery']) && !empty($params['filterquery'])) {
            $sql .= " WHERE "
                . self::EVENT_LOG_EVENT_NAME . " LIKE ? OR "
                . self::EVENT_LOG_ACTION . " LIKE ? OR "
                . self::EVENT_LOG_USER_ID . " LIKE ? OR "
                . self::EVENT_LOG_USER_ROLES . " LIKE ?"
            ;
            
            for ($i = 0; $i < 4; $i++) {
                $parameters[] = '%' . $params['filterquery'] . '%';
            }
        }

        $orderBy = isset($params['sortby']) ? $params['sortby'] : '';
        $orderDir = isset($params['sortorder']) ? strtoupper($params['sortorder']) : ' ASC';

        $sql .= ' ORDER BY ';
        $orderSep = '';

        if (in_array($orderBy, self::tableColumns()) && in_array($orderDir, ['ASC', 'DESC'])) {
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

    /**
     * Returns actual list of table columns with log data
     * @return array
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
     * @return common_persistence_SqlPersistence
     */
    private function getPersistence()
    {
        if (is_null($this->persistence)) {
            $this->persistence = common_persistence_Manager::getPersistence($this->getOption('persistence'));
        }

        return $this->persistence;
    }

}
