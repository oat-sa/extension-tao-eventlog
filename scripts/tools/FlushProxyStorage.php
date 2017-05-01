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
 */

namespace oat\taoEventLog\scripts\tools;

use oat\taoEventLog\model\storage\ProxyStorage;
use oat\oatbox\extension\AbstractAction;
use oat\taoEventLog\model\StorageInterface;

class FlushProxyStorage extends AbstractAction
{

    /** @var  \common_report_Report */
    private $report;

    /**
     * @param $params
     * @return \common_report_Report
     */
    public function __invoke( $params )
    {
        /** @var StorageInterface $storageService */
        $storageService = $this->getServiceManager()->get(StorageInterface::SERVICE_ID);
        if (!$storageService instanceof ProxyStorage) {
            return \common_report_Report::createFailure('Wrong storage configuration');
        }
        $this->report = \common_report_Report::createInfo('Flushing temporary event storage');
        $num = $storageService->flush();
        $this->report->add(new \common_report_Report(\common_report_Report::TYPE_SUCCESS, $num . ' records have been sent to internal storage'));
        return $this->report;
    }
}
