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
 * Copyright (c) 2017  (original work) Open Assessment Technologies SA;
 */


namespace oat\taoEventLog\model;

use oat\oatbox\event\Event;
use oat\oatbox\user\User;
use oat\dtms\DateTime;

/**
 * Class LogEntity
 * @package oat\taoEventLog\model
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class LogEntity
{
    /** @var Event  */
    protected $event;

    /** @var string  */
    protected $action;

    /** @var User  */
    protected $user;

    /** @var DateTime  */
    protected $time;

    /** @var array  */
    protected $data;

    /**
     * @param Event $event
     * @param string $action
     * @param User $user
     * @param DateTime $time
     * @param array $data
     */
    public function __construct(Event $event, $action, User $user, DateTime $time, $data = [])
    {
        $this->event = $event;
        $this->action = $action;
        $this->user = $user;
        $this->time = $time;
        $this->data = $data;
    }

    /**
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return DateTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}