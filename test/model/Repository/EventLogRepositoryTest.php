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
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA.
 */

declare(strict_types=1);

namespace oat\taoEventLog\test\model\Repository;

use InvalidArgumentException;
use oat\taoEventLog\model\Config\EventLogField;
use oat\taoEventLog\model\eventLog\RdsStorage;
use oat\taoEventLog\model\Repository\EventLogRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EventLogRepositoryTest extends TestCase
{
    public function testDeleteByDelegatesToStorageDeleteWithBuiltFilters(): void
    {
        $storage = $this->createMock(RdsStorage::class);
        $logger = $this->createMock(LoggerInterface::class);

        $storage
            ->expects($this->once())
            ->method('delete')
            ->with([[EventLogField::UserLogin->value, '=', 'john.doe']])
            ->willReturn(3);

        $subject = new EventLogRepository($storage, $logger);

        $this->assertSame(3, $subject->deleteBy([EventLogField::UserLogin->value => 'john.doe']));
    }

    public function testExistsByReturnsTrueWhenStorageCountIsPositive(): void
    {
        $storage = $this->createMock(RdsStorage::class);
        $logger = $this->createMock(LoggerInterface::class);

        $storage
            ->expects($this->once())
            ->method('count')
            ->with([[EventLogField::UserLogin->value, '=', 'john.doe']])
            ->willReturn(1);

        $subject = new EventLogRepository($storage, $logger);

        $this->assertTrue($subject->existsBy([EventLogField::UserLogin->value => 'john.doe']));
    }

    public function testDeleteByThrowsWhenNoValidFiltersProvided(): void
    {
        $storage = $this->createMock(RdsStorage::class);
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with('Event log field "unknown" does not exist.');

        $subject = new EventLogRepository($storage, $logger);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one filter must be specified.');

        $subject->deleteBy(['unknown' => 'value']);
    }
}
