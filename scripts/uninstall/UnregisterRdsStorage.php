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
 * @author Ivan Klimchuk <klimchuk@1pt.com>
 */

namespace oat\taoEventLog\scripts\uninstall;

use common_ext_action_InstallAction;
use common_Logger;
use common_persistence_SqlPersistence;
use common_report_Report;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use oat\oatbox\action\Action;
use oat\oatbox\service\ServiceManager;
use oat\taoEventLog\model\StorageInterface;

/**
 * Class UnregisterRdsStorage
 * @package oat\taoEventLog\scripts\uninstall
 */
class UnregisterRdsStorage extends common_ext_action_InstallAction implements Action
{
    /**
     * @param $params
     * @return \common_report_Report
     * @throws \common_exception_Error
     */
    public function __invoke($params = '')
    {
        if (!ServiceManager::getServiceManager()->has(StorageInterface::SERVICE_ID)) {
            common_Logger::i(sprintf("Service '%s' not found and can not be dropped", StorageInterface::SERVICE_ID));
        }

        $storageService = ServiceManager::getServiceManager()->get(StorageInterface::SERVICE_ID);

        /** @var common_persistence_SqlPersistence $persistence */
        $persistence = $storageService->getPersistence();

        /** @var AbstractSchemaManager $schemaManager */
        $schemaManager = $persistence->getDriver()->getSchemaManager();

        /** @var Schema $schema */
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $schema->dropTable(StorageInterface::EVENT_LOG_TABLE_NAME);
        } catch (SchemaException $e) {
            common_Logger::i('Database Schema for EventLog can\'t be dropped.');
        }

        $queries = $persistence->getPlatForm()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }

        return new common_report_Report(common_report_Report::TYPE_SUCCESS, __('Unregistered and dropped EventLog Rds Storage'));
    }
}

call_user_func(new UnregisterRdsStorage());
