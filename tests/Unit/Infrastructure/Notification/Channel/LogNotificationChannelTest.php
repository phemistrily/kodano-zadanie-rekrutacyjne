<?php

namespace App\Tests\Unit\Infrastructure\Notification\Channel;

use App\Domain\Notification\Notification;
use App\Infrastructure\Notification\Channel\LogNotificationChannel;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class LogNotificationChannelTest extends TestCase
{
    public function testLogsSubjectWithContext(): void
    {
        $notification = new Notification('Produkt zapisany: Laptop', 'Treść', ['productId' => 1]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with('Produkt zapisany: Laptop', ['productId' => 1]);

        (new LogNotificationChannel($logger))->send($notification);
    }
}
