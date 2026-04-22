<?php

namespace CubeConnect\DTOs;

class CampaignRecipientsPage
{
    /**
     * @param  string                    $campaignId
     * @param  CampaignRecipientResult[] $recipients
     * @param  int                       $currentPage
     * @param  int                       $perPage
     * @param  int                       $total
     * @param  int                       $lastPage
     */
    public function __construct(
        public readonly string $campaignId,
        public readonly array $recipients,
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $total,
        public readonly int $lastPage,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        $pagination = $data['pagination'] ?? [];

        $recipients = array_map(
            fn (array $r) => CampaignRecipientResult::fromArray($r),
            $data['recipients'] ?? [],
        );

        return new static(
            campaignId:  (string) ($data['campaign_id'] ?? ''),
            recipients:  $recipients,
            currentPage: (int) ($pagination['current_page'] ?? 1),
            perPage:     (int) ($pagination['per_page'] ?? 50),
            total:       (int) ($pagination['total'] ?? 0),
            lastPage:    (int) ($pagination['last_page'] ?? 1),
        );
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'campaign_id' => $this->campaignId,
            'recipients'  => array_map(fn ($r) => $r->toArray(), $this->recipients),
            'pagination'  => [
                'current_page' => $this->currentPage,
                'per_page'     => $this->perPage,
                'total'        => $this->total,
                'last_page'    => $this->lastPage,
            ],
        ];
    }
}
