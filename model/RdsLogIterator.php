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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoEventLog\model;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class RdsLogIterator
 * @package oat\taoEventLog\model\requestLog\rds
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RdsLogIterator implements \Iterator
{

    /** @var QueryBuilder */
    protected $queryBuilder;

    /** @var \common_persistence_SqlPersistence */
    protected $persistence;

    /** @var array */
    protected $current;

    /** @var int */
    protected $initialLimit;

    /** @var int */
    protected $firstResult;

    /** @var int */
    protected $currentKey;

    /**
     * RdsRequestLogIterator constructor.
     * @param \common_persistence_SqlPersistence $persistence
     * @param QueryBuilder $queryBuilder
     */
    public function __construct(\common_persistence_SqlPersistence $persistence, QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
        $this->persistence = $persistence;
        $this->initialLimit = $queryBuilder->getMaxResults();
        if ($this->initialLimit === null) {
            $this->initialLimit = PHP_INT_MAX;
        }
        $this->firstResult = $queryBuilder->getFirstResult();
        if ($this->firstResult === null) {
            $this->firstResult = 0;
        }
        $this->queryBuilder->setMaxResults(1);
        $this->rewind();
    }

    /**
     *
     */
    public function current()
    {
        return $this->current;
    }

    public function next()
    {
        if ($this->valid()) {
            $this->queryBuilder->setFirstResult($this->currentKey);
            $sql = $this->queryBuilder->getSQL();
            $params = $this->queryBuilder->getParameters();
            $stmt = $this->persistence->query($sql, $params);
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (empty($data)) {
                $this->current = null;
            } else {
                $this->current = $data;
            }
            $this->currentKey++;
        } else {
            $this->current = null;
        }
    }

    public function key()
    {
        return $this->currentKey - 1;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        if ($this->currentKey === $this->firstResult) {
            //initial state
            return true;
        }

        return $this->current !== null && ($this->currentKey + $this->firstResult) < $this->initialLimit;
    }

    public function rewind()
    {
        $this->currentKey = $this->firstResult;
        $this->next();
    }
}