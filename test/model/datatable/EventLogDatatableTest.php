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

namespace oat\taoEventLog\test\model\datatable;

use oat\tao\model\datatable\implementation\DatatableRequest;
use oat\tao\model\session\source\SessionSourceMatcher;
use oat\taoEventLog\model\datatable\EventLogDatatable;
use oat\taoEventLog\model\eventLog\LoggerService;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class EventLogDatatableTest extends TestCase
{
    /**
     * @dataProvider payloadOptionsProvider
     */
    public function testGetPayloadUsesRequestPagingAndSorting(
        int $rows,
        int $page,
        ?string $sortBy,
        string $sortOrder,
        int $expectedOffset
    ): void {
        $request = $this->createMock(DatatableRequest::class);
        $request->method('getRows')->willReturn($rows);
        $request->method('getPage')->willReturn($page);
        $request->method('getSortBy')->willReturn($sortBy);
        $request->method('getSortOrder')->willReturn($sortOrder);

        $logger = $this->createMock(LoggerService::class);
        $sessionSourceMatcher = $this->createMock(SessionSourceMatcher::class);
        $sessionSourceMatcher->expects($this->once())->method('isPortalSession')->willReturn(false);

        $filters = [['event_name', 'like', '%Login%']];
        $rawData = [];
        $recordsCount = 42;

        $logger
            ->expects($this->once())
            ->method('searchInstances')
            ->with($filters, [
                'limit' => $rows,
                'offset' => $expectedOffset,
                'sort' => $sortBy,
                'order' => $sortOrder,
            ])
            ->willReturn($rawData);

        $logger
            ->expects($this->once())
            ->method('count')
            ->with($filters)
            ->willReturn($recordsCount);

        $subject = $this->getMockBuilder(EventLogDatatable::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFilters'])
            ->getMock();

        $subject->expects($this->once())->method('getFilters')->willReturn($filters);

        $this->injectProperty($subject, 'request', $request);
        $this->injectProperty($subject, 'loggerService', $logger);
        $this->injectProperty($subject, 'sessionSourceMatcher', $sessionSourceMatcher);

        $payload = $subject->getPayload();

        $this->assertSame([], $payload['data']);
        $this->assertSame($page, $payload['page']);
        $this->assertSame(0, $payload['records']);
        $this->assertEquals(ceil($recordsCount / $rows), $payload['total']);
    }

    public function testJsonSerializeDelegatesToGetPayload(): void
    {
        $payload = ['data' => [], 'page' => 1, 'records' => 0, 'total' => 0.0];

        $subject = $this->getMockBuilder(EventLogDatatable::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPayload'])
            ->getMock();

        $subject->expects($this->once())->method('getPayload')->willReturn($payload);

        $this->assertSame($payload, $subject->jsonSerialize());
    }

    public function payloadOptionsProvider(): array
    {
        return [
            'first page' => [25, 1, 'occurred', 'asc', 0],
            'third page' => [10, 3, 'id', 'desc', 20],
        ];
    }

    private function injectProperty(object $subject, string $propertyName, $value): void
    {
        $property = new ReflectionProperty(EventLogDatatable::class, $propertyName);
        $property->setValue($subject, $value);
    }
}
