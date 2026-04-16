<?php

declare(strict_types=1);

namespace oat\taoEventLog\model\UserData;

use oat\tao\model\Observer\GCP\UserDataRemoval\UserDataPolicyHandlerInterface;
use oat\tao\model\Observer\GCP\UserDataRemoval\UserDataPolicyMessage;
use Psr\Log\LoggerInterface;
use Throwable;

class UserDataRemovalHandler implements UserDataPolicyHandlerInterface
{
    public function __construct(
        private readonly UserDataEventLogCleanupService $cleanupService,
        private readonly LoggerInterface                $logger
    ) {
    }

    public function handle(UserDataPolicyMessage $message): bool
    {
        $login = $message->getDataSubjectRawId();

        try {
            $removedRows = $this->cleanupService->deleteByLogin($login);
            $this->logger->info(
                sprintf(
                    'User data removal completed for login "%s", removed rows: %d.',
                    $login,
                    $removedRows
                )
            );

            return true;
        } catch (Throwable $exception) {
            $this->logger->error(
                sprintf('User data removal failed for login "%s": %s', $login, $exception->getMessage())
            );

            return false;
        }
    }
}
