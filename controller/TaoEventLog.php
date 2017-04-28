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


use oat\tao\model\export\implementation\CsvExporter;
use oat\taoEventLog\model\export\implementation\LogEntryCsvExporter;
use oat\taoEventLog\model\LoggerService;
use tao_actions_CommonModule;
use oat\taoEventLog\model\datatable\EventLogDatatable;

/**
 * Sample controller
 *
 * @author Open Assessment Technologies SA
 * @package taoEventLog
 * @license GPL-2.0
 *
 */
class TaoEventLog extends tao_actions_CommonModule
{
    /** @var LoggerService */
    private $loggerService;

    /**
     * TaoEventLog constructor.
     */
    public function __construct()
    {
        $this->loggerService = $this->getServiceManager()->get(LoggerService::SERVICE_ID);
    }

    /**
     * A possible entry point to tao
     */
    public function index() {
        $this->setView('TaoEventLog/log.tpl');
    }

    /**
     * Load json data with results
     * dates for GUI should be in user time zone
     */
    public function search()
    {
        $this->returnJson(new EventLogDatatable());
    }

    /**
     * Export log entries from database to csv file
     * dates should be in UTC
     */
    public function export()
    {
        $delimiter = $this->hasRequestParameter('field_delimiter') ? html_entity_decode($this->getRequestParameter('field_delimiter')) : ',';
        $enclosure = $this->hasRequestParameter('field_encloser') ? html_entity_decode($this->getRequestParameter('field_encloser')) : '"';
        $columnNames = $this->hasRequestParameter('first_row_column_names');
        
        if (!$exported = (new LogEntryCsvExporter())->export()) {

            return $this->returnJson(['message' => 'Not found exportable log entries. Please, check export configuration.'], 404);
        }

        $csvExporter = new CsvExporter($exported);
        setcookie('fileDownload', 'true', 0, '/');
        $csvExporter->export($columnNames, true, $delimiter, $enclosure);
    }
}
