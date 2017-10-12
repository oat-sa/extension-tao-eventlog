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

use common_Logger;
use common_persistence_SqlPersistence;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use oat\oatbox\service\ServiceManager;
use oat\taoEventLog\model\storage\RdsStorage;

if (!ServiceManager::getServiceManager()->has(RdsStorage::SERVICE_ID)) {
    return;
}
/** @var RdsStorage $storageService */
$storageService = ServiceManager::getServiceManager()->get(RdsStorage::SERVICE_ID);

/** @var common_persistence_SqlPersistence $persistence */
$persistence = $storageService->getPersistence();

/** @var AbstractSchemaManager $schemaManager */
$schemaManager = $persistence->getDriver()->getSchemaManager();

/** @var Schema $schema */
$schema = $schemaManager->createSchema();
$fromSchema = clone $schema;

try {
    $schema->dropTable(RdsStorage::EVENT_LOG_TABLE_NAME);
} catch (SchemaException $e) {
    common_Logger::i('Database Schema for EventLog can\'t be dropped.');
}

$queries = $persistence->getPlatForm()->getMigrateSchemaSql($fromSchema, $schema);
foreach ($queries as $query) {
    $persistence->exec($query);
}
