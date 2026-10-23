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

namespace oat\taoEventLog\test\model\ConfigHandler;

use DateInterval;
use oat\taoEventLog\model\ConfigHandler\CleanupConfigHandler;
use PHPUnit\Framework\TestCase;
use tao_helpers_Date;
use oat\tao\model\event\LoginSucceedEvent;
use oat\taoItems\model\event\ItemContentViewEvent;

class CleanupConfigHandlerTest extends TestCase
{
    /**
     * @dataProvider cleanupConfigDataProvider
     */
    public function testInvokeCreatesExpectedCleanupConfig(
        bool $cleanupEnabled,
        string $cleanupPeriod,
        string $cleanupEvents,
        string $expectedPeriod,
        array $expectedEvents
    ): void {
        $subject = new CleanupConfigHandler($cleanupEnabled, $cleanupPeriod, $cleanupEvents);

        $result = $subject->__invoke([]);

        $this->assertSame(
            [
                'enabled' => $cleanupEnabled,
                'period' => $expectedPeriod,
                'events' => $expectedEvents,
            ],
            $result['taoEventLog/controller/TaoEventLog/show']['cleanup']
        );
    }

    public function cleanupConfigDataProvider(): array
    {
        return [
            'valid period with short event names' => [
                'cleanupEnabled' => true,
                'cleanupPeriod' => 'P365D',
                'cleanupEvents' => 'eventA, eventB, ,eventC',
                'expectedPeriod' => (string) tao_helpers_Date::displayInterval(new DateInterval('P365D')),
                'expectedEvents' => ['eventA', 'eventB', 'eventC'],
            ],
            'fqcn events are converted to short unique names' => [
                'cleanupEnabled' => true,
                'cleanupPeriod' => 'P365D',
                'cleanupEvents' => implode(
                    ',',
                    [LoginSucceedEvent::class, ItemContentViewEvent::class, LoginSucceedEvent::class]
                ),
                'expectedPeriod' => (string) tao_helpers_Date::displayInterval(new DateInterval('P365D')),
                'expectedEvents' => ['LoginSucceedEvent', 'ItemContentViewEvent'],
            ],
            'invalid period and empty events' => [
                'cleanupEnabled' => true,
                'cleanupPeriod' => 'invalid-period',
                'cleanupEvents' => '',
                'expectedPeriod' => '',
                'expectedEvents' => [],
            ],
            'empty period and noisy events input' => [
                'cleanupEnabled' => false,
                'cleanupPeriod' => '',
                'cleanupEvents' => ' eventA ,, eventB ',
                'expectedPeriod' => '',
                'expectedEvents' => ['eventA', 'eventB'],
            ],
        ];
    }
}
