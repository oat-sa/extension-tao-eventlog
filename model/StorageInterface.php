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

/**
 * Interface StorageInterface
 * @package oat\taoEventLog\model
 */
interface StorageInterface
{
    /** Fields */
    const ID = 'id';
    const EVENT_NAME = 'event_name';
    const ACTION = 'action';
    const USER_ID = 'user_id';
    const USER_ROLE = 'user_role';
    const OCCURRED = 'occurred';
    const PROPERTIES = 'properties'; // json

    /**
     * StorageInterface constructor.
     * @param string
     */
    public function __construct($param = '');

    /**
     * Creates new log record
     * @param string $eventName
     * @param string $currentAction
     * @param string $userIdentifier
     * @param string $userRole
     * @param string $occurred
     * @param array $data
     * @return mixed
     */
    public function log($eventName = '', $currentAction = '', $userIdentifier = '', $userRole = '', $occurred = '', $data = []);

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
