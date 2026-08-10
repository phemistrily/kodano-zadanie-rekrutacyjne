<?php

namespace App\Domain\Notification;

interface NotificationChannelInterface
{
    public function send(Notification $notification): void;
}
