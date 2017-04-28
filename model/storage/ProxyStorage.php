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

namespace oat\taoEventLog\model\storage;

use oat\oatbox\service\ConfigurableService;
use oat\taoEventLog\model\StorageInterface;
use oat\generis\model\OntologyAwareTrait;
use oat\taoEventLog\model\LogEntity;

/**
 * Class ProxyStorage
 *
 * Configuration example:
 * ```
 * use oat\taoEventLog\model\storage\ProxyStorage;
 * return new ProxyStorage([
 *   ProxyStorage::OPTION_PERSISTENCE => 'eventLogProxy',
 *   ProxyStorage::OPTION_INTERNAL_STORAGE => new oat\taoEventLog\model\storage\RdsStorage([
 *     'persistence' => 'default'
 *   ])
 * ]);
 *
 * @package oat\taoEventLog\model\storage
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class ProxyStorage extends ConfigurableService implements StorageInterface
{
    use OntologyAwareTrait;

    const OPTION_PERSISTENCE = 'persistence';
    const OPTION_INTERNAL_STORAGE = 'internal_storage';

    const LAST_ID_KEY = 'last_id';

    /** @var \common_persistence_KeyValuePersistence */
    private $persistence;

    /**
     * @param LogEntity $logEntity
     * @return bool
     */
    public function log(LogEntity $logEntity)
    {
        $id = $this->getLastId();
        $id++;
        $this->getPersistence()->set($id, serialize($logEntity));
        $this->getPersistence()->set(self::LAST_ID_KEY, $id);
        return true;
    }

    /**
     * @param array $logEntities
     * @return boolean
     */
    public function bulkLog(array $logEntities)
    {
        $id = $this->getLastId();
        foreach ($logEntities as $logEntity) {
            $id++;
            $this->getPersistence()->set($id, serialize($logEntity));
        }
        $this->getPersistence()->set(self::LAST_ID_KEY, $id);
        return true;
    }

    /**
     * @param array $filters
     * @param array $options
     * @return array
     */
    public function search(array $filters = [], array $options = [])
    {
        return $this->getInternalStorage()->search($filters, $options);
    }

    /**
     * @param array $filters
     * @param array $options
     * @return array
     */
    public function count(array $filters = [], array $options = [])
    {
        return $this->getInternalStorage()->count($filters, $options);
    }

    /**
     * Flush temporary storage
     */
    public function flush()
    {
        $lastId = $this->getLastId();
        $persistence = $this->getPersistence();
        $logEntities = [];
        for ($id = 0; $id <= $lastId; $id++) {
            if ($persistence->exists($id)) {
                $logEntities[] = unserialize($persistence->get($id));
            }
        }
        $this->getInternalStorage()->bulkLog($logEntities);
        $persistence->purge();
    }

    /**
     * @return \common_persistence_KeyValuePersistence
     * @throws \common_exception_InconsistentData
     */
    private function getPersistence()
    {
        if ($this->persistence === null) {
            $persistenceId = $this->getOption(self::OPTION_PERSISTENCE);
            $this->persistence = $this->getServiceManager()
                ->get(\common_persistence_Manager::SERVICE_ID)
                ->getPersistenceById($persistenceId);

            if (!$this->persistence instanceof \common_persistence_KeyValuePersistence) {
                throw new \common_exception_InconsistentData(
                    __(self::class . ' supports only common_persistence_KeyValuePersistence as persistence')
                );
            }
        }
        return $this->persistence;
    }

    /**
     * @return StorageInterface
     * @throws \common_exception_InconsistentData
     */
    private function getInternalStorage()
    {
        $storage = $this->getOption(self::OPTION_INTERNAL_STORAGE);
        $storage->setServiceManager($this->getServiceManager());
        if (!$storage instanceof StorageInterface) {
            throw new \common_exception_InconsistentData(
                __(self::class . ' supports only ' . StorageInterface::class .' as an internal storage')
            );
        }
        return $storage;
    }

    /**
     * @return int
     */
    private function getLastId()
    {
        $persistence = $this->getPersistence();
        if (!$persistence->exists(self::LAST_ID_KEY)) {
            $id = -1;
            $persistence->set(self::LAST_ID_KEY, $id);
        } else {
            $id = $this->getPersistence()->get(self::LAST_ID_KEY);
        }
        return (integer) $id;
    }
}
