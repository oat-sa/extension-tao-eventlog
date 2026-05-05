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

namespace oat\taoEventLog\test\model\DataPolicyOrchestrator\Handler;

use oat\tao\model\DataPolicyOrchestrator\Exception\DataPolicyException;
use oat\tao\model\DataPolicyOrchestrator\Model\DataRemovalMessage;
use oat\taoEventLog\model\Config\EventLogField;
use oat\taoEventLog\model\DataPolicyOrchestrator\Handler\UserFullDataRemovalCheckHandler;
use oat\taoEventLog\model\Repository\EventLogRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserFullDataRemovalCheckHandlerTest extends TestCase
{
    private EventLogRepository|MockObject $eventLogRepository;
    private UserFullDataRemovalCheckHandler $subject;

    protected function setUp(): void
    {
        $this->eventLogRepository = $this->createMock(EventLogRepository::class);
        $this->subject = new UserFullDataRemovalCheckHandler($this->eventLogRepository);
    }

    public function testHandleDoesNotThrowWhenLogsDoNotExist(): void
    {
        $message = $this->createMessage();

        $this->eventLogRepository
            ->expects($this->once())
            ->method('existsBy')
            ->with([EventLogField::UserLogin->value => 'john.doe'])
            ->willReturn(false);

        $this->subject->handle($message);

        $this->addToAssertionCount(1);
    }

    public function testHandleThrowsExceptionWhenLogsStillExist(): void
    {
        $message = $this->createMessage();

        $this->eventLogRepository
            ->expects($this->once())
            ->method('existsBy')
            ->with([EventLogField::UserLogin->value => 'john.doe'])
            ->willReturn(true);

        $this->expectException(DataPolicyException::class);
        $this->expectExceptionMessage('[Data policy - full data removal] Event logs for user "john.doe" still exists');

        $this->subject->handle($message);
    }

    private function createMessage(): DataRemovalMessage
    {
        return new DataRemovalMessage([
            'dataSubjectRawId' => 'john.doe',
            'ownerApp' => 'authoring',
            'policyId' => 'policy-1',
            'policyVersion' => '1',
            'tenantId' => 'tenant-1',
            'uniqueId' => 'uid-1',
            'name' => 'user',
            'storageType' => 'db',
            'metadata' => [],
        ]);
    }
}
