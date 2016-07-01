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


use oat\oatbox\action\Action;
use common_report_Report;
use oat\taoEventLog\model\storage\RdsStorage;

class Faker implements Action
{
    private $events = ['login', 'logout', 'runDelivery', 'createDelivery', 'editDelivery'];
    private $actions = ['registration', 'delivery'];
    private $roles = ['guest', 'testTaker', 'admin'];
    private $data = [
        [
            'fakeData' => 'FakeAction'
        ],
        [
            'deliveryId' => '#delivery1',
            'deliveryExecution' => '#deliveryExecution',
            'proctor' => '#proctorId',
            'testCenter' => '#testCenterId',
            'additionalInfo' => [
                'projectName' => 'EventLog extension',
            ]
        ]
    ];

    public function __invoke($params)
    {
        if (empty($params)) {
            return new common_report_Report(common_report_Report::TYPE_ERROR, 'USAGE: ' . __CLASS__ . ' AMOUNT_OF_ADDED_DATA [dryrun]');
        }

        $count = intval(array_shift($params));
        if (!$count > 0) {
            return new common_report_Report(common_report_Report::TYPE_ERROR, 'USAGE: AMOUNT_OF_ADDED_DATA should be positive integer');
        }

        $dryrun = in_array('dryrun', $params) || in_array('--dryrun', $params);

        $report = new common_report_Report(common_report_Report::TYPE_INFO, 'Generating of the "' . $count . ' event log records"');


        $storage = new RdsStorage('default');

        if ($dryrun) {
            $report->add(new common_report_Report(common_report_Report::TYPE_SUCCESS, 'Will be generated ' . $count . ' events, looks like: ' . print_r($this->faker( $count > 3 ? 3 : $count ), true)));
        } else {
            foreach ($this->faker($count) as $fake) {
                $storage->log(
                    $fake[0],
                    $fake[1],
                    $fake[2],
                    $fake[3],
                    $fake[4],
                    $fake[5]
                );
            }
        }

        return $report;
    }

    private function faker($count = 0)
    {
        $fakeData = [];

        for ($i = 0; $i < $count; $i++) {
            $fakeData[] = [
                $this->events[array_rand($this->events)],
                $this->actions[array_rand($this->actions)],
                '#user_' . mt_rand(1, 10),
                $this->roles[array_rand($this->roles)],
                date('Y-m-d H:i:s', mktime(rand(0, 23), rand(0, 59), rand(0, 59), rand(1, 12), rand(1, 31), 2016)),
                json_encode($this->data[array_rand($this->data)])
            ];
        }

        return $fakeData;
    }
}
