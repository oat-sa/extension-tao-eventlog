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

namespace oat\taoEventLog\model\event;


/**
 * Interface TinCanEvent
 * @package oat\taoEventLog\model\event
 */
interface TinCanEvent
{
    /**
     * @return \TinCan\Verb
     * @see https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Data.md#verb
     * @see https://registry.tincanapi.com/#home/verbs
     */
    public function getVerb();

    /**
     * @return \TinCan\Activity
     * @see https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Data.md#object
     */
    public function getActivity();

    /**
     * @return \TinCan\Context
     * @see https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Data.md#context
     */
    public function getContext();
}