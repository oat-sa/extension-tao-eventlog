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

namespace oat\taoEventLog\model\storage;

use common_persistence_Manager;
use common_persistence_Persistence;
use common_persistence_SqlPersistence;
use DateTimeImmutable;
use oat\oatbox\service\ConfigurableService;
use oat\taoEventLog\model\StorageInterface;
use oat\oatbox\user\User;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\event\Event;
use oat\taoEventLog\model\event\TinCanEvent;
use oat\dtms\DateTime;
/**
 * Class TinCanStorage
 *
 * Configuration example:
 * ```php
 *
 * use \oat\taoEventLog\model\storage\TinCanStorage;
 *
 * return new TinCanStorage([
 *    TinCanStorage::OPTION_VERSION => '1.0.1',
 *    TinCanStorage::OPTION_ENDPOINT => 'http://ll.com/data/xAPI/',
 *    TinCanStorage::OPTION_AUTH => [
 *        '8991ed3f721cfb5bbf57d8e0a3e448e1f56114c6',
 *        '07e6e0fe16dd07018ba327efcebe8cbfbb73d08b',
 *    ],
 * ]);
 *
 * ```
 *
 * @package oat\taoEventLog\model\storage
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class TinCanStorage extends ConfigurableService implements StorageInterface
{
    use OntologyAwareTrait;

    const OPTION_PERSISTENCE = 'persistence';
    const OPTION_ENDPOINT = 'endpoint';
    const OPTION_VERSION = 'version';
    const OPTION_AUTH = 'auth';

    /**
     * @param Event $event
     * @param string $currentAction
     * @param User $user
     * @param DateTime $occurred
     * @param array $data
     * @return bool
     */
    public function log(Event $event, $currentAction, User $user, DateTime $occurred, $data = [])
    {
        /** @var common_session_Session $session */
        $session = \common_session_SessionManager::getSession();

        /** @var User $currentUser */
        $currentUser = $session->getUser();

        try {
            $lrs = $this->getLrs();

            $statement = new \TinCan\Statement([
                'actor' => $this->getActor($event, $currentUser),
                'verb'  => $this->getVerb($event, $currentUser),
                'object' => $this->getActivity($event, $currentAction),
                'context' => $this->getContext($event, $currentUser),
                'timestamp' => $occurred->format('Y-m-d\TH:i:s.uP'),
            ]);

            $response = $lrs->saveStatement($statement);

            if (!$response->success) {
                \common_Logger::e($response->content);
            }

        } catch (\Exception $e) {
            \common_Logger::e('Error logging to LRS ' . $e->getMessage());
        }
    }

    /**
     * @param array $params
     * @return array
     */
    public function searchInstances(array $params = [])
    {
        //ToDo: implement fetching reports
        return [];
    }

    /**
     * @return string
     */
    protected function getRootUrl()
    {
        return \tao_helpers_Uri::getRootUrl();
    }


    /**
     * @return \TinCan\RemoteLRS
     */
    protected function getLrs()
    {
        $lrs = new \TinCan\RemoteLRS();
        $lrs->setEndpoint($this->getOption(self::OPTION_ENDPOINT));
        $lrs->setVersion($this->getOption(self::OPTION_VERSION));
        $auth = $this->getOption(self::OPTION_AUTH);
        if (!is_array($auth)) {
            $auth = [$auth];
        }
        call_user_func_array([$lrs, 'setAuth'], $auth);
        return $lrs;
    }

    /**
     * @param Event $event
     * @param User $user
     * @return \TinCan\Agent
     */
    protected function getActor(Event $event, User $user)
    {
        $actor = new \TinCan\Agent([
            'name' => \oat\tao\helpers\UserHelper::getUserName($user, true),
            'openid' => _url('tao', 'Users', 'index', ['#' => \tao_helpers_Uri::encode($user->getIdentifier())]),
        ]);

        return $actor;
    }

    /**
     * @param Event $event
     * @param User $user
     * @return mixed
     */
    protected function getVerb(Event $event, User $user)
    {
        if ($event instanceof TinCanEvent) {
            $verb = $event->getVerb();
        } else {
            $verb = new \TinCan\Verb(['id' => $this->getRootUrl() . 'events#' . str_replace('\\', '/', $event->getName())]);
        }
        return $verb;
    }

    /**
     * @param Event $event
     * @param string $currentAction
     * @return \TinCan\Activity
     */
    protected function getActivity(Event $event, $currentAction = '')
    {
        if ($event instanceof TinCanEvent) {
            $activity = $event->getActivity();
        } else {
            $activity = new \TinCan\Activity([
                'id' => $this->getRootUrl() . 'activities#' . $currentAction
            ]);
        }
        return $activity;
    }

    /**
     * @param Event $event
     * @param User $user
     * @return \TinCan\Context
     */
    protected function getContext(Event $event, User $user)
    {
        if ($event instanceof TinCanEvent) {
            $context = $event->getContext();
        } else {
            $context = new \TinCan\Context([
                'platform' => GENERIS_INSTANCE_NAME,
            ]);
        }

        $extensions = $context->getExtensions();
        $extensions->set(PROPERTY_USER_ROLES, join(',', $user->getRoles()));

        $context->setExtensions($extensions);

        return $context;
    }
}
