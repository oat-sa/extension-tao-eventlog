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

namespace oat\taoEventLog\test\model\export\implementation;

use oat\tao\model\session\source\SessionSourceMatcher;
use oat\taoEventLog\model\eventLog\LoggerService;
use oat\taoEventLog\model\export\implementation\LogEntryRepository;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class LogEntryRepositoryTest extends TestCase
{
    public function testFetchRemovesUserRolesForPortalSession(): void
    {
        $logger = $this->createMock(LoggerService::class);
        $sessionSourceMatcher = $this->createMock(SessionSourceMatcher::class);
        $sessionSourceMatcher->expects($this->once())->method('isPortalSession')->willReturn(true);

        $logger->method('hasOption')->with(LoggerService::OPTION_FETCH_LIMIT)->willReturn(true);
        $logger->method('getOption')->willReturnCallback(
            static function (string $name, $default = null) {
                if ($name === LoggerService::OPTION_FETCH_LIMIT) {
                    return 10;
                }

                if ($name === LoggerService::OPTION_EXPORTABLE_QUANTITY) {
                    return 1;
                }

                return $default;
            }
        );
        $logger
            ->expects($this->once())
            ->method('search')
            ->with([], ['sort' => 'id', 'order' => 'desc', 'limit' => 1])
            ->willReturn([['id' => 10, 'user_roles' => 'roleA,roleB']]);

        $subject = $this->createSubject($logger, $sessionSourceMatcher, [], 'id', 'desc');
        $result = iterator_to_array($subject->fetch(), false);

        $this->assertSame([['id' => 10]], $result);
    }

    public function testFetchKeepsUserRolesForNonPortalSession(): void
    {
        $logger = $this->createMock(LoggerService::class);
        $sessionSourceMatcher = $this->createMock(SessionSourceMatcher::class);
        $sessionSourceMatcher->expects($this->once())->method('isPortalSession')->willReturn(false);

        $logger->method('hasOption')->with(LoggerService::OPTION_FETCH_LIMIT)->willReturn(true);
        $logger->method('getOption')->willReturnCallback(
            static function (string $name, $default = null) {
                if ($name === LoggerService::OPTION_FETCH_LIMIT) {
                    return 10;
                }

                if ($name === LoggerService::OPTION_EXPORTABLE_QUANTITY) {
                    return 1;
                }

                return $default;
            }
        );
        $logger
            ->expects($this->once())
            ->method('search')
            ->with([], ['sort' => 'id', 'order' => 'asc', 'limit' => 1])
            ->willReturn([['id' => 11, 'user_roles' => 'roleA']]);

        $subject = $this->createSubject($logger, $sessionSourceMatcher, [], 'id', 'asc');
        $result = iterator_to_array($subject->fetch(), false);

        $this->assertSame([['id' => 11, 'user_roles' => 'roleA']], $result);
    }

    public function testFetchPaginatesByIdAndRespectsLimits(): void
    {
        $logger = $this->createMock(LoggerService::class);
        $sessionSourceMatcher = $this->createMock(SessionSourceMatcher::class);
        $sessionSourceMatcher->expects($this->once())->method('isPortalSession')->willReturn(false);

        $logger->method('hasOption')->with(LoggerService::OPTION_FETCH_LIMIT)->willReturn(true);
        $logger->method('getOption')->willReturnCallback(
            static function (string $name, $default = null) {
                if ($name === LoggerService::OPTION_FETCH_LIMIT) {
                    return 2;
                }

                if ($name === LoggerService::OPTION_EXPORTABLE_QUANTITY) {
                    return 3;
                }

                return $default;
            }
        );

        $searchCalls = 0;
        $logger
            ->expects($this->exactly(2))
            ->method('search')
            ->willReturnCallback(
                function (array $filters, array $options) use (&$searchCalls) {
                    ++$searchCalls;

                    if ($searchCalls === 1) {
                        $this->assertSame([], $filters);
                        $this->assertSame(['sort' => 'occurred', 'order' => 'desc', 'limit' => 2], $options);
                        return [
                            ['id' => 9, 'user_roles' => 'r1'],
                            ['id' => 8, 'user_roles' => 'r2'],
                        ];
                    }

                    $this->assertSame([['id', '<', 8]], $filters);
                    $this->assertSame(['sort' => 'occurred', 'order' => 'desc', 'limit' => 1], $options);
                    return [['id' => 7, 'user_roles' => 'r3']];
                }
            );

        $subject = $this->createSubject($logger, $sessionSourceMatcher, [], 'occurred', 'desc');
        $result = iterator_to_array($subject->fetch(), false);

        $this->assertCount(3, $result);
        $this->assertSame([9, 8, 7], array_column($result, 'id'));
    }

    private function createSubject(
        LoggerService $logger,
        SessionSourceMatcher $sessionSourceMatcher,
        array $filters,
        ?string $sortColumn,
        ?string $sortOrder
    ): LogEntryRepository {
        $subject = $this->getMockBuilder(LogEntryRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->setProperty($subject, 'loggerService', $logger);
        $this->setProperty($subject, 'sessionSourceMatcher', $sessionSourceMatcher);
        $this->setProperty($subject, 'filters', $filters);
        $this->setProperty($subject, 'sortColumn', $sortColumn);
        $this->setProperty($subject, 'sortOrder', $sortOrder);

        return $subject;
    }

    private function setProperty(object $subject, string $propertyName, $value): void
    {
        $property = new ReflectionProperty(LogEntryRepository::class, $propertyName);
        $property->setValue($subject, $value);
    }
}
