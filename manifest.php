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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 */

return array(
    'name' => 'taoEventLog',
    'label' => 'Test-taker Event Logging',
    'description' => 'The event logging system that catches and logs all actions of test-takers',
    'license' => 'GPL-2.0',
    'version' => '2.1.3',
    'author' => 'Open Assessment Technologies SA',
    'requires' => array(
        'generis'        => '>=3.25.0',
        'tao'            => '>=21.0.0',
        'taoDeliveryRdf' => '*',
        'funcAcl'        => '*',
        'taoTests'       => '*',
        'taoTestTaker'   => '*',
        'taoItems'       => '*',
        'taoQtiItem'     => '*',
        'taoQtiTest'     => '*',
    ),
    'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#taoEventLogManager',
    'acl' => array(
        array('grant', 'http://www.tao.lu/Ontologies/generis.rdf#taoEventLogManager', array('ext' => 'taoEventLog')),
    ),
    'update' => 'oat\\taoEventLog\\scripts\\update\\Updater',
    'install' => [
        'php' => [
            \oat\taoEventLog\scripts\install\RegisterRdsStorage::class,
            \oat\taoEventLog\scripts\install\RegisterLoggerService::class,
            \oat\taoEventLog\scripts\install\RegisterRequestLog::class,
            \oat\taoEventLog\scripts\install\RegisterUserLastActivityLog::class,
        ]
    ],
    'uninstall' => [
        'php' => [
            join(DIRECTORY_SEPARATOR, [__DIR__, 'scripts', 'uninstall', 'DetachLoggerEvents.php']),
            join(DIRECTORY_SEPARATOR, [__DIR__, 'scripts', 'uninstall', 'UnregisterRdsStorage.php'])
        ]
    ],
    'routes' => array(
        '/taoEventLog' => 'oat\\taoEventLog\\controller'
    ),
    'constants' => array(
        # views directory
        "DIR_VIEWS" => dirname(__FILE__) . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR,

        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL . 'taoEventLog/',
    ),
    'extra' => array(
        'structures' => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'structures.xml',
    )
);
