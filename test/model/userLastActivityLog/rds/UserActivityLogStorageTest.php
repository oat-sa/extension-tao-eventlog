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

namespace oat\taoEventLog\test\model\userLastActivityLog\rds;

use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoEventLog\model\userLastActivityLog\rds\UserLastActivityLogStorage as Storage;
use oat\oatbox\service\ServiceManager;

/**
 * Class UserLastActivityLogStorageTest
 * @package oat\taoEventLog\test\model\usreActivityLog\rds
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class UserLastActivityLogStorageTest extends TaoPhpUnitTestRunner
{

    protected $fixtures;
    protected $persistence;
    protected $service;

    public function testLog()
    {
        $user = new TestUser([
            'admin', 'proctor',
        ], 'http://sample/first.rdf#i00000000000000001_test_record');
        $details = ['testKey' => 'testVal'];
        $service = $this->getService();
        $service->log($user, 'testAction', $details);

        $stmt = $this->persistence->query(
            'select * from ' .Storage::TABLE_NAME . ' order by ' . Storage::COLUMN_EVENT_TIME .' DESC limit 1'
        );
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals($user->getIdentifier(), $data[Storage::COLUMN_USER_ID]);
        $this->assertEquals(',' . implode(',', $user->getRoles()) . ',', $data[Storage::COLUMN_USER_ROLES]);
        $this->assertEquals('testAction', $data[Storage::COLUMN_ACTION]);
        $this->assertEquals(json_encode($details), $data[Storage::COLUMN_DETAILS]);

        //make sure that previous action has been overwritten
        $service->log($user, 'testAction2', $details);

        $stmt = $this->persistence->query(
            'select * from ' . Storage::TABLE_NAME . ' WHERE ' . Storage::COLUMN_USER_ID .' = \''.$user->getIdentifier() .'\''
        );
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertEquals(1, count($data));
    }

    public function testFind()
    {
        $service = $this->getService();
        $iterator = $service->find([
            [Storage::USER_ID, 'like', '%_test_record']
        ], [
            'limit' => 1,
            'offset' => 1,
        ]);
        $data = $iterator->current();
        $iterator->next();
        $this->assertFalse($iterator->valid());
        $this->assertEquals($this->fixtures[1][Storage::COLUMN_EVENT_TIME], $data[Storage::COLUMN_EVENT_TIME]);


        $iterator = $service->find([
            [Storage::USER_ID, '=', 'http://sample/first.rdf#i00000000000000003_test_record']
        ]);
        $data = $iterator->current();
        $iterator->next();
        $this->assertFalse($iterator->valid());
        $this->assertEquals('http://sample/first.rdf#i00000000000000003_test_record', $data[Storage::COLUMN_USER_ID]);


        $iterator = $service->find([
            [Storage::USER_ID, 'like', '%_test_record'],
            [Storage::COLUMN_EVENT_TIME, 'between', 1490703795.3623, 1490703795.3624]
        ]);
        $this->assertEquals(1490703795.3623, $iterator->current()[Storage::COLUMN_EVENT_TIME]);
        $iterator->next();
        $this->assertEquals(1490703795.3624, $iterator->current()[Storage::COLUMN_EVENT_TIME]);
        $iterator->next();
        $this->assertFalse($iterator->valid());


        $iterator = $service->find([
            [Storage::USER_ID, 'like', '%_test_record'],
            [Storage::COLUMN_USER_ROLES, 'like', '%manager%'],
        ]);
        $this->assertEquals('http://sample/first.rdf#i00000000000000002_test_record', $iterator->current()[Storage::COLUMN_USER_ID]);
        $iterator->next();
        $this->assertFalse($iterator->valid());
    }


    public function testCount()
    {
        $service = $this->getService();

        $result = $service->count([
            [Storage::USER_ID, '=', 'http://sample/first.rdf#i00000000000000003_test_record']
        ]);
        $this->assertEquals(1, $result);


        $result = $service->count([
            [Storage::USER_ID, 'like', '%_test_record'],
            [Storage::COLUMN_EVENT_TIME, 'between', 1490703795.3623, 1490703795.3624],
        ]);
        $this->assertEquals(2, $result);

        $result = $service->count([
            [Storage::USER_ID, 'like', '%_test_record'],
            [Storage::COLUMN_EVENT_TIME, 'between', 1490703795.3622, 1490703795.3625],
            [Storage::COLUMN_USER_ROLES, 'like', '%admin%'],
        ]);
        $this->assertEquals(3, $result);

        $result = $service->count([
            [Storage::USER_ID, 'like', '%_test_record'],
        ], ['group' => Storage::COLUMN_ACTION]);
        $this->assertEquals(2, $result);


        $result = $service->count([
            [Storage::USER_ID, 'like', '%_test_record'],
            [Storage::COLUMN_USER_ROLES, 'like', '%manager%'],
        ]);
        $this->assertEquals(1, $result);


        $result = $service->count([
            [Storage::COLUMN_USER_ID, '=', 'unexistent'],
        ]);
        $this->assertEquals(0, $result);
    }

    /**
     * Load fixtures to table
     */
    protected function loadFixtures($persistence)
    {
        $query = 'INSERT INTO '.Storage::TABLE_NAME.' ('
            .Storage::COLUMN_USER_ID.', '.Storage::COLUMN_USER_ROLES.', '.Storage::COLUMN_ACTION.', '.Storage::COLUMN_EVENT_TIME.', '.Storage::COLUMN_DETAILS.') '
            .'VALUES  (?, ?, ?, ?, ?)';

        $this->fixtures = [
            [
                Storage::COLUMN_USER_ID => 'http://sample/first.rdf#i00000000000000001_test_record',
                Storage::COLUMN_USER_ROLES => 'testtaker',
                Storage::COLUMN_ACTION => 'http://package-tao/tao/Main/login',
                Storage::COLUMN_EVENT_TIME => 1490703795.3622,
                Storage::COLUMN_DETAILS => json_encode(['method' => 'GET', 'id' => 0]),
            ],
            [
                Storage::COLUMN_USER_ID => 'http://sample/first.rdf#i00000000000000002_test_record',
                Storage::COLUMN_USER_ROLES => 'admin,proctor,manager',
                Storage::COLUMN_ACTION => 'http://package-tao/tao/Main/index',
                Storage::COLUMN_EVENT_TIME => 1490703795.3623,
                Storage::COLUMN_DETAILS => json_encode(['method' => 'GET', 'id' => 1]),
            ],
            [
                Storage::COLUMN_USER_ID => 'http://sample/first.rdf#i00000000000000003_test_record',
                Storage::COLUMN_USER_ROLES => 'admin,proctor',
                Storage::COLUMN_ACTION => 'http://package-tao/tao/Main/login',
                Storage::COLUMN_EVENT_TIME => 1490703795.3624,
                Storage::COLUMN_DETAILS => json_encode(['method' => 'GET', 'id' => 2]),
            ],
            [
                Storage::COLUMN_USER_ID => 'http://sample/first.rdf#i00000000000000004_test_record',
                Storage::COLUMN_USER_ROLES => 'admin,proctor',
                Storage::COLUMN_ACTION => 'http://package-tao/tao/Main/index',
                Storage::COLUMN_EVENT_TIME => 1490703795.3625,
                Storage::COLUMN_DETAILS => json_encode(['method' => 'GET', 'id' => 3]),
            ],
        ];

        foreach ($this->fixtures as $fixture) {
            $persistence->exec($query, array(
                $fixture[Storage::COLUMN_USER_ID],
                $fixture[Storage::COLUMN_USER_ROLES],
                $fixture[Storage::COLUMN_ACTION],
                $fixture[Storage::COLUMN_EVENT_TIME],
                $fixture[Storage::COLUMN_DETAILS],
            ));
        }
    }

    /**
     * @throws
     * @return Storage
     */
    protected function getService()
    {
        if ($this->service === null) {
            $persistenceManager = $this->getSqlMock('test_user_activity_log');
            $this->persistence = $persistenceManager->getPersistenceById('test_user_activity_log');
            Storage::install($this->persistence);
            $this->service = new Storage([
                Storage::OPTION_PERSISTENCE => 'test_user_activity_log'
            ]);
            $config = new \common_persistence_KeyValuePersistence([], new \common_persistence_InMemoryKvDriver());
            $config->set(\common_persistence_Manager::SERVICE_ID, $persistenceManager);
            $serviceManager = new ServiceManager($config);
            $this->service->setServiceManager($serviceManager);
            $this->loadFixtures($this->persistence);
        }
        return $this->service;
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