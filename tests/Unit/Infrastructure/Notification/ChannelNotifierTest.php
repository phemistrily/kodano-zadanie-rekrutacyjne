<?php

namespace App\Tests\Unit\Infrastructure\Notification;

use App\Domain\Notification\Notification;
use App\Domain\Notification\NotificationChannelInterface;
use App\Infrastructure\Notification\ChannelNotifier;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ChannelNotifierTest extends TestCase
{
    public function testNotifiesEveryChannel(): void
    {
        $notification = new Notification('Temat', 'Treść');

        $channelA = $this->createMock(NotificationChannelInterface::class);
        $channelA->expects(self::once())->method('send')->with($notification);

        $channelB = $this->createMock(NotificationChannelInterface::class);
        $channelB->expects(self::once())->method('send')->with($notification);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        (new ChannelNotifier([$channelA, $channelB], $logger))->notify($notification);
    }

    public function testFailingChannelDoesNotStopOthersAndIsLogged(): void
    {
        $notification = new Notification('Temat', 'Treść');

        $failing = $this->createStub(NotificationChannelInterface::class);
        $failing->method('send')->willThrowException(new \RuntimeException('boom'));

        $working = $this->createMock(NotificationChannelInterface::class);
        $working->expects(self::once())->method('send')->with($notification);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        (new ChannelNotifier([$failing, $working], $logger))->notify($notification);
    }
}
