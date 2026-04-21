<?php

namespace CubeConnect\Contracts;

use CubeConnect\DTOs\CampaignResponse;
use CubeConnect\DTOs\MessageResponse;
use CubeConnect\DTOs\MessageStatusResponse;
use CubeConnect\DTOs\TemplateData;

interface Messaging
{
    /** Send a pre-approved template message. Params map to {{1}}, {{2}}, etc. */
    public function sendTemplate(string $phone, string $name, array $params = [], string $languageCode = 'en_US', ?string $scheduledAt = null, ?string $timezone = null): MessageResponse;

    /** Create a bulk campaign. */
    public function createCampaign(array $payload): CampaignResponse;

    /** Retrieve campaign status and statistics. */
    public function getCampaign(string $campaignId): CampaignResponse;

    /** Cancel a scheduled campaign that has not yet started. */
    public function cancelCampaign(string $campaignId): bool;

    /** Get the current delivery status of a sent message. */
    public function getMessageStatus(int $messageLogId): MessageStatusResponse;

    /** List templates for the configured WhatsApp account. Pass $status to filter (e.g. 'APPROVED'). */
    public function getTemplates(?string $status = null): array;

    /** Check the platform health status. No authentication required. */
    public function health(): array;
}
