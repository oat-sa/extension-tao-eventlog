<?php
/**
 * Created by PhpStorm.
 * User: OnePoint
 * Date: 6/23/2017
 * Time: 3:19 PM
 */

namespace oat\taoEventLog\model;


interface RdsStorageInterface
{
    /**
     * Returns actual list of table columns
     * @return array
     */
    public static function tableColumns();

    /**
     * Install storage (create table).
     * @param \common_persistence_SqlPersistence $persistence
     * @return mixed
     */
    public static function install($persistence);

    /**
     * @return string
     */
    public function getTableName();
}
