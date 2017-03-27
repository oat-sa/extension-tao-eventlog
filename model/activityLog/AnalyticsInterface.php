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

namespace oat\taoEventLog\model\activityLog;

use \DateTime;
use \DateInterval;

/**
 * Interface AnalyticsInterface
 * @package oat\taoEventLog\model\activityLog
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
interface AnalyticsInterface
{
    /**
     * Get analytics for period of time
     *
     * @param array $filters filters by user id, url, role etc.
     * @param DateTime|null $since
     * @param DateTime|null $until
     * @param DateInterval|null $interval grouping interval
     * @return mixed
     */
    public function getAnalytics(array $filters = [], DateTime $since = null, DateTime $until = null, DateInterval $interval = null);
}