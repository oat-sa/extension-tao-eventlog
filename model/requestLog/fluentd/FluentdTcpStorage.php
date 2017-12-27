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

use oat\taoEventLog\model\requestLog\AbstractRequestLogStorage;
use oat\oatbox\user\User;
use Psr\Http\Message\RequestInterface;

/**
 * A fluentd tcp forwarding implementation
 *
 * @package oat\taoEventLog\model\storage
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class FluentdTcpStorage extends AbstractRequestLogStorage
{
    const OPTION_HOST  = 'host';
    const OPTION_PORT  = 'port';
    const OPTION_DELIMITER = 'delimiter';

    /**
     * @var resource
     */
    private $resource;

    /**
     * Connect to the server and return the open socket
     *
     * @return resource
     * @throws RequestLogException
     */
    public function getSocket()
    {
        if (is_null($this->resource)) {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($socket === false) {
                throw new RequestLogException('Unable to open TCP socket: '.socket_strerror(socket_last_error()));
            }
            $success = socket_connect($socket, $this->getOption(self::OPTION_HOST), $this->getOption(self::OPTION_PORT));
            if ($success === false) {
                throw new RequestLogException('Unable to connect to host: '.socket_strerror(socket_last_error()));
            }
            $this->resource = $socket;
        }
        return $this->resource;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoEventLog\model\requestLog\RequestLogStorageWritable::log()
     */
    public function log(RequestInterface $request, User $user)
    {
        $message = json_encode($this->prepareData($request, $user));
        $message .= $this->hasOption(self::OPTION_DELIMITER) ? $this->getOption(self::OPTION_DELIMITER) : '';
        while (!empty($message))
        {
            $send = socket_write($this->getSocket(),$message, strlen($message));
            if ($send != strlen($message)) {
                throw new RequestLogException('Unable to send msg: '.socket_strerror(socket_last_error()));
            }
            $message = substr($message, $send);
        }
    }
}