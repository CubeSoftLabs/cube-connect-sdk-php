<?php

namespace CubeConnect\DTOs;

class CampaignRecipientResult
{
    /**
     * @param  string       $phone
     * @param  string|null  $name
     * @param  string       $status         pending|sent|failed
     * @param  string|null  $messageLogId
     * @param  string|null  $errorMessage
     * @param  string|null  $sentAt         ISO 8601 or null
     */
    public function __construct(
        public readonly string $phone,
        public readonly ?string $name,
        public readonly string $status,
        public readonly ?string $messageLogId,
        public readonly ?string $errorMessage,
        public readonly ?string $sentAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            phone:         (string) ($data['phone'] ?? ''),
            name:          isset($data['name']) ? (string) $data['name'] : null,
            status:        (string) ($data['status'] ?? 'pending'),
            messageLogId:  isset($data['message_log_id']) ? (string) $data['message_log_id'] : null,
            errorMessage:  isset($data['error_message']) ? (string) $data['error_message'] : null,
            sentAt:        isset($data['sent_at']) ? (string) $data['sent_at'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'phone'          => $this->phone,
            'name'           => $this->name,
            'status'         => $this->status,
            'message_log_id' => $this->messageLogId,
            'error_message'  => $this->errorMessage,
            'sent_at'        => $this->sentAt,
        ];
    }
}
