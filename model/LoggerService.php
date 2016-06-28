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

use oat\oatbox\service\ConfigurableService;
use oat\taoEventLog\model\storage\RdsStorage;

class LoggerService extends ConfigurableService
{
    const SERVICE_ID = 'taoEventLog/logger';

    /**
     * @var StorageInterface
     */
    private $storage;

    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function logEvent($testTaker='', $delivery='', $deliveryExecution='', $event='')
    {
        $this->getStorage()->logEvent($testTaker, $delivery, $deliveryExecution, $event);
    }

    private function getStorage()
    {
        if (!isset($this->storage)) {
            $this->storage = new RdsStorage($this->getOption(RdsStorage::OPTION_PERSISTENCE));
        }

        return $this->storage;
    }

}
