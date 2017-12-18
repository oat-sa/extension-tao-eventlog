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

namespace oat\taoEventLog\model\requestLog\fluentd;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\ServerRequest;
use oat\oatbox\user\User;
use oat\taoEventLog\model\requestLog\AbstractRequestLogStorage;

/**
 * Class FluentdRequestLogStorage
 * @package oat\taoEventLog\model\requestLog\fluentd
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class FluentdRequestLogStorage extends AbstractRequestLogStorage
{
    const CONST_ENDPOINT = 'endpoint';
    const CONST_TAG = 'tag';

    const COLUMN_USER_ID = self::USER_ID;
    const COLUMN_USER_ROLES = self::USER_ROLES;
    const COLUMN_ACTION = self::ACTION;
    const COLUMN_EVENT_TIME = self::EVENT_TIME;
    const COLUMN_DETAILS = self::DETAILS;

    /**
     * @inheritdoc
     */
    public function log(Request $request = null, User $user = null)
    {
        if ($request === null) {
            $request = ServerRequest::fromGlobals();
        }

        if ($user === null) {
            $user = \common_session_SessionManager::getSession()->getUser();
        }

        $userId = $user->getIdentifier();
        if ($userId === null) {
            $userId = get_class($user);
        }

        $data = [
            self::USER_ID => $userId,
            self::USER_ROLES => ','. implode(',', $user->getRoles()). ',',
            self::COLUMN_ACTION => $request->getUri(),
            self::COLUMN_EVENT_TIME => microtime(true),
            self::COLUMN_DETAILS => json_encode([
                'method' => $request->getMethod(),
            ]),
        ];
        $this->getPersistence()->insert(self::TABLE_NAME, $data);
    }
}