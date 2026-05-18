<?php

namespace CubeConnect\Contracts;

use CubeConnect\DTOs\CampaignResponse;
use CubeConnect\DTOs\CampaignRecipientsPage;
use CubeConnect\DTOs\MessageResponse;
use CubeConnect\DTOs\MessageStatusResponse;
use CubeConnect\DTOs\TemplateData;

interface Messaging
{
    /** Send a plain text message. Pass $whatsappAccountId to override the default. */
    public function sendText(string $phone, string $body, ?string $scheduledAt = null, ?string $timezone = null, ?string $whatsappAccountId = null): MessageResponse;

    /**
     * Send a pre-approved template message. Params map to {{1}}, {{2}}, etc.
     *
     * @param  bool  $autoRetryOnFrequencyCap  Opt-in 26h auto-retry when Meta rejects with code 131049
     *                                         (per-recipient marketing frequency cap). One retry per chain.
     */
    public function sendTemplate(
        string $phone,
        string $name,
        string $languageCode,
        array $params = [],
        ?string $scheduledAt = null,
        ?string $timezone = null,
        ?string $whatsappAccountId = null,
        bool $autoRetryOnFrequencyCap = false,
    ): MessageResponse;

    /** Create a bulk campaign. Pass whatsapp_account_id inside $payload to override the default. */
    public function createCampaign(array $payload): CampaignResponse;

    /** Retrieve campaign status and statistics. */
    public function getCampaign(string $campaignId): CampaignResponse;

    /** Cancel a scheduled campaign that has not yet started. */
    public function cancelCampaign(string $campaignId): bool;

    /** Get paginated list of campaign recipients with delivery status. */
    public function getCampaignRecipients(string $campaignId, int $page = 1, int $perPage = 50, ?string $status = null): CampaignRecipientsPage;

    /** Get the current delivery status of a sent message. */
    public function getMessageStatus(string $messageLogId): MessageStatusResponse;

    /** List templates. Pass $whatsappAccountId to override the default. Pass $status to filter (e.g. 'APPROVED'). */
    public function getTemplates(?string $status = null, ?string $whatsappAccountId = null): array;

    /** Check the platform health status. No authentication required. */
    public function health(): array;
}
