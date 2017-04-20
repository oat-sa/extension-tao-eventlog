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

namespace oat\taoEventLog\model\storage;

use common_persistence_Manager;
use common_persistence_Persistence;
use common_persistence_SqlPersistence;
use DateTime;
use DateTimeImmutable;
use oat\oatbox\service\ConfigurableService;
use oat\taoEventLog\model\StorageInterface;

/**
 * Class RdsStorage
 * @package oat\taoEventLog\model\storage
 */
class RdsStorage extends ConfigurableService implements StorageInterface
{
    const OPTION_PERSISTENCE = 'persistence';
    const EVENT_LOG_TABLE_NAME = 'event_log';

    /**
     * Persistence for DB
     * @var common_persistence_Persistence
     */
    private $persistence;

    /** @var string */
    protected $sql = '';

    /** @var array */
    protected $parameters = [];

    /**
     * @param string $eventName
     * @param string $currentAction
     * @param string $userIdentifier
     * @param string $userRoles
     * @param string $occurred
     * @param array $data
     * @return bool
     */
    public function log($eventName = '', $currentAction = '', $userIdentifier = '', $userRoles = '', $occurred = '', $data = [])
    {
        $result = $this->getPersistence()->insert(
            self::EVENT_LOG_TABLE_NAME, [
                self::EVENT_LOG_EVENT_NAME => $eventName,
                self::EVENT_LOG_ACTION => $currentAction,
                self::EVENT_LOG_USER_ID => $userIdentifier,
                self::EVENT_LOG_USER_ROLES => $userRoles,
                self::EVENT_LOG_OCCURRED => $occurred,
                self::EVENT_LOG_PROPERTIES => json_encode($data),
            ]
        );

        return $result === 1;
    }

    /**
     * @param DateTimeImmutable $beforeDate
     * @return mixed
     */
    public function removeOldLogEntries(DateTimeImmutable $beforeDate)
    {
        $sql = "DELETE FROM " . self::EVENT_LOG_TABLE_NAME . " WHERE " . self::EVENT_LOG_OCCURRED . " <= ?";

        return $this->getPersistence()->query($sql, [$beforeDate->format(DateTime::ISO8601)]);
    }

    private function addSqlCondition(&$sql, $condition) {

        if (mb_strpos($sql, 'WHERE') === false) {
            $sql .= ' WHERE ';
        } else {
            $sql .= ' AND ';
        }

        $sql .= '(' . $condition . ')';
    }

    /**
     * @param array $params
     * @return array
     */
    public function search(array $params = [])
    {
        $this->sql = 'SELECT * FROM ' . self::EVENT_LOG_TABLE_NAME;
        $this->prepareQuery($params);

        $orderBy = isset($params['sortby']) ? $params['sortby'] : '';
        $orderDir = isset($params['sortorder']) ? strtoupper($params['sortorder']) : ' ASC';

        $this->sql .= ' ORDER BY ';
        $orderSep = '';

        if (in_array($orderBy, self::tableColumns()) && in_array($orderDir, ['ASC', 'DESC'])) {
            $this->sql .= $orderBy . ' ' . $orderDir;
            $orderSep = ', ';
        }

        if ($orderBy != 'id') {
            $this->sql .= $orderSep . 'id DESC';
        }
        
        $page = isset($params['page']) ? (intval($params['page']) - 1) : 0;
        $rows = isset($params['rows']) ? intval($params['rows']) : 25;

        if ($page < 0) {
            $page = 0;
        }

        $this->sql .= ' LIMIT ? OFFSET ?';
        $this->parameters[] = $rows;
        $this->parameters[] = $page * $rows;

        $stmt = $this->getPersistence()->query($this->sql, $this->parameters);
        
        $ret = [];
        $ret['data'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $ret['records'] = $this->count($params);

        return $ret;
    }

    /**
     * @param array $params
     * @return integer
     */
    public function count(array $params = [])
    {
        $this->sql = 'SELECT COUNT('.self::EVENT_LOG_ID.') FROM ' . self::EVENT_LOG_TABLE_NAME;
        $this->prepareQuery($params);
        $stmt = $this->getPersistence()->query($this->sql, $this->parameters);
        $total = current($stmt->fetchAll(\PDO::FETCH_ASSOC));
        return array_shift($total);
    }

    /**
     * @param array $params
     */
    protected function prepareQuery(array $params)
    {
        $this->parameters = [];
        if ((isset($params['periodStart']) && !empty($params['periodStart']))) {
            $this->addSqlCondition($this->sql, self::EVENT_LOG_OCCURRED . '>= ?');
            $this->parameters[] = $params['periodStart'];
        }

        if ((isset($params['periodEnd']) && !empty($params['periodEnd']))) {
            $this->addSqlCondition($this->sql, self::EVENT_LOG_OCCURRED . '<= ?');
            $this->parameters[] = $params['periodEnd'];
        }

        if (isset($params['filterquery']) && isset($params['filtercolumns']) && count($params['filtercolumns'])
            && in_array(current($params['filtercolumns']), self::tableColumns())) {

            $column = current($params['filtercolumns']);

            $this->addSqlCondition($this->sql, $column . " LIKE ?");

            $this->parameters[] = '%' . $params['filterquery'] . '%';

        } elseif (isset($params['filterquery']) && !empty($params['filterquery'])) {

            $condition = self::EVENT_LOG_EVENT_NAME . " LIKE ? OR "
                . self::EVENT_LOG_ACTION . " LIKE ? OR "
                . self::EVENT_LOG_USER_ID . " LIKE ? OR "
                . self::EVENT_LOG_USER_ROLES . " LIKE ? "
            ;

            $this->addSqlCondition($this->sql, $condition);

            for ($i = 0; $i < 4; $i++) {
                $this->parameters[] = '%' . $params['filterquery'] . '%';
            }
        }

        if (isset($params['till']) && $params['till'] instanceof DateTimeImmutable) {
            $this->addSqlCondition($this->sql, self::EVENT_LOG_OCCURRED . " >= ? ");
            $this->parameters[] = $params['till']->format(DateTime::ISO8601);
        }
    }

    /**
     * Returns actual list of table columns with log data
     * @return array
     */
    public static function tableColumns()
    {
        return [
            self::EVENT_LOG_ID,
            self::EVENT_LOG_USER_ID,
            self::EVENT_LOG_USER_ROLES,
            self::EVENT_LOG_EVENT_NAME,
            self::EVENT_LOG_ACTION,
            self::EVENT_LOG_OCCURRED,
            self::EVENT_LOG_PROPERTIES
        ];
    }

    /**
     * @return common_persistence_SqlPersistence
     */
    public function getPersistence()
    {
        $persistenceId = $this->getOption(self::OPTION_PERSISTENCE);
        if (is_null($this->persistence)) {
            $this->persistence = $this->getServiceManager()
                ->get(common_persistence_Manager::SERVICE_ID)
                ->getPersistenceById($persistenceId);
        }

        return $this->persistence;
    }

}
