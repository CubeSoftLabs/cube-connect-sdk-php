<?php

namespace CubeConnect\Contracts;

use CubeConnect\DTOs\CampaignResponse;
use CubeConnect\DTOs\MessageResponse;

interface Messaging
{
    /**
     * Send a text message to a WhatsApp number.
     *
     * Text messages can only be sent within 24 hours of the customer's
     * last inbound message. Outside this window, use sendTemplate() instead.
     *
     * @param  string       $phone
     * @param  string       $body
     * @param  string|null  $scheduledAt  ISO 8601 datetime for deferred delivery (e.g. "2026-05-01T10:00:00Z")
     * @return \CubeConnect\DTOs\MessageResponse
     *
     * @throws \CubeConnect\Exceptions\AuthenticationException
     * @throws \CubeConnect\Exceptions\ValidationException
     * @throws \CubeConnect\Exceptions\RateLimitException
     * @throws \CubeConnect\Exceptions\CubeConnectException
     */
    public function sendText(string $phone, string $body, ?string $scheduledAt = null): MessageResponse;

    /**
     * Send a pre-approved template message.
     *
     * Template messages can be sent at any time, regardless of the
     * 24-hour messaging window. Parameters map to {{1}}, {{2}}, etc.
     *
     * @param  string       $phone
     * @param  string       $name
     * @param  array<int, string>  $params
     * @param  string       $languageCode
     * @param  string|null  $scheduledAt  ISO 8601 datetime for deferred delivery
     * @return \CubeConnect\DTOs\MessageResponse
     *
     * @throws \CubeConnect\Exceptions\AuthenticationException
     * @throws \CubeConnect\Exceptions\ValidationException
     * @throws \CubeConnect\Exceptions\RateLimitException
     * @throws \CubeConnect\Exceptions\CubeConnectException
     */
    public function sendTemplate(string $phone, string $name, array $params = [], string $languageCode = 'en_US', ?string $scheduledAt = null): MessageResponse;

    /**
     * Create a bulk campaign.
     *
     * @param  array{
     *   whatsapp_account_id: string,
     *   message_type: 'text'|'template',
     *   body?: string,
     *   template_id?: string,
     *   template_params?: string[],
     *   recipients: array<int, array{phone: string, name?: string, variables?: array<string, string>}>,
     *   campaign_name?: string,
     *   scheduled_at?: string,
     * }  $payload
     * @return \CubeConnect\DTOs\CampaignResponse
     *
     * @throws \CubeConnect\Exceptions\AuthenticationException
     * @throws \CubeConnect\Exceptions\ValidationException
     * @throws \CubeConnect\Exceptions\RateLimitException
     * @throws \CubeConnect\Exceptions\CubeConnectException
     */
    public function createCampaign(array $payload): CampaignResponse;

    /**
     * Retrieve campaign status and statistics.
     *
     * @param  string  $campaignId
     * @return \CubeConnect\DTOs\CampaignResponse
     *
     * @throws \CubeConnect\Exceptions\NotFoundException
     * @throws \CubeConnect\Exceptions\AuthenticationException
     * @throws \CubeConnect\Exceptions\CubeConnectException
     */
    public function getCampaign(string $campaignId): CampaignResponse;

    /**
     * Cancel a scheduled campaign that has not yet started.
     *
     * @param  string  $campaignId
     * @return bool
     *
     * @throws \CubeConnect\Exceptions\NotFoundException
     * @throws \CubeConnect\Exceptions\ValidationException
     * @throws \CubeConnect\Exceptions\AuthenticationException
     * @throws \CubeConnect\Exceptions\CubeConnectException
     */
    public function cancelCampaign(string $campaignId): bool;

    /**
     * Check the platform health status.
     *
     * @return array{status: string, checks: array{app: bool, database: bool, cache: bool}, timestamp: string}
     */
    public function health(): array;
}
