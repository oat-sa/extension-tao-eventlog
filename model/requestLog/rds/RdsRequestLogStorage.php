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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoEventLog\model\requestLog\rds;

use DateTime;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManager;
use oat\taoEventLog\model\requestLog\RequestLogStorage;
use GuzzleHttp\Psr7\Request;
use oat\oatbox\user\User;
use Doctrine\DBAL\Schema\SchemaException;

/**
 * Class RdsRequestLogStorage
 * @package oat\taoEventLog\model\requestLog\rds
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RdsRequestLogStorage extends ConfigurableService implements RequestLogStorage
{
    const OPTION_PERSISTENCE = 'persistence_id';
    const TABLE_NAME = 'request_log';

    const COLUMN_USER_ID = self::USER_ID;
    const COLUMN_USER_ROLES = self::USER_ROLES;
    const COLUMN_ACTION = self::ACTION;
    const COLUMN_EVENT_TIME = self::EVENT_TIME;
    const COLUMN_DETAILS = self::DETAILS;

    /**
     * @inheritdoc
     */
    public function log(Request $request = null, User $user = null)
    {
        // TODO: Implement logCurrentSession() method.
    }

    /**
     *
     * @param array $filters
     * @param DateTime|null $since
     * @param DateTime|null $until
     * @return RdsRequestLogIterator
     */
    public function find(array $filters = [], DateTime $since = null, DateTime $until = null)
    {
        // TODO: build filters here
        return new RdsRequestLogIterator($this->getPersistence(), $this->getQueryBuilder());
    }

    /**
     * @return \common_persistence_SqlPersistence
     */
    protected function getPersistence()
    {
        $persistenceManager = $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID);
        return $persistenceManager->getPersistenceById($this->getOption(self::OPTION_PERSISTENCE));
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function getQueryBuilder()
    {
        if ($this->connection === null) {
            $this->connection = \Doctrine\DBAL\DriverManager::getConnection(
                $this->getPersistence()->getDriver()->getParams(),
                new \Doctrine\DBAL\Configuration()
            );
        }

        return $this->connection->createQueryBuilder()->select('*')->from(RdsStorage::TABLE_NAME, 'r');
    }

    /**
     * Initialize RDS Request log storage and register serrvice.
     *
     * @param string $persistenceId
     * @return \common_report_Report
     */
    static function install($persistenceId = 'default')
    {
        $persistence = \common_persistence_Manager::getPersistence($persistenceId);

        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $table = $schema->createTable(self::TABLE_NAME);
            $table->addOption('engine', 'InnoDB');
            $table->addColumn(static::COLUMN_USER_ID, "string", ["length" => 255]);
            $table->addColumn(static::COLUMN_USER_ROLES, "string", ["notnull" => true]);
            $table->addColumn(static::COLUMN_ACTION, "string", ["notnull" => false, "length" => 4096]);
            $table->addColumn(static::COLUMN_EVENT_TIME, "string", array("notnull" => true, "length" => 255));
            $table->addColumn(static::COLUMN_DETAILS, "text", ["notnull" => false]);
            $table->addIndex([static::COLUMN_USER_ID], 'IDX_' . static::TABLE_NAME . '_' . static::COLUMN_USER_ID);
            $table->addIndex([static::COLUMN_EVENT_TIME], 'IDX_' . static::TABLE_NAME . '_' . static::COLUMN_EVENT_TIME);
        } catch(SchemaException $e) {
            \common_Logger::i('Database Schema already up to date.');
        }

        $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }

        ServiceManager::getServiceManager()->register(
            self::SERVICE_ID,
            new self([self::OPTION_PERSISTENCE => $persistenceId])
        );
        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('User activity log successfully registered.'));
    }
}