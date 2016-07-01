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

use common_Logger;
use JsonSerializable;
use oat\oatbox\event\Event;
use oat\oatbox\service\ConfigurableService;
use oat\taoEventLog\model\storage\RdsStorage;

/**
 * Class LoggerService
 * @package oat\taoEventLog\model
 */
class LoggerService extends ConfigurableService
{
    const SERVICE_ID = 'taoEventLog/logger';

    /** @var StorageInterface */
    private $storage;

    /**
     * @param StorageInterface $storage
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param Event $event
     */
    public function logEvent(Event $event)
    {
        if (!is_subclass_of($event, JsonSerializable::class)) {
            common_Logger::d('Event %s should implements JsonSerializable interface for to be logged by EventLog extension');
            return;
        }

        // to be done
        $this->getStorage()->log();
    }

    /**
     * @return RdsStorage|StorageInterface
     */
    private function getStorage()
    {
        if (!isset($this->storage)) {
            $this->storage = new RdsStorage($this->getOption(RdsStorage::OPTION_PERSISTENCE));
        }

        return $this->storage;
    }
    
    public function searchInstances(array $params=[])
    {
        return $this->getStorage()->searchInstances($params);
    }
}
