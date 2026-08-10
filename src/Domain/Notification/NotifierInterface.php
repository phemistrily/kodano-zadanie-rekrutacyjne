<?php

namespace App\Domain\Notification;

interface NotifierInterface
{
    public function notify(Notification $notification): void;
}
