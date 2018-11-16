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

namespace oat\taoEventLog\model\storage;

use common_persistence_Manager;
use common_persistence_Persistence;
use common_persistence_SqlPersistence;
use oat\oatbox\service\ConfigurableService;
use oat\taoEventLog\model\RdsStorageInterface;
use oat\taoEventLog\model\StorageInterface;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class AbstractRdsStorage
 * @package oat\taoEventLog\model\storage
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
abstract class AbstractRdsStorage extends ConfigurableService implements StorageInterface, RdsStorageInterface
{
    const OPTION_PERSISTENCE = 'persistence';
    const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    const ID = 'id';

    /**
     * Persistence for DB
     * @var common_persistence_Persistence
     */
    protected $persistence;

    /** @var string */
    protected $sql = '';

    /** @var array */
    protected $parameters = [];

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
        if (isset($options['group']) && in_array($options['group'], static::tableColumns())) {
            $queryBuilder->groupBy($options['group']);
        }

        foreach ($filters as $filter) {
            $this->addFilter($queryBuilder, $filter);
        }

        $sort = isset($options['sort']) ? $options['sort'] : '';
        $order = isset($options['order']) ? strtoupper($options['order']) : ' ASC';

        if (in_array($sort, static::tableColumns()) && in_array($order, ['ASC', 'DESC'])) {
            $queryBuilder->addOrderBy($sort, $order);
        }

        if ($sort !== self::ID) {
            $queryBuilder->addOrderBy(self::ID, 'DESC');
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
        $queryBuilder->select(self::ID);

        foreach ($filters as $filter) {
            $this->addFilter($queryBuilder, $filter);
        }
        if (isset($options['group']) && in_array($options['group'], static::tableColumns())) {
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

        if (!in_array($colName, static::tableColumns())) {
            return;
        }

        if (!in_array($operation, ['<', '>', '<>', '<=', '>=', '=', 'between', 'like', 'in'])) {
            return;
        }
        $params = [];
        if ($operation === 'between') {
            $condition = "$colName between ? AND ?";
            $params[] = $val;
            $params[] = $val2;
        } else if ($operation === 'like') {
            $condition = "lower($colName) $operation ?";
            $params[] = strtolower($val);
        } else if ($operation === 'in') {
            $condition = "$colName $operation (" . implode(',',array_fill(0, count($val),'?')).")";
            $params = array_values($val);
        } else {
            $condition = "$colName $operation ?";
            $params[] = $val;
        }

        $queryBuilder->andWhere($condition);

        $params = array_merge($queryBuilder->getParameters(), $params);
        $queryBuilder->setParameters($params);
    }

     /**
      * @return \Doctrine\DBAL\Query\QueryBuilder
      * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
      */
    private function getQueryBuilder()
    {
        return $this->getPersistence()->getPlatForm()->getQueryBuilder()->from($this->getTableName());
    }

     /**
      * @return common_persistence_SqlPersistence
      * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
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

     /**
      * @param array $filters
      * @return integer
      * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
      */
     public function delete(array $filters )
     {
         $qb = $this->getPersistence()->getPlatForm()->getQueryBuilder()->delete($this->getTableName());
         foreach ($filters as $filter){
             $this->addFilter($qb, $filter);
         }
         return $qb->execute();
     }

 }
