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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoEventLog\scripts\tools;

use DateInterval;
use DateTimeImmutable;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\script\ScriptAction;
use \common_report_Report as Report;
use oat\taoEventLog\model\eventLog\LoggerService;
use oat\taoEventLog\model\eventLog\RdsStorage;
use oat\taoEventLog\model\storage\AbstractRdsStorage;

/**
 * Class CleanUpEventLogs
 * Usage: sudo -u www-data php index.php 'oat\taoEventLog\scripts\tools\CleanUpEventLogs'
 * @package oat\taoEventLog\scripts\tools
 */
class CleanUpEventLogs extends ScriptAction
{
    use OntologyAwareTrait;

    /**
     * @return array
     */
    protected function provideOptions()
    {
        return [
            'wetRun' => [
                'prefix' => 'w',
                'longPrefix' => 'wet-run',
                'flag' => true,
                'description' => 'Will perform real database operations if it will be required, including removing data',
                'required' => false,
                'defaultValue' => false
            ],
            'period' => [
                'prefix' => 'p',
                'longPrefix' => 'period',
                'description' => 'Specify period that should be KEPT, all records older will be removed.',
                'required' => true
            ],
            'events' => [
                'prefix' => 'e',
                'longPrefix' => 'events',
                'defaultValue' => [],
                'description' => 'Specify exact events that should be REMOVED, coma separated',
                'required' => true
            ],
        ];
    }

    protected function provideDescription()
    {
        return 'Scripts removes logged request/response related data from event log, older than given time';
    }

    protected function provideUsage()
    {
        return [
            'prefix' => 'h',
            'longPrefix' => 'help',
            'description' => 'Shows this message'
        ];
    }

    /**
     * @return Report
     * @throws \common_exception_Error
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    protected function run()
    {
        $report = new Report(Report::TYPE_INFO, 'Script execution started');

        $isWetRun = $this->getOption('wetRun');
        $period = new DateInterval($this->getOption('period'));
        $events = explode(',', $this->getOption('events'));
        $beforeDate = (new DateTimeImmutable())->sub($period);

        /** @var LoggerService $service */
        $service = $this->getServiceLocator()->get(LoggerService::SERVICE_ID);
        $filters = [];
        $filters[] = [RdsStorage::EVENT_LOG_OCCURRED, '<=', $beforeDate->format(AbstractRdsStorage::DATE_TIME_FORMAT)];
        $filters[] = [RdsStorage::EVENT_LOG_EVENT_NAME, 'in', $events];

        $count = $service->count($filters);
        $report->add(new Report(Report::TYPE_INFO, sprintf('%s to be removed ', $count)));


        if ($isWetRun) {
            $report->add(new Report(Report::TYPE_INFO, 'Script is running in wet-run mode'));
            $x = $service->delete($filters);
            $report->add(new Report(Report::TYPE_INFO, sprintf('%s to be removed ', $x)));
        } else {
            $report->add(new Report(Report::TYPE_INFO, 'Script is running in dry-run mode'));
        }

        $report->add(new Report(Report::TYPE_SUCCESS, 'Script finished execution'));

        return $report;
    }
}
