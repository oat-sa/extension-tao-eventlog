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
 * @author Alexander Zagovorichev <zagovorichev@1pt.com>
 */

namespace oat\taoEventLog\scripts\tools;


use oat\dtms\DateTime;
use oat\oatbox\action\Action;
use oat\oatbox\service\ServiceManager;
use oat\taoEventLog\model\storage\RdsStorage;
use oat\taoEventLog\model\StorageInterface;

class ConvertStoredTimezone implements Action
{

    /** @var  \common_report_Report */
    private $report;

    /**
     * @var bool
     */
    private $dryrun = false;

    public function __invoke( $params )
    {

        $this->dryrun = in_array('dryrun', $params) || in_array('--dryrun', $params);

        /** @var StorageInterface $storageService */
        $storageService = ServiceManager::getServiceManager()->get(StorageInterface::SERVICE_ID);

        $page = 1;
        $rows = 500;

        $this->report = new \common_report_Report(\common_report_Report::TYPE_INFO, 'Converting of dates for the event log');

        while ( true ) {

            $slice = $storageService->searchInstances(['page' => $page, 'rows' => $rows ]);

            if (!count($slice['data'])) {
                break;
            }

            foreach ($slice['data'] as $row) {

                if (empty($row['occurred']) || $row['occurred'] == '0000-00-00 00:00:00') {
                    $this->report->add(new \common_report_Report(\common_report_Report::TYPE_WARNING, 'Would not be converted date in id="' . $row['id'] . '" date is "' . $row['occurred'] . '"'));
                    continue;
                }

                if ($this->dryrun) {
                    $this->report->add(new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'Would be changed date "' . $row['occurred'] . '" to "' . $this->convertToUtcDate($row['occurred']) . '"'));
                } else {
                    $this->setOccurred($storageService, $row['id'], $this->convertToUtcDate($row['occurred']));
                }

            }

            if ($this->dryrun) {
                $this->report->add(new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'In the same way would be changed "' . $slice['records'] . '" records'));
                break;
            }

            $page++;
        }

        $this->report->add(new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'Done'));

        return $this->report;
    }

    private function convertToUtcDate($date = '')
    {

        // will be in current TIME_ZONE
        $dateTime = new DateTime($date);
        $dateTime->setTimezone(new \DateTimeZone('UTC'));
        return $dateTime->format(\DateTime::ISO8601);
    }

    private function setOccurred($storageService, $id, $occurred)
    {
        /** @var \common_persistence_SqlPersistence $persistence */
        $persistence = $storageService->getPersistence();
        $sql = "UPDATE " . RdsStorage::EVENT_LOG_TABLE_NAME . " SET `occurred` = ? WHERE id = ?";
        $r = $persistence->exec($sql, [$occurred, $id]);

        return $r === 1;
    }
}
