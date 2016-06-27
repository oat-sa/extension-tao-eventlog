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
     * initialize the services
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * A possible entry point to tao
     */
    public function index() {
        $this->setData('author', 'Open Assessment Technologies SA');
        $this->setView('TaoEventLog/log.tpl');
    }

    /**
     * Load json data with results
     */
    public function search()
    {
        $results = ['data' => [
            [
                'user_id' => 1,
                'id' => 0, //event_id
                'name' => 'First User Name',
                'time' => date('Y-m-d H:i:s'),
                'event' => 'Move Item',
                'ip' => '',
                'ipv6' => '2001:0db8:11a3:09d7:1f34:8a2e:07a0:765d',
                'desc' => json_encode([
                    [
                        'delivery_execution' => '#delivery_execution_uri',
                        'delivery' => '#delivery_uri'
                    ]
                ], JSON_PRETTY_PRINT)
            ], [
                'user_id' => 2,
                'id' => 1,
                'ip' => '127.0.0.1',
                'ipv6' => '2001:0db8:11a3:09d7:1f34:8a2e:07a0:765d',
                'name' => 'Second User Name',
                'time' => date('Y-m-d H:i:s'),
                'event' => 'Log Out'

            ], [
                'id' => 2, 
                'user_id' => 'guest',
                'ip' => '127.0.0.1',
                'ipv6' => '',
                'name' => 'Guest',
                'time' => date('Y-m-d H:i:s'),
                'event' => 'Log Error',
                'desc' => ['Failure password']
            ]
        ], 'page' => 1, 'total' => '3', 'records' => 23];

        $this->returnJson($results, 200);
    }
}
