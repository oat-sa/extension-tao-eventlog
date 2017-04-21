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
 * Copyright (c) 2016  (original work) Open Assessment Technologies SA;
 *
 * @author Ivan klimchuk <klimchuk@1pt.com>
 */

namespace oat\taoEventLog\model\export\implementation;

use DateInterval;
use DateTimeImmutable;
use oat\oatbox\service\ServiceManager;
use oat\taoEventLog\model\export\Exporter;
use oat\taoEventLog\model\LoggerService;

/**
 * Class LogEntryCsvExporter
 * @package oat\taoEventLog\model\export\implementation
 */
class LogEntryCsvExporter implements Exporter
{
    /** @var LoggerService $loggerService */
    private $loggerService;

    /**
     * LogEntryCsvExporter constructor.
     */
    public function __construct()
    {
        $this->loggerService = ServiceManager::getServiceManager()->get(LoggerService::SERVICE_ID);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function export(array $params = [])
    {
        $from = (new DateTimeImmutable())
            ->sub(new DateInterval($this->loggerService->getOption(LoggerService::OPTION_EXPORTABLE_PERIOD)))
            ->format(\DateTime::ISO8601);
        $options['limit'] = $this->loggerService->getOption(LoggerService::OPTION_EXPORTABLE_QUANTITY);
        $filters[] = ['occurred', '>' , $from];

        $data = $this->loggerService->searchInstances($filters, $options);

        return $data;
    }
}
