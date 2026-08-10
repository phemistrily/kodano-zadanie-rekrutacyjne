<?php

namespace App\Infrastructure\Notification\Channel;

use App\Domain\Notification\NotificationChannelInterface;
use App\Domain\Notification\Notification;
use Psr\Log\LoggerInterface;

final class LogNotificationChannel implements NotificationChannelInterface
{
    public function __construct(
        private readonly LoggerInterface $notificationLogger,
    ) {
    }

    public function send(Notification $notification): void
    {
        $this->notificationLogger->info(
            $notification->getSubject(),
            $notification->getContext(),
        );
    }
}
