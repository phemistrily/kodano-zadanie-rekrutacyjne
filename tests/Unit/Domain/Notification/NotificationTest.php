<?php

namespace App\Tests\Unit\Domain\Notification;

use App\Domain\Notification\Notification;
use PHPUnit\Framework\TestCase;

final class NotificationTest extends TestCase
{
    public function testExposesItsData(): void
    {
        $notification = new Notification('Temat', 'Treść wiadomości', ['productId' => 1]);

        self::assertSame('Temat', $notification->getSubject());
        self::assertSame('Treść wiadomości', $notification->getMessage());
        self::assertSame(['productId' => 1], $notification->getContext());
    }
}
