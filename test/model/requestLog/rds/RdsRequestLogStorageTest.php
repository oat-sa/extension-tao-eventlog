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
use oat\taoEventLog\model\requestLog\rds\RdsRequestLogStorage;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\service\ServiceNotFoundException;

/**
 * Class RdsRequestLogStorageTest
 * @package oat\taoEventLog\test\model\requestLog\rds
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RdsRequestLogStorageTest extends TaoPhpUnitTestRunner
{

    /**
     * Check whether rds request log is installed
     */
    protected function setUp()
    {
        $persistence = $this->getPersistence();
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        if (!$schema->hasTable(RdsRequestLogStorage::TABLE_NAME)) {
            $this->markTestSkipped(
                'RdsRequestLogStorage table is not exist.'
            );
        }
        $this->deleteTestData();
    }

    public function testLog()
    {
        $serviceManager = ServiceManager::getServiceManager();
    }

    /**
     * @return RdsRequestLogStorage
     */
    protected function getService()
    {
        $serviceManager = ServiceManager::getServiceManager();
        $service = new RdsRequestLogStorage([
            RdsRequestLogStorage::OPTION_PERSISTENCE => $this->getPersistence()->getPersistenceId
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
            $service = $serviceManager->get(RdsRequestLogStorage::SERVICE_ID);
            if ($service instanceof RdsRequestLogStorage) {
                $persistenceId = $service->getOption(RdsRequestLogStorage::OPTION_PERSISTENCE);
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
}