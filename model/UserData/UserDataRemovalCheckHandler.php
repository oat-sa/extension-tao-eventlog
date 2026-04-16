<?php

declare(strict_types=1);

namespace oat\taoEventLog\model\UserData;

use oat\tao\model\Observer\GCP\UserDataRemoval\UserDataPolicyHandlerInterface;
use oat\tao\model\Observer\GCP\UserDataRemoval\UserDataPolicyMessage;
use Psr\Log\LoggerInterface;
use Throwable;

class UserDataRemovalCheckHandler implements UserDataPolicyHandlerInterface
{
    public function __construct(
        private readonly UserDataEventLogCleanupService $cleanupService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(UserDataPolicyMessage $message): bool
    {
        $login = $message->getDataSubjectRawId();

        try {
            $isRemoved = !$this->cleanupService->hasData($login);
            $this->logger->info(
                sprintf(
                    'User data check for login "%s": %s.',
                    $login,
                    $isRemoved ? 'removed' : 'still exists'
                )
            );

            return $isRemoved;
        } catch (Throwable $exception) {
            $this->logger->error(
                sprintf('User data check failed for login "%s": %s', $login, $exception->getMessage())
            );

            return false;
        }
    }
}
