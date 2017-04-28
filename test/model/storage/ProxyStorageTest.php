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
use oat\taoEventLog\model\storage\ProxyStorage;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\event\Event;
use oat\oatbox\user\User;
use oat\dtms\DateTime;
use oat\taoEventLog\model\LogEntity;
use Prophecy\Argument;
use oat\taoEventLog\model\StorageInterface;
use oat\oatbox\service\ConfigurableService;

/**
 * Class ProxyStorageTest
 * @package oat\taoEventLog\test\model\requestLog\rds
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class ProxyStorageTest extends TaoPhpUnitTestRunner
{
    /** @var \common_persistence_KeyValuePersistence */
    private $kvPersistence;

    public function testLog()
    {
        $storage = $this->getService();
        $eventProphecy = $this->prophesize(Event::class);
        $eventProphecy->getName()->willReturn('event name');

        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getIdentifier()->willReturn('user_id');
        $userProphecy->getRoles()->willReturn(['role_1', 'role_2']);

        $logEntity = new LogEntity(
            $eventProphecy->reveal(),
            'action name',
            $userProphecy->reveal(),
            DateTime::createFromFormat('Y-m-d H:i:s', '2017-04-19 12:00:00'),
            ['id'=>1]
        );

        $this->assertFalse($this->kvPersistence->exists(ProxyStorage::LAST_ID_KEY));

        $storage->log($logEntity);

        $this->assertTrue($this->kvPersistence->exists(ProxyStorage::LAST_ID_KEY));
        $this->assertEquals(0, $this->kvPersistence->get(ProxyStorage::LAST_ID_KEY));
        $this->assertEquals($logEntity, unserialize($this->kvPersistence->get(0)));
    }

    public function testBulkLog()
    {
        $storage = $this->getService();
        $logEntities = [];
        for ($i = 0; $i < 5; $i++) {
            $eventProphecy = $this->prophesize(Event::class);
            $eventProphecy->getName()->willReturn('test_event_' . $i);

            $userProphecy = $this->prophesize(User::class);
            $userProphecy->getIdentifier()->willReturn('test_user_' . $i);
            $userProphecy->getRoles()->willReturn(['role_' . (($i%5)+1) , 'role_2' . (($i%5)+2)]);

            $logEntities[] = new LogEntity(
                $eventProphecy->reveal(),
                'test_action_' . $i,
                $userProphecy->reveal(),
                DateTime::createFromFormat('Y-m-d H:i:s', '2017-04-19 12:'.str_pad($i, 2, '0', STR_PAD_LEFT).':00'),
                ['id'=>$i]
            );
        }

        $this->assertFalse($this->kvPersistence->exists(ProxyStorage::LAST_ID_KEY));

        $storage->bulkLog($logEntities);

        $this->assertTrue($this->kvPersistence->exists(ProxyStorage::LAST_ID_KEY));
        $this->assertEquals(4, $this->kvPersistence->get(ProxyStorage::LAST_ID_KEY));
        $this->assertEquals($logEntities[0], unserialize($this->kvPersistence->get(0)));
    }

    public function testCount()
    {
        $storage = $this->getService();
        $internalStorage = $this->getInternalStorageProphecy();
        $internalStorage->count(Argument::type('array'), Argument::type('array'))->shouldBeCalledTimes(1)->willReturn(60);
        $storage->setOption(ProxyStorage::OPTION_INTERNAL_STORAGE, $internalStorage->reveal());
        $this->loadFixtures($storage);
        $this->assertEquals(60, $storage->count());
    }

    public function testSearch()
    {
        $storage = $this->getService();
        $internalStorage = $this->getInternalStorageProphecy();
        $internalStorage->search(Argument::type('array'), Argument::type('array'))->shouldBeCalledTimes(1)->willReturn('search_result');
        $storage->setOption(ProxyStorage::OPTION_INTERNAL_STORAGE, $internalStorage->reveal());
        $this->loadFixtures($storage);
        $this->assertEquals('search_result', $storage->search());
    }

    public function testFlush()
    {
        $storage = $this->getService();
        $internalStorage = $this->getInternalStorageProphecy();
        $internalStorage->bulkLog(Argument::type('array'))->shouldBeCalledTimes(1)->willReturn(true);
        $storage->setOption(ProxyStorage::OPTION_INTERNAL_STORAGE, $internalStorage->reveal());
        $this->loadFixtures($storage);
        $this->assertEquals(59, $this->kvPersistence->get(ProxyStorage::LAST_ID_KEY));
        $storage->flush();
        $this->assertFalse($this->kvPersistence->exists(ProxyStorage::LAST_ID_KEY));
    }

    /**
     * @expectedException \common_exception_InconsistentData
     */
    public function testGetPersistenceException()
    {
        $persistenceManager = $this->getSqlMock('rds');

        $config = new \common_persistence_KeyValuePersistence([], new \common_persistence_InMemoryKvDriver());
        $config->set(\common_persistence_Manager::SERVICE_ID, $persistenceManager);
        $serviceManager = new ServiceManager($config);

        $storage = new ProxyStorage([
            ProxyStorage::OPTION_PERSISTENCE => 'rds',
            ProxyStorage::OPTION_INTERNAL_STORAGE => $this->getInternalStorageProphecy()->reveal(),
        ]);
        $storage->setServiceManager($serviceManager);
        $storage->flush();
    }

    /**
     * @expectedException \common_exception_InconsistentData
     */
    public function testGetInternalStorageException()
    {
        $storage = $this->getService();
        $smProphecy = $this->prophesize(ConfigurableService::class);
        $smProphecy->setServiceManager(Argument::any())->willReturn(null);
        $storage->setOption(ProxyStorage::OPTION_INTERNAL_STORAGE, $smProphecy->reveal());
        $storage->count();
    }

    /**
     * @return ProxyStorage
     */
    protected function getService()
    {
        $this->kvPersistence = new \common_persistence_KeyValuePersistence([], new \common_persistence_InMemoryKvDriver());

        $pmProphecy = $this->prophesize(\common_persistence_Manager::class);
        $pmProphecy->setServiceLocator(Argument::any())->willReturn(null);
        $pmProphecy->getPersistenceById('test_kv')->willReturn($this->kvPersistence);

        $config = new \common_persistence_KeyValuePersistence([], new \common_persistence_InMemoryKvDriver());
        $config->set(\common_persistence_Manager::SERVICE_ID, $pmProphecy->reveal());
        $serviceManager = new ServiceManager($config);

        $storage = new ProxyStorage([
            ProxyStorage::OPTION_PERSISTENCE => 'test_kv',
            ProxyStorage::OPTION_INTERNAL_STORAGE => $this->getInternalStorageProphecy()->reveal(),
        ]);
        $storage->setServiceManager($serviceManager);
        return $storage;
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    protected function getInternalStorageProphecy()
    {
        $storageProphecy = $this->prophesize(ProxyStorage::class);
        $storageProphecy->setServiceManager(Argument::any())->willReturn(null);
        return $storageProphecy;
    }

    protected function loadFixtures(StorageInterface $storage)
    {
        for ($i = 0; $i < 60; $i++) {
            $eventProphecy = $this->prophesize(Event::class);
            $eventProphecy->getName()->willReturn('test_event_' . $i);

            $userProphecy = $this->prophesize(User::class);
            $userProphecy->getIdentifier()->willReturn('test_user_' . $i);
            $userProphecy->getRoles()->willReturn(['role_' . (($i%5)+1) , 'role_2' . (($i%5)+2)]);

            $logEntity = new LogEntity(
                $eventProphecy->reveal(),
                'test_action_' . $i,
                $userProphecy->reveal(),
                DateTime::createFromFormat('Y-m-d H:i:s', '2017-04-19 12:'.str_pad($i, 2, '0', STR_PAD_LEFT).':00'),
                ['id'=>$i]
            );

            $storage->log($logEntity);
        }
    }
}