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

namespace oat\taoEventLog\test\model\requestLog\rds;

use oat\tao\test\TaoPhpUnitTestRunner;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\service\ServiceNotFoundException;
use oat\taoEventLog\model\requestLog\rds\RdsRequestLogStorage as RdsStorage;
use oat\taoEventLog\model\requestLog\rds\RdsRequestLogIterator;

/**
 * Class RdsRequestLogIteratorTest
 * @package oat\taoEventLog\test\model\requestLog\rds
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RdsRequestLogIteratorTest extends TaoPhpUnitTestRunner
{

    /**
     * @var array
     */
    protected $fixtures = [];

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     * Check whether rds request log is installed
     */
    protected function setUp()
    {
        $persistence = $this->getPersistence();
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        if (!$schema->hasTable(RdsStorage::TABLE_NAME)) {
            $this->markTestSkipped(
                'RdsRequestLogStorage table is not exist.'
            );
        }
        $this->deleteTestData();
    }

    /**
     * Delete fixtures
     */
    public function tearDown()
    {
        $this->deleteTestData();
    }

    public function testCurrent()
    {
        $this->loadFixture();

        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->setMaxResults(3);

        $iterator = new RdsRequestLogIterator($this->getPersistence(), $queryBuilder);
        $current = $iterator->current();
        $this->assertEquals($this->fixtures[0], $current);

        $iterator->next();
        $current = $iterator->current();
        $this->assertEquals($this->fixtures[1], $current);

        $iterator->next();
        $current = $iterator->current();
        $this->assertEquals($this->fixtures[2], $current);

        $iterator->next();
        $current = $iterator->current();
        $this->assertEquals(null, $current);
    }

    public function testNext()
    {
        $this->loadFixture();

        $queryBuilder = $this->getQueryBuilder();

        $iterator = new RdsRequestLogIterator($this->getPersistence(), $queryBuilder);
        $this->assertEquals(0, $iterator->key());
        $iterator->next();
        $this->assertEquals(1, $iterator->key());


        //test in loop
        $queryBuilder = $this->getQueryBuilder();
        $iterator = new RdsRequestLogIterator($this->getPersistence(), $queryBuilder);
        foreach ($iterator as $key=>$value) {
            $this->assertEquals($this->fixtures[$key], $value);
        }
    }

    public function testKey()
    {
        $this->loadFixture();

        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder->setFirstResult(1);

        $iterator = new RdsRequestLogIterator($this->getPersistence(), $queryBuilder);
        $this->assertEquals(1, $iterator->key());
        $iterator->next();
        $this->assertEquals(2, $iterator->key());
        $iterator->next();
        $this->assertEquals(3, $iterator->key());
    }

    public function testValid()
    {
        $this->loadFixture();

        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->setMaxResults(2);
        $iterator = new RdsRequestLogIterator($this->getPersistence(), $queryBuilder);
        $this->assertTrue($iterator->valid());
        $iterator->next();
        $this->assertFalse($iterator->valid());

        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->setMaxResults(1);
        $iterator = new RdsRequestLogIterator($this->getPersistence(), $queryBuilder);
        $this->assertFalse($iterator->valid());
    }

    public function testRewind()
    {
        $this->loadFixture();

        $queryBuilder = $this->getQueryBuilder();
        $iterator = new RdsRequestLogIterator($this->getPersistence(), $queryBuilder);
        $iterator->next();
        $this->assertEquals(1, $iterator->key());
        $iterator->rewind();
        $this->assertEquals(0, $iterator->key());
        $current = $iterator->current();
        $this->assertEquals($this->fixtures[0][RdsStorage::COLUMN_USER_ID], $current[RdsStorage::COLUMN_USER_ID]);
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

        return $this->connection->createQueryBuilder()->select('*')
            ->from(RdsStorage::TABLE_NAME, 'r')
            ->where(RdsStorage::USER_ID.' like ?')->setParameters(['%_test_record']);
    }

    /**
     * Load fixtures to table
     */
    protected function loadFixture()
    {
        $query = 'INSERT INTO '.RdsStorage::TABLE_NAME.' ('
            .RdsStorage::COLUMN_USER_ID.', '.RdsStorage::COLUMN_USER_ROLES.', '.RdsStorage::COLUMN_ACTION.', '.RdsStorage::COLUMN_EVENT_TIME.', '.RdsStorage::COLUMN_DETAILS.') '
            .'VALUES  (?, ?, ?, ?, ?)';

        $this->fixtures = [
            [
                RdsStorage::COLUMN_USER_ID => 'http://sample/first.rdf#i00000000000000001_test_record',
                RdsStorage::COLUMN_USER_ROLES => 'admin,proctor',
                RdsStorage::COLUMN_ACTION => 'http://package-tao/tao/Main/login',
                RdsStorage::COLUMN_EVENT_TIME => 1490703795.3622,
                RdsStorage::COLUMN_DETAILS => json_encode(['method' => 'GET', 'id' => 0]),
            ],
            [
                RdsStorage::COLUMN_USER_ID => 'http://sample/first.rdf#i00000000000000001_test_record',
                RdsStorage::COLUMN_USER_ROLES => 'admin,proctor',
                RdsStorage::COLUMN_ACTION => 'http://package-tao/tao/Main/index?structure=items&ext=taoItems&section=manage_items',
                RdsStorage::COLUMN_EVENT_TIME => 1490703795.3623,
                RdsStorage::COLUMN_DETAILS => json_encode(['method' => 'GET', 'id' => 1]),
            ],
            [
                RdsStorage::COLUMN_USER_ID => 'http://sample/first.rdf#i00000000000000002_test_record',
                RdsStorage::COLUMN_USER_ROLES => 'admin,proctor',
                RdsStorage::COLUMN_ACTION => 'http://package-tao/tao/Main/login',
                RdsStorage::COLUMN_EVENT_TIME => 1490703795.3624,
                RdsStorage::COLUMN_DETAILS => json_encode(['method' => 'GET', 'id' => 2]),
            ],
            [
                RdsStorage::COLUMN_USER_ID => 'http://sample/first.rdf#i00000000000000002_test_record',
                RdsStorage::COLUMN_USER_ROLES => 'admin,proctor',
                RdsStorage::COLUMN_ACTION => 'http://package-tao/tao/Main/index?structure=items&ext=taoItems&section=manage_items',
                RdsStorage::COLUMN_EVENT_TIME => 1490703795.3625,
                RdsStorage::COLUMN_DETAILS => json_encode(['method' => 'GET', 'id' => 3]),
            ],
        ];

        $persistence = $this->getPersistence();
        foreach ($this->fixtures as $fixture) {
            $persistence->exec($query, array(
                $fixture[RdsStorage::COLUMN_USER_ID],
                $fixture[RdsStorage::COLUMN_USER_ROLES],
                $fixture[RdsStorage::COLUMN_ACTION],
                $fixture[RdsStorage::COLUMN_EVENT_TIME],
                $fixture[RdsStorage::COLUMN_DETAILS],
            ));
        }
    }

    /**
     * Clear test data before and after each test method
     * @after
     * @before
     */
    protected function deleteTestData()
    {
        $sql = 'DELETE FROM ' . RdsStorage::TABLE_NAME .
            ' WHERE ' . RdsStorage::COLUMN_USER_ID . " LIKE '%_test_record'";

        $this->getPersistence()->exec($sql);
    }

    /**
     * @return \common_persistence_SqlPersistence
     */
    protected function getPersistence()
    {
        $serviceManager = ServiceManager::getServiceManager();
        try {
            $service = $serviceManager->get(RdsStorage::SERVICE_ID);
            if ($service instanceof RdsStorage) {
                $persistenceId = $service->getOption(RdsStorage::OPTION_PERSISTENCE);
            } else {
                $persistenceId = 'default';
            }
        } catch (ServiceNotFoundException $e) {
            $persistenceId = 'default';
        }

        $persistenceManager = $serviceManager->get(\common_persistence_Manager::SERVICE_ID);
        return $persistenceManager->getPersistenceById($persistenceId);
    }
}