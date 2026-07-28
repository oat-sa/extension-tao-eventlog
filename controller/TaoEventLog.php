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

use core_kernel_classes_Resource;
use Exception;
use oat\tao\model\http\HttpJsonResponseTrait;
use oat\taoEventLog\model\datatable\EventLogDatatable;
use oat\taoEventLog\model\export\implementation\LogEntryCsvStdOutExporter;
use oat\taoEventLog\model\export\implementation\LogEntryRepository;
use oat\taoEventLog\model\FrontendAction\Service\FrontendActionEventLogger;
use tao_actions_CommonModule;

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
    use HttpJsonResponseTrait;

    /**
     * A possible entry point to tao
     */
    public function index()
    {
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
     *
     * @throws \Exception
     */
    public function export()
    {
        $delimiter   = $this->getParameter('field_delimiter', ',');
        $enclosure   = $this->getParameter('field_encloser', '"');
        $sortBy      = $this->getParameter('sortby', '');
        $sortOrder   = $this->getParameter('sortorder', '');

        $exportParameters = [];

        if ($this->hasRequestParameter('filtercolumns')) {
            $parameters = $this->getRequestParameter('filtercolumns');

            if (is_array($parameters)) {
                $exportParameters = $parameters;
            }
        }

        $csvExporter = new LogEntryCsvStdOutExporter(
            new LogEntryRepository($exportParameters, $sortBy, $sortOrder),
            $delimiter,
            $enclosure
        );

        setcookie('fileDownload', 'true', 0, '/');
        $csvExporter->export();
    }

    public function logFrontendAction(): void
    {
        try {
            $request = $this->getPsrRequest();
            if ($request->getMethod() !== 'POST') {
                $this->setErrorJsonResponse('Method not allowed', 0, [], 405);
                return;
            }

            $this->validateCsrf();

            $requestParams = $request->getParsedBody();
            if (!is_array($requestParams)) {
                $requestParams = [];
            }

            $action = (string) ($requestParams['action'] ?? '');
            $resourceUri = (string) ($requestParams['resourceUri'] ?? '');

            if ($action !== '' && $resourceUri !== '') {
                $this->getFrontendActionEventLogger()->logAction(
                    $action,
                    new core_kernel_classes_Resource($resourceUri)
                );
            }

            $this->setSuccessJsonResponse(['success' => true]);
        } catch (Exception $exception) {
            $this->setErrorJsonResponse($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return string
     */
    protected function getParameter($name, $defaultValue)
    {
        return $this->hasRequestParameter($name)
            ? html_entity_decode($this->getRequestParameter($name))
            : $defaultValue;
    }

    private function getFrontendActionEventLogger(): FrontendActionEventLogger
    {
        return $this->getPsrContainer()->get(FrontendActionEventLogger::class);
    }
}
