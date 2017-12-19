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

namespace oat\taoEventLog\model\requestLog\proxy;

use oat\taoEventLog\model\requestLog\AbstractRequestLogStorage;
use GuzzleHttp\Psr7\Request;
use oat\oatbox\user\User;
use oat\taoEventLog\model\requestLog\RequestLogStorageReadable;
use oat\taoEventLog\model\requestLog\RequestLogStorageWritable;

/**
 * Class ProxyStorage
 *
 * Configuration example:
 * ```
 * use oat\taoEventLog\model\requestLog\rds\RdsRequestLogStorage;
 * use oat\taoEventLog\model\requestLog\RequestLogService;
 * use oat\taoEventLog\model\requestLog\proxy\ProxyStorage;
 *
 * return new RequestLogService([
 *      RequestLogService::OPTION_STORAGE => ProxyStorage::class,
 *      RequestLogService::OPTION_STORAGE_PARAMETERS => [
 *          ProxyStorage::OPTION_PERSISTENCE => 'cache',
 *          ProxyStorage::OPTION_KV_STORAGE_KEY => 'request_log_proxy',
 *          ProxyStorage::OPTION_MAX_STORAGE_SIZE => 100,
 *          ProxyStorage::OPTION_INTERNAL_STORAGE => RdsRequestLogStorage::class,
 *          ProxyStorage::OPTION_INTERNAL_STORAGE_PARAMS => [
 *          RdsRequestLogStorage::OPTION_PERSISTENCE => 'default'
 *      ],
 *   ],
 * ]);
 *
 * NOTE: internal storage is not transaction safe. During high load some data may be missed
 *
 * @package oat\taoEventLog\model\storage
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class ProxyStorage extends AbstractRequestLogStorage implements RequestLogStorageReadable
{
    const OPTION_PERSISTENCE = 'persistence';
    const OPTION_INTERNAL_STORAGE = 'internal_storage';
    const OPTION_INTERNAL_STORAGE_PARAMS = 'internal_storage_params';
    const OPTION_KV_STORAGE_KEY = 'kv_storage_key';
    const OPTION_MAX_STORAGE_SIZE = 'max_storage_size';

    const LAST_ID_KEY = 'last_id';

    /** @var \common_persistence_KeyValuePersistence */
    private $persistence;

    /** @var RequestLogStorageReadable|RequestLogStorageWritable */
    private $internalStorage;

    /**
     * @inheritdoc
     */
    public function log(Request $request, User $user)
    {
        $data = $this->prepareData($request, $user);
        $persistence = $this->getPersistence();
        $cachedData = unserialize($persistence->get($this->getKey()));
        $cachedData[] = $data;
        $returnValue = $persistence->set($this->getKey(), serialize($cachedData));
        if (count($cachedData) > $this->getMaxSize()) {
            $this->flush();
        }
        return $returnValue;
    }

    /**
     * @inheritdoc
     */
    public function bulkLog(array $data)
    {
        $persistence = $this->getPersistence();
        $cachedData = unserialize($persistence->get($this->getKey())) + $data;
        $returnValue = $persistence->set($this->getKey(), serialize($cachedData));
        if (count($cachedData) > $this->getMaxSize()) {
            $this->flush();
        }
        return $returnValue;
    }

    /**
     * @param array $filters
     * @param array $options
     * @return array
     * @throws
     */
    public function find(array $filters = [], array $options = [])
    {
        return $this->getInternalStorage()->find($filters, $options);
    }

    /**
     * @param array $filters
     * @param array $options
     * @return array
     * @throws
     */
    public function count(array $filters = [], array $options = [])
    {
        return $this->getInternalStorage()->count($filters, $options);
    }

    /**
     * Flush temporary storage
     */
    protected function flush()
    {
        $persistence = $this->getPersistence();
        $data = unserialize($persistence->get($this->getKey()));
        $persistence->set($this->getKey(), serialize([]));
        return $this->getInternalStorage()->bulkLog($data);
    }

    public static function install()
    {
        // nothing to do
    }

    /**
     * @return \common_persistence_KeyValuePersistence
     * @throws
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
     * @return RequestLogStorageWritable|RequestLogStorageReadable
     * @throws
     */
    private function getInternalStorage()
    {
        if ($this->internalStorage === null) {
            $storageClass = $this->getOption(self::OPTION_INTERNAL_STORAGE);
            if (!class_exists($storageClass)) {
                throw new RequestLogException('Storage class does not exist');
            }
            $storageParams = $this->getOption(self::OPTION_INTERNAL_STORAGE_PARAMS)?:[];
            $this->internalStorage = new $storageClass($storageParams);
            $this->getServiceManager()->propagate($this->internalStorage);
        }
        return $this->internalStorage;
    }

    /**
     * @return string
     */
    private function getKey()
    {
        return $this->getOption(self::OPTION_KV_STORAGE_KEY);
    }

    /**
     * @return integer
     */
    private function getMaxSize()
    {
        return $this->getOption(self::OPTION_MAX_STORAGE_SIZE);
    }
}