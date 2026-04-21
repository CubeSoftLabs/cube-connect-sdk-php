<?php

namespace CubeConnect\DTOs;

class MessageStatusResponse
{
    public function __construct(
        public readonly int $messageLogId,
        public readonly string $status,
        public readonly string $toPhone,
        public readonly string $messageType,
        public readonly ?string $metaMessageId,
        public readonly ?string $sentAt,
        public readonly ?string $scheduledAt,
        public readonly float $costAmount,
        public readonly string $costCurrency,
        public readonly ?string $errorMessage,
        public readonly string $createdAt,
    ) {}

    public static function fromResponse(array $data): static
    {
        return new static(
            messageLogId:  (int) ($data['message_log_id'] ?? 0),
            status:        $data['status'] ?? '',
            toPhone:       $data['to_phone'] ?? '',
            messageType:   $data['message_type'] ?? '',
            metaMessageId: $data['meta_message_id'] ?? null,
            sentAt:        $data['sent_at'] ?? null,
            scheduledAt:   $data['scheduled_at'] ?? null,
            costAmount:    (float) ($data['cost_amount'] ?? 0),
            costCurrency:  $data['cost_currency'] ?? '',
            errorMessage:  $data['error_message'] ?? null,
            createdAt:     $data['created_at'] ?? '',
        );
    }

    public function isSent(): bool      { return $this->status === 'sent'; }
    public function isDelivered(): bool { return $this->status === 'delivered'; }
    public function isRead(): bool      { return $this->status === 'read'; }
    public function isFailed(): bool    { return $this->status === 'failed'; }
    public function isScheduled(): bool { return $this->status === 'scheduled'; }

    public function toArray(): array
    {
        return [
            'message_log_id'  => $this->messageLogId,
            'status'          => $this->status,
            'to_phone'        => $this->toPhone,
            'message_type'    => $this->messageType,
            'meta_message_id' => $this->metaMessageId,
            'sent_at'         => $this->sentAt,
            'scheduled_at'    => $this->scheduledAt,
            'cost_amount'     => $this->costAmount,
            'cost_currency'   => $this->costCurrency,
            'error_message'   => $this->errorMessage,
            'created_at'      => $this->createdAt,
        ];
    }
}
