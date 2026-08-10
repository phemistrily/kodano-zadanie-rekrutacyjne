<?php

namespace App\Domain\Notification;

final readonly class Notification
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private string $subject,
        private string $message,
        private array $context = [],
    ) {
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
