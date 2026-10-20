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
 * Copyright (c) 2018-2026 (original work) Open Assessment Technologies SA.
 */

namespace oat\taoEventLog\scripts\tools;

use DateInterval;
use DateTimeImmutable;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\script\ScriptAction;
use oat\oatbox\reporting\Report;
use oat\taoEventLog\model\Config\EventLogField;
use oat\taoEventLog\model\eventLog\LoggerService;
use oat\taoEventLog\model\storage\AbstractRdsStorage;

/**
 * @example sudo -u www-data php index.php 'oat\taoEventLog\scripts\tools\CleanUpEventLogs'
 */
class CleanUpEventLogs extends ScriptAction
{
    use OntologyAwareTrait;

    protected function provideOptions(): array
    {
        return [
            'wetRun' => [
                'prefix' => 'w',
                'longPrefix' => 'wet-run',
                'flag' => true,
                'description' => 'Will perform real database operations if it will be required, including data removal',
                'required' => false,
                'defaultValue' => false,
            ],
            'period' => [
                'prefix' => 'p',
                'longPrefix' => 'period',
                'description' => 'Specify period that should be KEPT, all records older will be removed.',
                'required' => true,
            ],
            'events' => [
                'prefix' => 'e',
                'longPrefix' => 'events',
                'cast' => 'string',
                'description' => 'Specify exact events that should be REMOVED, coma separated',
                'required' => true,
            ],
        ];
    }

    protected function provideDescription(): string
    {
        return 'Scripts removes logged request/response related data from event log, older than given time';
    }

    protected function provideUsage(): array
    {
        return [
            'prefix' => 'h',
            'longPrefix' => 'help',
            'description' => 'Shows this message'
        ];
    }

    protected function run(): Report
    {
        $report = Report::createInfo('Script execution started');

        $filters = [[EventLogField::Occurred->value, '<=', $this->getBeforeDate()]];
        $events = $this->getEvents();

        if (!empty($events)) {
            $filters[] = [EventLogField::EventName->value, 'in', $events];
        }

        $loggerService = $this->getLoggerService();
        $count = $loggerService->count($filters);
        $report->add(Report::createInfo(sprintf('%s to be removed ', $count)));

        if ($this->getOption('wetRun')) {
            $report->add(Report::createInfo('Script is running in wet-run mode'));
            $x = $loggerService->delete($filters);
            $report->add(Report::createInfo(sprintf('%s to be removed ', $x)));
        } else {
            $report->add(Report::createInfo('Script is running in dry-run mode'));
        }

        $report->add(Report::createSuccess('Script finished execution'));

        return $report;
    }

    private function getBeforeDate(): string
    {
        $period = new DateInterval($this->getOption('period'));

        return (new DateTimeImmutable())
            ->sub($period)
            ->format(AbstractRdsStorage::DATE_TIME_FORMAT);
    }

    private function getEvents(): array
    {
        $events = explode(',', $this->getOption('events'));

        return array_values(array_filter(
            array_map('trim', $events),
            static fn (string $event): bool => $event !== ''
        ));
    }

    private function getLoggerService(): LoggerService
    {
        return $this->getServiceManager()->getContainer()->get(LoggerService::SERVICE_ID);
    }
}
