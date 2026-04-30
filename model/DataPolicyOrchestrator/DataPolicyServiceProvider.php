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

namespace oat\taoEventLog\model\DataPolicyOrchestrator;

use oat\generis\model\DependencyInjection\ContainerServiceProviderInterface;
use oat\oatbox\log\LoggerService;
use oat\tao\model\DataPolicyOrchestrator\Handler\DataRemovalHandlerProxy;
use oat\tao\model\DataPolicyOrchestrator\Handler\FullDataRemovalCheckHandlerProxy;
use oat\taoEventLog\model\DataPolicyOrchestrator\Handler\UserDataRemovalHandler;
use oat\taoEventLog\model\DataPolicyOrchestrator\Handler\UserFullDataRemovalCheckHandler;
use oat\taoEventLog\model\Repository\EventLogRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class DataPolicyServiceProvider implements ContainerServiceProviderInterface
{
    public function __invoke(ContainerConfigurator $configurator): void
    {
        $services = $configurator->services();

        $services
            ->set(UserDataRemovalHandler::class, UserDataRemovalHandler::class)
            ->args(
                [
                    service(EventLogRepository::class),
                    service(LoggerService::SERVICE_ID),
                ]
            );

        $services
            ->set(UserFullDataRemovalCheckHandler::class, UserFullDataRemovalCheckHandler::class)
            ->args(
                [
                    service(EventLogRepository::class),
                ]
            );

        $services
            ->get(DataRemovalHandlerProxy::class)
            ->call(
                'addHandler',
                ['remove-deactivated-administrative-user-peripheral-data', service(UserDataRemovalHandler::class)]
            );

        $services
            ->get(FullDataRemovalCheckHandlerProxy::class)
            ->call(
                'addHandler',
                [
                    'remove-deactivated-administrative-user-peripheral-data',
                    service(UserFullDataRemovalCheckHandler::class)
                ]
            );
    }
}
