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
use DateTimeImmutable;
use oat\oatbox\service\ConfigurableService;
use oat\taoEventLog\model\StorageInterface;
use Doctrine\DBAL\Query\QueryBuilder;
use oat\oatbox\event\Event;
use oat\oatbox\user\User;
use oat\dtms\DateTime;

/**
 * Class RdsStorage
 * @package oat\taoEventLog\model\storage
 */
class RdsStorage extends ConfigurableService implements StorageInterface
{
    const OPTION_PERSISTENCE = 'persistence';
    const EVENT_LOG_TABLE_NAME = 'event_log';
    const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

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
     * @param Event $event
     * @param string $currentAction
     * @param User $user
     * @param DateTime $occurred
     * @param array $data
     * @return bool
     */
    public function log(Event $event, $currentAction, User $user, DateTime $occurred, $data = [])
    {
        $result = $this->getPersistence()->insert(
            self::EVENT_LOG_TABLE_NAME, [
                self::EVENT_LOG_EVENT_NAME => $event->getName(),
                self::EVENT_LOG_ACTION => $currentAction,
                self::EVENT_LOG_USER_ID => $user->getIdentifier(),
                self::EVENT_LOG_USER_ROLES => join(',', $user->getRoles()),
                self::EVENT_LOG_OCCURRED => $occurred->format(self::DATE_TIME_FORMAT),
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

        return $this->getPersistence()->query($sql, [$beforeDate->format(self::DATE_TIME_FORMAT)]);
    }

    /**
     * @param array $params
     * @deprecated use $this->search() instead
     * @return array
     */
    public function searchInstances(array $params = [])
    {
        return $this->search($params);
    }

    /**
     *
     * Filters parameter example:
     * ```
     * [
     *   ['user_id', 'in', ['http://sample/first.rdf#i1490617729993174', 'http://sample/first.rdf#i1490617729993174'],
     *   ['occurred', 'between', '2017-04-13 15:29:21', '2017-04-14 15:29:21'],
     *   ['action', '=', '/tao/Main/login'],
     * ]
     * ```
     * Available operations: `<`, `>`, `<>`, `<=`, `>=`, `=`, `between`, `like`
     *
     * Options parameter example:
     * ```
     * [
     *      'limit' => 100,
     *      'offset' => 200,
     *      'sort' => 'occurred',
     *      'order' => 'ASC',
     *      'group' => 'user_id,
     * ]
     * ```
     *
     *
     * @param array $filters
     * @param array $options
     * @return array
     * @todo return \Iterator instead of array
     */
    public function search(array $filters = [], array $options = [])
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select('*');
        if (isset($options['limit'])) {
            $queryBuilder->setMaxResults(intval($options['limit']));
        }
        if (isset($options['offset'])) {
            $queryBuilder->setFirstResult(intval($options['offset']));
        }
        if (isset($options['group']) && in_array($options['group'], self::tableColumns())) {
            $queryBuilder->groupBy($options['group']);
        }

        foreach ($filters as $filter) {
            $this->addFilter($queryBuilder, $filter);
        }

        $sort = isset($options['sort']) ? $options['sort'] : '';
        $order = isset($options['order']) ? strtoupper($options['order']) : ' ASC';

        if (in_array($sort, self::tableColumns()) && in_array($order, ['ASC', 'DESC'])) {
            $queryBuilder->addOrderBy($sort, $order);
        }

        if ($sort !== 'id') {
            $queryBuilder->addOrderBy('id', 'DESC');
        }


        $sql = $queryBuilder->getSQL();
        $params = $queryBuilder->getParameters();
        $stmt = $this->getPersistence()->query($sql, $params);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $data;
    }

    /**
     * @param array $filters
     * @param array $options
     * @return integer
     */
    public function count(array $filters = [], array $options = [])
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select(self::EVENT_LOG_USER_ID);

        foreach ($filters as $filter) {
            $this->addFilter($queryBuilder, $filter);
        }
        if (isset($options['group']) && in_array($options['group'], self::tableColumns())) {
            $queryBuilder->select($options['group']);
            $queryBuilder->groupBy($options['group']);
        }

        $stmt = $this->getPersistence()->query(
            'SELECT count(*) as count FROM (' .$queryBuilder->getSQL() . ') as group_q', $queryBuilder->getParameters());
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        return intval($data['count']);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $filter
     */
    private function addFilter(QueryBuilder $queryBuilder, array $filter)
    {
        $colName = strtolower($filter[0]);
        $operation = strtolower($filter[1]);
        $val = $filter[2];
        $val2 = isset($filter[3]) ? $filter[3] : null;

        if (!in_array($colName, $this->tableColumns())) {
            return;
        }

        if (!in_array($operation, ['<', '>', '<>', '<=', '>=', '=', 'between', 'like'])) {
            return;
        }
        $params = [];
        if ($operation === 'between') {
            $condition = "r.$colName between ? AND ?";
            $params[] = $val;
            $params[] = $val2;
        } else if ($operation === 'like') {
            $condition = "lower(r.$colName) $operation ?";
            $params[] = strtolower($val);
        } else {
            $condition = "r.$colName $operation ?";
            $params[] = $val;
        }

        $queryBuilder->andWhere($condition);

        $params = array_merge($queryBuilder->getParameters(), $params);
        $queryBuilder->setParameters($params);
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private function getQueryBuilder()
    {
        return $this->getPersistence()->getPlatForm()->getQueryBuilder()->from(self::EVENT_LOG_TABLE_NAME, 'r');
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
