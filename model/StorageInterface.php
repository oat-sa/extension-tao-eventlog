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

namespace oat\taoEventLog\model;


interface StorageInterface
{
    /** Fields */
    const ID = 'id';
    const USER_ID = 'user_id';
    const EVENT = 'event';
    const TIME = 'action_time';
    const IP = 'ip';
    const IPv6 = 'ipv6';
    const DESCRIPTION = 'desc';

    /**
     * StorageInterface constructor.
     * @param string
     */
    public function __construct($param = '');

    /**
     * Create new log record
     *
     * @param string $testTaker
     * @param string $delivery
     * @param string $deliveryExecution
     * @param string $event
     * @return bool
     */
    public function event($testTaker = '', $delivery = '', $deliveryExecution = '', $event = '');

    /**
     * Create storage
     * @return string (table name or file path)
     */
    public function createStorage();

    /**
     * Destroy storage
     * @return bool
     */
    public function dropStorage();

}
