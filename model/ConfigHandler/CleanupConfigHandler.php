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

namespace oat\taoEventLog\model\ConfigHandler;

use DateInterval;
use Exception;
use oat\tao\model\featureFlag\FeatureFlagConfigHandlerInterface;
use tao_helpers_Date;

class CleanupConfigHandler implements FeatureFlagConfigHandlerInterface
{
    private const MODULE_CONFIG_ID = 'taoEventLog/controller/TaoEventLog/show';

    public function __construct(
        private readonly bool $cleanupEnabled,
        private readonly string $cleanupPeriod,
        private readonly string $cleanupEvents
    ) {
    }

    public function __invoke(array $configs): array
    {
        $moduleConfig = $configs[self::MODULE_CONFIG_ID] ?? [];
        $moduleConfig['cleanup'] = [
            'enabled' => $this->cleanupEnabled,
            'period' => $this->extractPeriodLabel(),
            'events' => array_filter(array_map('trim', explode(',', $this->cleanupEvents))),
        ];

        $configs[self::MODULE_CONFIG_ID] = $moduleConfig;

        return $configs;
    }

    private function extractPeriodLabel(): string
    {
        $period = trim($this->cleanupPeriod);

        if ($period === '') {
            return '';
        }

        try {
            return (string) tao_helpers_Date::displayInterval(new DateInterval($period));
        } catch (Exception $exception) {
            return '';
        }
    }
}
