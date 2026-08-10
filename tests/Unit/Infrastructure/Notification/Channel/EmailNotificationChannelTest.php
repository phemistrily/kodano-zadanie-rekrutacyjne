<?php

namespace App\Tests\Unit\Infrastructure\Notification\Channel;

use App\Domain\Notification\Notification;
use App\Infrastructure\Notification\Channel\EmailNotificationChannel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class EmailNotificationChannelTest extends TestCase
{
    public function testSendsEmailWithSubjectFromToAndBody(): void
    {
        $notification = new Notification('Produkt zapisany: Laptop', 'Treść wiadomości');

        $captured = null;
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email) use (&$captured): bool {
                $captured = $email;

                return true;
            }));

        (new EmailNotificationChannel($mailer, 'from@example.com', 'to@example.com'))->send($notification);

        self::assertInstanceOf(Email::class, $captured);
        self::assertSame('Produkt zapisany: Laptop', $captured->getSubject());
        self::assertSame('Treść wiadomości', $captured->getTextBody());
        self::assertSame('from@example.com', $captured->getFrom()[0]->getAddress());
        self::assertSame('to@example.com', $captured->getTo()[0]->getAddress());
    }
}
