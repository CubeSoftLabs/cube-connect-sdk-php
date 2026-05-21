<?php

namespace CubeConnect\DTOs;

class CampaignResponse
{
    /**
     * Create a new campaign response instance.
     *
     * @param  string       $campaignId
     * @param  string|null  $name
     * @param  string       $status            pending|processing|completed|cancelled|failed|scheduled
     * @param  string       $messageType       text|template
     * @param  int          $totalCount
     * @param  int          $sentCount
     * @param  int          $deliveredCount    Subset of $sentCount confirmed delivered by Meta webhook
     * @param  int          $readCount         Subset of $sentCount confirmed read by Meta webhook
     * @param  int          $failedCount
     * @param  string|null  $scheduledAt       ISO 8601 or null
     * @param  string       $createdAt         ISO 8601
     */
    public function __construct(
        public readonly string $campaignId,
        public readonly ?string $name,
        public readonly string $status,
        public readonly string $messageType,
        public readonly int $totalCount,
        public readonly int $sentCount,
        public readonly int $deliveredCount,
        public readonly int $readCount,
        public readonly int $failedCount,
        public readonly ?string $scheduledAt,
        public readonly string $createdAt,
    ) {}

    /**
     * Create a new instance from an API response array.
     *
     * @param  array<string, mixed>  $data
     * @return static
     */
    public static function fromResponse(array $data): static
    {
        return new static(
            campaignId:     (string) ($data['campaign_id'] ?? ''),
            name:           isset($data['name']) ? (string) $data['name'] : null,
            status:         (string) ($data['status'] ?? 'pending'),
            messageType:    (string) ($data['message_type'] ?? ''),
            totalCount:     (int) ($data['total_count'] ?? 0),
            sentCount:      (int) ($data['sent_count'] ?? 0),
            deliveredCount: (int) ($data['delivered_count'] ?? 0),
            readCount:      (int) ($data['read_count'] ?? 0),
            failedCount:    (int) ($data['failed_count'] ?? 0),
            scheduledAt:    isset($data['scheduled_at']) ? (string) $data['scheduled_at'] : null,
            createdAt:      (string) ($data['created_at'] ?? ''),
        );
    }

    /**
     * Determine if the campaign is scheduled (not yet started).
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled'
            || ($this->status === 'pending' && $this->scheduledAt !== null);
    }

    /**
     * Determine if the campaign has completed successfully.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Determine if the campaign was cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get the response as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'campaign_id'     => $this->campaignId,
            'name'            => $this->name,
            'status'          => $this->status,
            'message_type'    => $this->messageType,
            'total_count'     => $this->totalCount,
            'sent_count'      => $this->sentCount,
            'delivered_count' => $this->deliveredCount,
            'read_count'      => $this->readCount,
            'failed_count'    => $this->failedCount,
            'scheduled_at'    => $this->scheduledAt,
            'created_at'      => $this->createdAt,
        ];
    }
}
