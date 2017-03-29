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
use oat\taoEventLog\model\requestLog\rds\RdsRequestLogStorage as RdsStorage;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\service\ServiceNotFoundException;
use GuzzleHttp\Psr7\Request;

/**
 * Class RdsRequestLogStorageTest
 * @package oat\taoEventLog\test\model\requestLog\rds
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RdsRequestLogStorageTest extends TaoPhpUnitTestRunner
{

    protected $fixtures;

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

    public function testLog()
    {
        $request = new Request(
            'GET',
            '/taoDeliveryRdf/RestDelivery/generate'
        );
        $user = new TestUser([
            'admin', 'proctor',
        ], 'http://sample/first.rdf#i00000000000000001_test_record');
        $service = $this->getService();
        $service->log($request, $user);

        $stmt = $this->getPersistence()->query(
            'select * from ' .RdsStorage::TABLE_NAME . ' order by ' . RdsStorage::COLUMN_EVENT_TIME .' DESC limit 1'
        );
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals($user->getIdentifier(), $data[RdsStorage::COLUMN_USER_ID]);
        $this->assertEquals(',' . implode(',', $user->getRoles()) . ',', $data[RdsStorage::COLUMN_USER_ROLES]);
        $this->assertEquals('/taoDeliveryRdf/RestDelivery/generate', $data[RdsStorage::COLUMN_ACTION]);
    }

    public function testFind()
    {
        $this->loadFixture();
        $service = $this->getService();
        $iterator = $service->find([
            [RdsStorage::USER_ID, 'like', '%_test_record']
        ], [
            'limit' => 1,
            'offset' => 1,
        ]);
        $data = $iterator->current();
        $iterator->next();
        $this->assertFalse($iterator->valid());
        $this->assertEquals($this->fixtures[1][RdsStorage::COLUMN_EVENT_TIME], $data[RdsStorage::COLUMN_EVENT_TIME]);


        $iterator = $service->find([
            [RdsStorage::USER_ID, '=', 'http://sample/first.rdf#i00000000000000003_test_record']
        ]);
        $data = $iterator->current();
        $iterator->next();
        $this->assertFalse($iterator->valid());
        $this->assertEquals('http://sample/first.rdf#i00000000000000003_test_record', $data[RdsStorage::COLUMN_USER_ID]);


        $iterator = $service->find([
            [RdsStorage::USER_ID, 'like', '%_test_record'],
            [RdsStorage::COLUMN_EVENT_TIME, 'between', 1490703795.3623, 1490703795.3624]
        ]);
        $this->assertEquals(1490703795.3623, $iterator->current()[RdsStorage::COLUMN_EVENT_TIME]);
        $iterator->next();
        $this->assertEquals(1490703795.3624, $iterator->current()[RdsStorage::COLUMN_EVENT_TIME]);
        $iterator->next();
        $this->assertFalse($iterator->valid());


        $iterator = $service->find([
            [RdsStorage::USER_ID, 'like', '%_test_record'],
            [RdsStorage::COLUMN_USER_ROLES, 'like', '%manager%'],
        ]);
        $this->assertEquals('http://sample/first.rdf#i00000000000000002_test_record', $iterator->current()[RdsStorage::COLUMN_USER_ID]);
        $iterator->next();
        $this->assertFalse($iterator->valid());
    }


    public function testCount()
    {
        $this->loadFixture();
        $service = $this->getService();

        $result = $service->count([
            [RdsStorage::USER_ID, '=', 'http://sample/first.rdf#i00000000000000003_test_record']
        ]);
        $this->assertEquals(1, $result);


        $result = $service->count([
            [RdsStorage::USER_ID, 'like', '%_test_record'],
            [RdsStorage::COLUMN_EVENT_TIME, 'between', 1490703795.3623, 1490703795.3624],
        ]);
        $this->assertEquals(2, $result);

        $result = $service->count([
            [RdsStorage::USER_ID, 'like', '%_test_record'],
            [RdsStorage::COLUMN_EVENT_TIME, 'between', 1490703795.3622, 1490703795.3625],
            [RdsStorage::COLUMN_USER_ROLES, 'like', '%admin%'],
        ]);
        $this->assertEquals(3, $result);

        $result = $service->count([
            [RdsStorage::USER_ID, 'like', '%_test_record'],
        ], ['group' => RdsStorage::COLUMN_ACTION]);
        $this->assertEquals(2, $result);


        $result = $service->count([
            [RdsStorage::USER_ID, 'like', '%_test_record'],
            [RdsStorage::COLUMN_USER_ROLES, 'like', '%manager%'],
        ]);
        $this->assertEquals(1, $result);


        $result = $service->count([
            [RdsStorage::COLUMN_USER_ID, '=', 'unexistent'],
        ]);
        $this->assertEquals(0, $result);
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
                RdsStorage::COLUMN_USER_ROLES => 'testtaker',
                RdsStorage::COLUMN_ACTION => 'http://package-tao/tao/Main/login',
                RdsStorage::COLUMN_EVENT_TIME => 1490703795.3622,
                RdsStorage::COLUMN_DETAILS => json_encode(['method' => 'GET', 'id' => 0]),
            ],
            [
                RdsStorage::COLUMN_USER_ID => 'http://sample/first.rdf#i00000000000000002_test_record',
                RdsStorage::COLUMN_USER_ROLES => 'admin,proctor,manager',
                RdsStorage::COLUMN_ACTION => 'http://package-tao/tao/Main/index',
                RdsStorage::COLUMN_EVENT_TIME => 1490703795.3623,
                RdsStorage::COLUMN_DETAILS => json_encode(['method' => 'GET', 'id' => 1]),
            ],
            [
                RdsStorage::COLUMN_USER_ID => 'http://sample/first.rdf#i00000000000000003_test_record',
                RdsStorage::COLUMN_USER_ROLES => 'admin,proctor',
                RdsStorage::COLUMN_ACTION => 'http://package-tao/tao/Main/login',
                RdsStorage::COLUMN_EVENT_TIME => 1490703795.3624,
                RdsStorage::COLUMN_DETAILS => json_encode(['method' => 'GET', 'id' => 2]),
            ],
            [
                RdsStorage::COLUMN_USER_ID => 'http://sample/first.rdf#i00000000000000004_test_record',
                RdsStorage::COLUMN_USER_ROLES => 'admin,proctor',
                RdsStorage::COLUMN_ACTION => 'http://package-tao/tao/Main/index',
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
     * @return RdsStorage
     */
    protected function getService()
    {
        $serviceManager = ServiceManager::getServiceManager();
        $service = new RdsStorage([
            RdsStorage::OPTION_PERSISTENCE => $this->getPersistenceId()
        ]);
        $service->setServiceManager($serviceManager);

        return $service;
    }

    /**
     * @return string
     */
    protected function getPersistenceId()
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
        return $persistenceId;
    }

    /**
     * @return \common_persistence_SqlPersistence
     */
    protected function getPersistence()
    {
        $serviceManager = ServiceManager::getServiceManager();
        $persistenceManager = $serviceManager->get(\common_persistence_Manager::SERVICE_ID);
        return $persistenceManager->getPersistenceById($this->getPersistenceId());
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
}

class TestUser extends \common_test_TestUser
{
    protected $roles;
    protected $id;

    public function __construct(array $roles, $id)
    {
        $this->roles = $roles;
        $this->id = $id;
    }

    public function getIdentifier()
    {
        return $this->id;
    }

    public function getRoles()
    {
        return $this->roles;
    }
}