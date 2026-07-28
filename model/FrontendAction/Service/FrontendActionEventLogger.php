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
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA
 */

declare(strict_types=1);

namespace oat\taoEventLog\model\FrontendAction\Service;

use core_kernel_classes_Resource;
use oat\tao\model\TaoOntology;
use oat\taoEventLog\model\eventLog\LoggerService;
use oat\taoItems\model\event\ItemPrintAttemptEvent;

class FrontendActionEventLogger
{
    public function __construct(private readonly LoggerService $loggerService)
    {
    }

    public function logAction(string $action, core_kernel_classes_Resource $resource): void
    {
        if ($action !== 'itemPrintAttempt') {
            return;
        }

        if ($resource->isInstanceOf($resource->getClass(TaoOntology::CLASS_URI_ITEM))) {
            $this->loggerService->log(new ItemPrintAttemptEvent($resource->getUri()));
        }
    }
}
