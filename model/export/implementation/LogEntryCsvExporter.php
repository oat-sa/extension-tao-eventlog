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

use common_session_SessionManager;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use oat\oatbox\service\ServiceManager;
use oat\taoEventLog\model\eventLog\LoggerService;
use oat\taoEventLog\model\export\Exporter;

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
     * @param array  $filters
     *
     * @param string $sortColumn
     * @param string $sortOrder
     *
     * @return mixed
     * @throws \common_exception_Error
     */
    public function export(array $filters = [], $sortColumn = '', $sortOrder = 'asc')
    {
        $options = [
            'limit' => $this->loggerService->getOption(LoggerService::OPTION_EXPORTABLE_QUANTITY),
            'sort'  => $sortColumn,
            'order' => $sortOrder,
        ];

        return $this->loggerService->search($this->prepareFilters($filters), $options);
    }

    /**
     * @param array $filters
     *
     * @return array
     *
     * @throws \common_exception_Error
     * @throws \Exception
     */
    private function prepareFilters(array $filters = [])
    {
        /** @var \common_session_Session $session */
        $session  = common_session_SessionManager::getSession();
        $timeZone = new DateTimeZone($session->getTimeZone());
        $utc      = new DateTimeZone('UTC');

        $result = [];

        foreach ($filters as $name => $value) {
            if (!empty($value)) {
                switch ($name) {
                    case 'from':
                        $from     = new DateTimeImmutable($filters['from'], $timeZone);
                        $result[] = ['occurred', '>', $from->setTimezone($utc)->format(DateTime::ISO8601)];
                        break;
                    case 'to':
                        $to       = new DateTimeImmutable($filters['to'], $timeZone);
                        $result[] = ['occurred', '<=', $to->setTimezone($utc)->format(DateTime::ISO8601)];
                        break;
                    default:
                        $result[] = [$name, 'LIKE', "%$value%"];
                }
            }
        }

        return $result;
    }
}
