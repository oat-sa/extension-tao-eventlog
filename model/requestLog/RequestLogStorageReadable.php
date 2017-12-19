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

namespace oat\taoEventLog\model\requestLog;

/**
 * Interface RequestLogStorage
 * @package oat\taoEventLog\model\requestLog
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
interface RequestLogStorageReadable extends RequestLogStorageWritable
{
    /**
     * Find user requests.
     *
     * Result example:
     * ```
     * [
     *     [...],
     *     [
     *         'user_id' => 'http://sample/first.rdf#i1490617729993174',
     *         'user_role' => 'http://www.tao.lu/Ontologies/TAO.rdf#BackOfficeRole,http://www.tao.lu/Ontologies/TAOLTI.rdf#LtiDeliveryProviderManagerRole',
     *         'action' => '/tao/Main/index?structure=settings&ext=tao&section=settings_ext_mng',
     *         'event_time' => '1490617792.5479',
     *         'details' => '"{\"login\":\"proctor\"}"', //json encoded additional data
     *     ],
     *     [...],
     * ]
     * ```
     *
     * Filters parameter example:
     * ```
     * [
     *   ['user_id', 'in', ['http://sample/first.rdf#i1490617729993174', 'http://sample/first.rdf#i1490617729993174'],
     *   ['event_time', 'between', '1490703795.3624', '1490704796.2467'],
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
     *      'group' => 'user_id,
     * ]
     * ```
     * @param array $filters filters by user id, url, role etc.
     * @param array $options
     * @return \Iterator
     */
    public function find(array $filters = [], array $options = []);

    /**
     * Count number of records by given search criteria
     * Options parameter example:
     * ```
     * [
     *      'group' => 'user_id,
     * ]
     * ```
     *
     * @param array $filters @see self::find() description.
     * @param array $options
     * @return integer
     */
    public function count(array $filters = [], array $options = []);
}