<?php

declare(strict_types=1);

namespace oat\taoEventLog\migrations;

use common_ext_ExtensionsManager;
use Doctrine\DBAL\Schema\Schema;
use oat\oatbox\event\EventManager;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoEventLog\model\eventLog\LoggerService;

/**
 * Auto-generated Migration: Please modify to your needs!
 *
 * phpcs:disable Squiz.Classes.ValidClassName
 */
final class Version202608180720062752_taoEventLog extends AbstractMigration
{
    private const LTI_ADMINISTRATOR_DEVELOPER_ROLE = 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Developer';
    private const LTI_INSTITUTION_ADMINISTRATOR_ROLE = 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator';
    private const CONTENT_BANK_ACCESSED_FROM_PORTAL_EVENT =
        'oat\\taoLti\\models\\classes\\event\\ContentBankAccessedFromPortalEvent';

    public function getDescription(): string
    {
        return 'Grant taoEventLog access to Portal administrator LTI roles and attach Content Bank portal access event';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->getRules() as $rule) {
            AclProxy::applyRule($rule);
        }

        if ($this->isTaoLtiEnabled()) {
            $eventManager = $this->getEventManager();
            $eventManager->attach(self::CONTENT_BANK_ACCESSED_FROM_PORTAL_EVENT, [LoggerService::class, 'logEvent']);
            $this->getServiceLocator()->register(EventManager::SERVICE_ID, $eventManager);
        }
    }

    public function down(Schema $schema): void
    {
        foreach ($this->getRules() as $rule) {
            AclProxy::revokeRule($rule);
        }

        if ($this->isTaoLtiEnabled()) {
            $eventManager = $this->getEventManager();
            $eventManager->detach(self::CONTENT_BANK_ACCESSED_FROM_PORTAL_EVENT, [LoggerService::class, 'logEvent']);
            $this->getServiceLocator()->register(EventManager::SERVICE_ID, $eventManager);
        }
    }

    /**
     * @return AccessRule[]
     */
    private function getRules(): array
    {
        return [
            new AccessRule(
                AccessRule::GRANT,
                self::LTI_ADMINISTRATOR_DEVELOPER_ROLE,
                ['ext' => 'taoEventLog']
            ),
            new AccessRule(
                AccessRule::GRANT,
                self::LTI_INSTITUTION_ADMINISTRATOR_ROLE,
                ['ext' => 'taoEventLog']
            ),
        ];
    }

    private function getEventManager(): EventManager
    {
        return $this->getServiceLocator()->get(EventManager::SERVICE_ID);
    }

    private function isTaoLtiEnabled(): bool
    {
        /** @var common_ext_ExtensionsManager $extensionManager */
        $extensionManager = $this->getServiceLocator()->get(common_ext_ExtensionsManager::SERVICE_ID);

        return $extensionManager->isEnabled('taoLti')
            && class_exists(self::CONTENT_BANK_ACCESSED_FROM_PORTAL_EVENT);
    }
}
