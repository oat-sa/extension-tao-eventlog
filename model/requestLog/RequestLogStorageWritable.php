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

use Psr\Http\Message\RequestInterface;
use oat\oatbox\user\User;

/**
 * Interface RequestLogStorage
 * @package oat\taoEventLog\model\requestLog
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
interface RequestLogStorageWritable
{

    /**
     * Log request data.
     *
     * @param RequestInterface $request
     * @param User $user
     * @return boolean
     */
    public function log(RequestInterface $request, User $user);

    /**
     * Log bunch of events at once
     *
     * @param array $data
     * @return mixed
     */
    public function bulkLog(array $data);
}