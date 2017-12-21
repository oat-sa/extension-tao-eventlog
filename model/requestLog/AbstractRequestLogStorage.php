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
use oat\oatbox\Configurable;
use oat\oatbox\service\ServiceManagerAwareInterface;
use oat\oatbox\service\ServiceManagerAwareTrait;

/**
 * Class AbstractRequestLogStorage
 * @package oat\taoEventLog\model\requestLog
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
abstract class AbstractRequestLogStorage extends Configurable implements RequestLogStorageWritable, ServiceManagerAwareInterface
{

    use ServiceManagerAwareTrait;

    /**
     * Prepare data to log
     *
     * @param RequestInterface $request
     * @param User $user
     * @return array
     */
    protected function prepareData(RequestInterface $request, User $user)
    {
        $userId = $user->getIdentifier();
        if ($userId === null) {
            $userId = get_class($user);
        }

        return [
            RequestLogService::USER_ID => $userId,
            RequestLogService::USER_ROLES => ','. implode(',', $user->getRoles()). ',',
            RequestLogService::ACTION => $request->getUri()->getPath(),
            RequestLogService::EVENT_TIME => microtime(true),
            RequestLogService::DETAILS => json_encode([
                'method' => $request->getMethod(),
                'query' => $request->getUri()->getQuery(),
            ]),
        ];
    }
}