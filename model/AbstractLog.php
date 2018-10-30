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
 * Copyright (c) 2017  (original work) Open Assessment Technologies SA;
 * 
 */

namespace oat\taoEventLog\model;

use oat\oatbox\event\Event;
use oat\oatbox\service\ConfigurableService;

/**
 * Class AbstractLog
 * @package oat\taoEventLog\model
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
abstract class AbstractLog extends ConfigurableService
{
    const OPTION_STORAGE = 'storage';

    /**
     * @param Event $event
     */
    abstract public function log(Event $event);

    /**
     * @param array $filters
     * @param array $options
     * @return array
     */
    public function search(array $filters = [], array $options = [])
    {
        return $this->getStorage()->search($filters, $options);
    }

    public function delete(array $filters){
        return $this->getStorage()->delete($filters);
    }

    /**
     * Count records in log which are meet the search criteria
     * @param array $filters
     * @param array $options
     * @return integer
     */
    public function count(array $filters = [], array $options = [])
    {
        return $this->getStorage()->count($filters, $options);
    }

    /**
     * @return StorageInterface
     */
    protected function getStorage()
    {
        $storage = $this->getOption(self::OPTION_STORAGE);
        return $storage;
    }
}
