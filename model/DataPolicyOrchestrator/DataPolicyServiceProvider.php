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
use oat\tao\model\DataPolicyOrchestrator\Handler\FullDataRemovalHandlerProxy;
use oat\taoEventLog\model\DataPolicyOrchestrator\Handler\DataRemovalHandler;
use oat\taoEventLog\model\DataPolicyOrchestrator\Handler\FullDataRemovalHandler;
use oat\taoEventLog\model\DataPolicyOrchestrator\Repository\EventLogRepository;
use oat\taoEventLog\model\eventLog\RdsStorage;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class DataPolicyServiceProvider implements ContainerServiceProviderInterface
{
    public function __invoke(ContainerConfigurator $configurator): void
    {
        $services = $configurator->services();

        $services
            ->set(EventLogRepository::class, EventLogRepository::class)
            ->args(
                [
                    service(RdsStorage::SERVICE_ID),
                ]
            );

        $services
            ->set(DataRemovalHandler::class, DataRemovalHandler::class)
            ->args(
                [
                    service(EventLogRepository::class),
                    service(LoggerService::SERVICE_ID),
                ]
            );

        $services
            ->set(FullDataRemovalHandler::class, FullDataRemovalHandler::class)
            ->args(
                [
                    service(EventLogRepository::class),
                ]
            );

        $services
            ->get(DataRemovalHandlerProxy::class)
            ->call(
                'addHandler',
                ['remove-deactivated-administrative-user-peripheral-data', service(DataRemovalHandler::class)]
            );

        $services
            ->get(FullDataRemovalHandlerProxy::class)
            ->call(
                'addHandler',
                [
                    'remove-deactivated-administrative-user-peripheral-data',
                    service(FullDataRemovalHandler::class)
                ]
            );
    }
}
