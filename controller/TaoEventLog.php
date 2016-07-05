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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *               
 * 
 */

namespace oat\taoEventLog\controller;
use oat\taoEventLog\model\LoggerService;

/**
 * Sample controller
 *
 * @author Open Assessment Technologies SA
 * @package taoEventLog
 * @license GPL-2.0
 *
 */
class TaoEventLog extends \tao_actions_CommonModule {

    /**
     * A possible entry point to tao
     */
    public function index() {
        $this->setView('TaoEventLog/log.tpl');
    }

    /**
     * Load json data with results
     */
    public function search()
    {        
        $loggerService = $this->getServiceManager()->get(LoggerService::SERVICE_ID);
        $results = $loggerService->searchInstances($this->getRequestParameters());

        array_walk($results['data'], function (&$row) {
            $row['id'] = 'identifier-' . $row['id'];
        });
        
        $results['page'] = $this->getRequestParameter('page');
        $results['total'] = ceil($results['records'] / $this->getRequestParameter('rows'));
        
        $this->returnJson($results, 200);
    }
}
