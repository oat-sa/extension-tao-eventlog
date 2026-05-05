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

namespace oat\taoEventLog\model\Repository;

use InvalidArgumentException;
use oat\taoEventLog\model\Config\EventLogField;
use oat\taoEventLog\model\eventLog\RdsStorage;
use Psr\Log\LoggerInterface;

class EventLogRepository
{
    public function __construct(private readonly RdsStorage $storage, private readonly LoggerInterface $logger)
    {
    }

    public function deleteBy(array $params): int
    {
        return $this->storage->delete($this->buildFilters($params));
    }

    public function existsBy(array $params): bool
    {
        return (bool) $this->storage->count($this->buildFilters($params));
    }

    private function buildFilters(array $params): array
    {
        $filters = [];

        foreach ($params as $param => $value) {
            if (!EventLogField::tryFrom($param)) {
                $this->logger->warning(sprintf('Event log field "%s" does not exist.', $param));

                continue;
            }

            $filters[] = [
                $param,
                is_array($value) ? 'in' : '=',
                $value
            ];
        }

        if (empty($filters)) {
            throw new InvalidArgumentException('At least one filter must be specified.');
        }

        return $filters;
    }
}
