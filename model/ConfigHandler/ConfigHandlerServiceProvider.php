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

use oat\generis\model\DependencyInjection\ContainerServiceProviderInterface;
use oat\tao\model\featureFlag\FeatureFlagConfigSwitcher;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

class ConfigHandlerServiceProvider implements ContainerServiceProviderInterface
{
    private const DEFAULT_CLEANUP_ENABLED = 'eventLog.cleanup.default.enabled';
    private const DEFAULT_CLEANUP_PERIOD = 'eventLog.cleanup.default.period';
    private const DEFAULT_CLEANUP_EVENTS = 'eventLog.cleanup.default.events';

    public function __invoke(ContainerConfigurator $configurator): void
    {
        $services = $configurator->services();
        $parameters = $configurator->parameters();

        $parameters->set(self::DEFAULT_CLEANUP_ENABLED, false);
        $parameters->set(self::DEFAULT_CLEANUP_PERIOD, '');
        $parameters->set(self::DEFAULT_CLEANUP_EVENTS, '');

        $services
            ->set(CleanupConfigHandler::class, CleanupConfigHandler::class)
            ->args([
                env('EVENT_LOG_CLEANUP_ENABLED')->default(self::DEFAULT_CLEANUP_ENABLED)->bool(),
                env('EVENT_LOG_CLEANUP_PERIOD')->default(self::DEFAULT_CLEANUP_PERIOD)->string(),
                env('EVENT_LOG_CLEANUP_EVENTS')->default(self::DEFAULT_CLEANUP_EVENTS)->string(),
            ])
            ->public();

        $services
            ->get(FeatureFlagConfigSwitcher::class)
            ->call(
                'addClientConfigHandler',
                [
                    CleanupConfigHandler::class,
                ]
            );
    }
}
