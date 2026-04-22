<?php

namespace CubeConnect\DTOs;

class MessageResponse
{
    public function __construct(
        public readonly string $status,
        public readonly string $messageLogId,
        public readonly string $conversationCategory,
        public readonly float $cost,
        public readonly ?string $scheduledAt = null,
    ) {}

    public static function fromResponse(array $data): static
    {
        return new static(
            status:               $data['status'] ?? '',
            messageLogId:         (string) ($data['message_log_id'] ?? ''),
            conversationCategory: $data['conversation_category'] ?? '',
            cost:                 (float) ($data['cost'] ?? 0),
            scheduledAt:          $data['scheduled_at'] ?? null,
        );
    }

    public function queued(): bool
    {
        return $this->status === 'queued';
    }

    public function scheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function toArray(): array
    {
        return [
            'status'                => $this->status,
            'message_log_id'        => $this->messageLogId,
            'conversation_category' => $this->conversationCategory,
            'cost'                  => $this->cost,
            'scheduled_at'          => $this->scheduledAt,
        ];
    }
}
