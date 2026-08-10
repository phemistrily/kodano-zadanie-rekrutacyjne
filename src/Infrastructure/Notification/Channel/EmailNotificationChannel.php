<?php

namespace App\Infrastructure\Notification\Channel;

use App\Domain\Notification\NotificationChannelInterface;
use App\Domain\Notification\Notification;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class EmailNotificationChannel implements NotificationChannelInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $from,
        private readonly string $to,
    ) {
    }

    public function send(Notification $notification): void
    {
        $email = (new Email())
            ->from($this->from)
            ->to($this->to)
            ->subject($notification->getSubject())
            ->text($notification->getMessage());

        $this->mailer->send($email);
    }
}
