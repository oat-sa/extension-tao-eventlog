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
 * Copyright (c) 2024 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoEventLog\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use oat\generis\persistence\PersistenceManager;
use oat\oatbox\reporting\Report;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoEventLog\model\userLastActivityLog\rds\UserLastActivityLogStorage;

final class Version202412181339442234_taoEventLog extends AbstractMigration
{
    public function getDescription(): string
    {
        return sprintf(
            'Expand role field for "%s" table',
            UserLastActivityLogStorage::TABLE_NAME
        );
    }

    public function up(Schema $schema): void
    {
        $originalSchema = clone $schema;
        $this->updateTable($schema, Type::getType(Types::TEXT));
        $this->migrate($originalSchema, $schema);

        $this->addReport(
            Report::createSuccess(
                sprintf(
                    'Table "%s" successfully updated',
                    UserLastActivityLogStorage::TABLE_NAME
                )
            )
        );
    }

    public function down(Schema $schema): void
    {
        $originalSchema = clone $schema;
        $this->updateTable($schema, Type::getType(Types::STRING));
        $this->migrate($originalSchema, $schema);

        $this->addReport(
            Report::createSuccess(
                sprintf(
                    'Table "%s" successfully reverted',
                    UserLastActivityLogStorage::TABLE_NAME
                )
            )
        );
    }

    private function updateTable(Schema $schema, Type $filedType): void
    {
        $userLastActivityLogTable = $schema->getTable(
            UserLastActivityLogStorage::TABLE_NAME
        );

        $userLastActivityLogTable->changeColumn(
            UserLastActivityLogStorage::COLUMN_USER_ROLES,
            [
                'type' => $filedType,
            ]
        );
    }

    private function migrate(Schema $originalSchema, Schema $schema): void
    {
        $persistenceManager = $this->getServiceLocator()->get(PersistenceManager::SERVICE_ID);
        $persistence = $persistenceManager->getPersistenceById('default');

        $queries = $persistence->getPlatForm()->getMigrateSchemaSql($originalSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }
}
