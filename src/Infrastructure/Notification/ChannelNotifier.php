<?php

namespace App\Infrastructure\Notification;

use App\Domain\Notification\NotificationChannelInterface;
use App\Domain\Notification\NotifierInterface;
use App\Domain\Notification\Notification;
use Psr\Log\LoggerInterface;

final class ChannelNotifier implements NotifierInterface
{
    /**
     * @param iterable<NotificationChannelInterface> $channels
     */
    public function __construct(
        private readonly iterable $channels,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function notify(Notification $notification): void
    {
        foreach ($this->channels as $channel) {
            try {
                $channel->send($notification);
            } catch (\Throwable $exception) {
                $this->logger->error('Notification channel failed.', [
                    'channel' => $channel::class,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}
