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

namespace oat\taoEventLog\model\DataPolicyOrchestrator\Handler;

use oat\tao\model\DataPolicyOrchestrator\Exception\DataPolicyException;
use oat\tao\model\DataPolicyOrchestrator\Handler\DataPolicyHandlerInterface;
use oat\tao\model\DataPolicyOrchestrator\Model\DataPolicyMessage;
use oat\taoEventLog\model\DataPolicyOrchestrator\Repository\EventLogRepository;

class FullDataRemovalHandler implements DataPolicyHandlerInterface
{
    public function __construct(
        private readonly EventLogRepository $eventLogRepository,
    ) {
    }

    public function handle(DataPolicyMessage $message): void
    {
        $login = $message->dataSubjectRawId;

        if ($this->eventLogRepository->existsByLogin($login)) {
            throw new DataPolicyException(
                sprintf('[Data policy - full data removal] Event logs for user "%s" still exists', $login)
            );
        }
    }
}
