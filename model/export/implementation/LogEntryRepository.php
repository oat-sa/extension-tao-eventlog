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
 * Copyright (c) 2019  (original work) Open Assessment Technologies SA;
 */

namespace oat\taoEventLog\model\export\implementation;

use common_session_SessionManager;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use oat\oatbox\service\ServiceManager;
use oat\taoEventLog\model\eventLog\LoggerService;
use oat\taoEventLog\model\export\LogEntryRepositoryInterface;

class LogEntryRepository implements LogEntryRepositoryInterface
{
    /** @var LoggerService $loggerService */
    private $loggerService;
    /**
     * @var array
     */
    private $filters;
    /**
     * @var string
     */
    private $sortColumn;
    /**
     * @var string
     */
    private $sortOrder;

    /**
     * @param array $filters
     * @param string $sortColumn
     * @param string $sortOrder
     */
    public function __construct(array $filters = [], $sortColumn = null, $sortOrder = null)
    {
        $this->loggerService = ServiceManager::getServiceManager()->get(LoggerService::SERVICE_ID);
        $this->filters = $filters;
        $this->sortColumn = $sortColumn;
        $this->sortOrder = $sortOrder;
    }

    /**
     * @return \Generator
     *
     * @throws \common_exception_Error
     */
    public function fetch()
    {
        $internalLimit = $this->loggerService->hasOption(LoggerService::OPTION_FETCH_LIMIT)
            ? $this->loggerService->getOption(LoggerService::OPTION_FETCH_LIMIT)
            : 500;

        $limit = $this->loggerService->getOption(LoggerService::OPTION_EXPORTABLE_QUANTITY);

        $options = [
            'sort'  => $this->sortColumn,
            'order' => $this->sortOrder,
        ];

        $preparedFilters = $this->prepareFilters($this->filters);

        $lastId = null;

        $fetched = 0;

        do {
            $extendedPreparedFilters = (null !== $lastId)
                ? array_merge($preparedFilters, [['id', '<', $lastId]])
                : $preparedFilters;

            $leftToFetch = $limit - $fetched;
            $options['limit'] = $leftToFetch < $internalLimit ? $leftToFetch : $internalLimit;

            $logs = $this->loggerService->search($extendedPreparedFilters, $options);

            $count = count($logs);

            if ($count > 0) {
                $fetched += $count;
                $lastId = $logs[$count - 1]['id'];

                foreach ($logs as $log) {
                    yield $log;
                }
            }
        } while ($count > 0 && $fetched < $limit);
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
        $session = common_session_SessionManager::getSession();
        $timeZone = new DateTimeZone($session->getTimeZone());
        $utc = new DateTimeZone('UTC');

        $result = [];

        foreach ($filters as $name => $value) {
            if (!empty($value)) {
                switch ($name) {
                    case 'from':
                        $from = new DateTimeImmutable($filters['from'], $timeZone);
                        $result[] = ['occurred', '>', $from->setTimezone($utc)->format(DateTime::ISO8601)];
                        break;
                    case 'to':
                        $to = new DateTimeImmutable($filters['to'], $timeZone);
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
