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

use oat\tao\model\DataPolicyOrchestrator\Handler\DataPolicyHandlerInterface;
use oat\tao\model\DataPolicyOrchestrator\Model\DataPolicyMessageInterface;
use oat\taoEventLog\model\Config\EventLogField;
use oat\taoEventLog\model\Repository\EventLogRepository;
use Psr\Log\LoggerInterface;

class UserDataRemovalHandler implements DataPolicyHandlerInterface
{
    public function __construct(
        private readonly EventLogRepository $eventLogRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(DataPolicyMessageInterface $message): void
    {
        $login = $message->dataSubjectRawId;

        $removedRows = $this->eventLogRepository->deleteBy([EventLogField::UserLogin->value => $login]);
        $this->logger->info(
            sprintf(
                'User data removal completed for login "%s", removed rows: %d.',
                $login,
                $removedRows
            )
        );
    }
}
