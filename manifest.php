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
 * Copyright (c) 2016-2026 (original work) Open Assessment Technologies SA;
 */

use oat\tao\model\accessControl\func\AccessRule;
use oat\taoEventLog\model\DataPolicyOrchestrator\DataPolicyServiceProvider;
use oat\taoEventLog\model\FrontendAction\FrontendActionServiceProvider;
use oat\taoEventLog\model\Repository\EventLogRepositoryServiceProvider;
use oat\taoEventLog\scripts\install\RegisterLoggerService;
use oat\taoEventLog\scripts\install\RegisterRdsStorage;
use oat\taoEventLog\scripts\install\RegisterRequestLog;
use oat\taoEventLog\scripts\install\RegisterUserLastActivityLog;

return [
    'name' => 'taoEventLog',
    'label' => 'Test-taker Event Logging',
    'description' => 'The event logging system that catches and logs all actions of test-takers',
    'license' => 'GPL-2.0',
    'author' => 'Open Assessment Technologies SA',
    'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#taoEventLogManager',
    'acl' => [
        [AccessRule::GRANT, 'http://www.tao.lu/Ontologies/generis.rdf#taoEventLogManager', ['ext' => 'taoEventLog']],
        [
            AccessRule::GRANT,
            'http://www.tao.lu/Ontologies/TAOItem.rdf#ItemAuthor',
            ['ext' => 'taoEventLog', 'mod' => 'TaoEventLog', 'act' => 'logFrontendAction'],
        ],
        [
            AccessRule::GRANT,
            'http://www.tao.lu/Ontologies/TAOItem.rdf#ItemPreviewerRole',
            ['ext' => 'taoEventLog', 'mod' => 'TaoEventLog', 'act' => 'logFrontendAction'],
        ],
        [
            AccessRule::GRANT,
            'http://www.tao.lu/Ontologies/TAOItem.rdf#TestAuthor',
            ['ext' => 'taoEventLog', 'mod' => 'TaoEventLog', 'act' => 'logFrontendAction'],
        ],
    ],
    'update' => 'oat\\taoEventLog\\scripts\\update\\Updater',
    'install' => [
        'php' => [
            RegisterRdsStorage::class,
            RegisterLoggerService::class,
            RegisterRequestLog::class,
            RegisterUserLastActivityLog::class,
        ]
    ],
    'uninstall' => [
        'php' => [
            implode(DIRECTORY_SEPARATOR, [__DIR__, 'scripts', 'uninstall', 'DetachLoggerEvents.php']),
            implode(DIRECTORY_SEPARATOR, [__DIR__, 'scripts', 'uninstall', 'UnregisterRdsStorage.php'])
        ]
    ],
    'routes' => [
        '/taoEventLog' => 'oat\\taoEventLog\\controller'
    ],
    'constants' => [
        # views directory
        'DIR_VIEWS' => __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR,

        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL . 'taoEventLog/',
    ],
    'extra' => [
        'structures' => __DIR__ . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'structures.xml',
    ],
    'containerServiceProviders' => [
        EventLogRepositoryServiceProvider::class,
        FrontendActionServiceProvider::class,
        DataPolicyServiceProvider::class,
    ],
];
