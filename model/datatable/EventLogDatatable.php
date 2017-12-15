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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA ;
 *
 */

namespace oat\taoEventLog\model\datatable;

use oat\tao\model\datatable\implementation\DatatableRequest;
use oat\tao\model\datatable\DatatablePayload;
use oat\oatbox\service\ServiceManager;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use oat\taoEventLog\model\eventLog\LoggerService;

/**
 * Class DeliveriesActivityDatatable
 * @package oat\taoEventLog\model\datatable
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class EventLogDatatable implements DatatablePayload, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /** @var DatatableRequest */
    protected $request;

    /** @var LoggerService */
    protected $loggerService;

    /**
     * EventLogDatatable constructor.
     */
    public function __construct()
    {
        $this->setServiceLocator(ServiceManager::getServiceManager());
        $request = DatatableRequest::fromGlobals();
        $this->request = $request;
        $this->loggerService =  $this->getServiceLocator()->get(LoggerService::SERVICE_ID);
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        $filters = $this->getFilters();
        $results = [
            'data' => $this->loggerService->searchInstances($filters, [
                'limit' => $this->request->getRows(),
                'offset' => ($this->request->getPage() - 1) * $this->request->getRows(),
                'sort' => $this->request->getSortBy(),
                'order' => $this->request->getSortOrder(),
            ]),
            'records' => $this->loggerService->count($filters),
        ];

        $result = $this->doPostProcessing($results);

        return $result;
    }

    /**
     * @param array $results
     * @return array
     */
    protected function doPostProcessing(array $results)
    {
        // prettify data
        array_walk($results['data'], function (&$row) {

            $date = new \DateTime($row['occurred'], new \DateTimeZone('UTC'));
            $row['occurred'] = \tao_helpers_Date::displayeDate($date->getTimestamp());

            $row['raw'] = array_map(null, $row);

            $row['id'] = 'identifier-' . $row['id'];

            $eventNameChunks = explode('\\', $row['event_name']);
            $row['event_name'] = array_pop($eventNameChunks);
            $row['user_id'] = \tao_helpers_Uri::getUniqueId($row['user_id']) ?: $row['user_id'];

            $roles = explode(',', $row['user_roles']);
            foreach ($roles as &$role) {
                $role =  \tao_helpers_Uri::getUniqueId($role);
            }
            $row['user_roles'] = join(', ', $roles);
        });

        $payload = [
            'data' => $results['data'],
            'page' => (integer) $this->request->getPage(),
            'records' => (integer) count($results['data']),
            'total' => ceil($results['records'] / $this->request->getRows()),
        ];
        return $payload;
    }

    /**
     * @return array
     */
    protected function getFilters()
    {
        $params = \Context::getInstance()->getRequest()->getParameters();
        $filters = [];

        foreach ($params as $key => $val) {
            if (empty($params[$key])) {
                unset($params[$key]);
            }
        }

        /** @var \common_session_Session $session */
        $session = \common_session_SessionManager::getSession();

        $timeZone = $session->getTimeZone();
        $utc = new \DateTimeZone('UTC');

        if (isset($params['periodStart']) && !empty($params['periodStart'])) {
            $params['periodStart'] = (new \DateTime($params['periodStart'], new \DateTimeZone($timeZone)))->setTimezone($utc)->format(\DateTime::ISO8601);
        }

        if (isset($params['periodEnd']) && !empty($params['periodEnd'])) {
            $params['periodEnd'] = (new \DateTime($params['periodEnd'], new \DateTimeZone($timeZone)))->setTimezone($utc)->format(\DateTime::ISO8601);
        }

        if (isset($params['periodStart']) && isset($params['periodEnd'])) {
            $filters[] = ['occurred', 'between', $params['periodStart'], $params['periodEnd']];
        } else if (isset($params['periodStart'])) {
            $filters[] = ['occurred', '>', $params['periodStart']];
        } else if (isset($params['periodEnd'])) {
            $filters[] = ['occurred', '<', $params['periodEnd']];
        }

        if (isset($params['filtercolumns']) && is_array($params['filtercolumns'])) {
            foreach ($params['filtercolumns'] as $col => $val) {
                $filters[] = [$col, 'like', '%' . $val . '%'];
            }
        }

        return $filters;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getPayload();
    }
}