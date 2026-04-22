<?php

namespace CubeConnect;

use CubeConnect\Contracts\Messaging;
use CubeConnect\DTOs\CampaignResponse;
use CubeConnect\DTOs\MessageResponse;
use CubeConnect\DTOs\MessageStatusResponse;
use CubeConnect\DTOs\TemplateData;
use CubeConnect\Exceptions\AuthenticationException;
use CubeConnect\Exceptions\CubeConnectException;
use CubeConnect\Exceptions\NotFoundException;
use CubeConnect\Exceptions\RateLimitException;
use CubeConnect\Exceptions\ValidationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class CubeConnect implements Messaging
{
    protected string $apiKey;
    protected string $whatsappAccountId;
    protected string $baseUrl;
    protected int $timeout;

    public function __construct(string $apiKey, string $whatsappAccountId, string $baseUrl, int $timeout = 30)
    {
        $this->apiKey            = $apiKey;
        $this->whatsappAccountId = $whatsappAccountId;
        $this->baseUrl           = rtrim($baseUrl, '/');
        $this->timeout           = $timeout;
    }

    /** Send a plain text message. Pass $whatsappAccountId to override the default. */
    public function sendText(string $phone, string $body, ?string $scheduledAt = null, ?string $timezone = null, ?string $whatsappAccountId = null): MessageResponse
    {
        $payload = [
            'whatsapp_account_id' => $whatsappAccountId ?? $this->whatsappAccountId,
            'phone'               => $phone,
            'message_type'        => 'text',
            'data'                => ['text' => $body],
        ];

        if ($scheduledAt !== null) {
            $payload['scheduled_at'] = $scheduledAt;
        }

        if ($timezone !== null) {
            $payload['timezone'] = $timezone;
        }

        return $this->send($payload);
    }

    /** Send a pre-approved template message. Params map to {{1}}, {{2}}, etc. Pass $whatsappAccountId to override the default. */
    public function sendTemplate(string $phone, string $name, string $languageCode, array $params = [], ?string $scheduledAt = null, ?string $timezone = null, ?string $whatsappAccountId = null): MessageResponse
    {
        $data = [
            'name'          => $name,
            'language_code' => $languageCode,
        ];

        if (! empty($params)) {
            $data['components'] = [
                [
                    'type'       => 'body',
                    'parameters' => array_map(fn ($value) => [
                        'type' => 'text',
                        'text' => (string) $value,
                    ], array_values($params)),
                ],
            ];
        }

        $payload = [
            'whatsapp_account_id' => $whatsappAccountId ?? $this->whatsappAccountId,
            'phone'               => $phone,
            'message_type'        => 'template',
            'data'                => $data,
        ];

        if ($scheduledAt !== null) {
            $payload['scheduled_at'] = $scheduledAt;
        }

        if ($timezone !== null) {
            $payload['timezone'] = $timezone;
        }

        return $this->send($payload);
    }

    /** Create a bulk campaign. */
    public function createCampaign(array $payload): CampaignResponse
    {
        $payload = array_merge(['whatsapp_account_id' => $this->whatsappAccountId], $payload);

        try {
            $response = $this->buildRequest()
                ->post("{$this->baseUrl}/api/v1/campaigns", $payload);
        } catch (ConnectionException $e) {
            throw CubeConnectException::connectionFailed($e);
        }

        $this->handleErrors($response);

        return CampaignResponse::fromResponse($response->json('data', []));
    }

    /** Retrieve campaign status and statistics. */
    public function getCampaign(string $campaignId): CampaignResponse
    {
        try {
            $response = $this->buildRequest()
                ->get("{$this->baseUrl}/api/v1/campaigns/{$campaignId}");
        } catch (ConnectionException $e) {
            throw CubeConnectException::connectionFailed($e);
        }

        $this->handleErrors($response);

        return CampaignResponse::fromResponse($response->json('data', []));
    }

    /** Cancel a scheduled campaign that has not yet started. */
    public function cancelCampaign(string $campaignId): bool
    {
        try {
            $response = $this->buildRequest()
                ->post("{$this->baseUrl}/api/v1/campaigns/{$campaignId}/cancel");
        } catch (ConnectionException $e) {
            throw CubeConnectException::connectionFailed($e);
        }

        $this->handleErrors($response);

        return (bool) $response->json('data.success', false);
    }

    /** Get the current delivery status of a sent message. */
    public function getMessageStatus(string $messageLogId): MessageStatusResponse
    {
        try {
            $response = $this->buildRequest()
                ->get("{$this->baseUrl}/api/v1/messages/{$messageLogId}");
        } catch (ConnectionException $e) {
            throw CubeConnectException::connectionFailed($e);
        }

        $this->handleErrors($response);

        return MessageStatusResponse::fromResponse($response->json('data', []));
    }

    /** List templates. Pass $whatsappAccountId to override the default. Pass $status to filter (e.g. 'APPROVED'). */
    public function getTemplates(?string $status = null, ?string $whatsappAccountId = null): array
    {
        $query = http_build_query(array_filter([
            'whatsapp_account_id' => $whatsappAccountId ?? $this->whatsappAccountId,
            'status'              => $status,
        ]));

        try {
            $response = $this->buildRequest()
                ->get("{$this->baseUrl}/api/v1/templates?{$query}");
        } catch (ConnectionException $e) {
            throw CubeConnectException::connectionFailed($e);
        }

        $this->handleErrors($response);

        return array_map(
            fn (array $item) => TemplateData::fromArray($item),
            $response->json('data', []),
        );
    }

    /** Check the platform health status. No authentication required. */
    public function health(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/api/health");
        } catch (ConnectionException $e) {
            throw CubeConnectException::connectionFailed($e);
        }

        if ($response->failed()) {
            $error = $response->json('error', []);
            throw CubeConnectException::serverError(
                $response->status(),
                $error['code'] ?? '',
                $error['message'] ?? '',
            );
        }

        return $response->json('data', []);
    }

    // ── Private ──────────────────────────────────────────────────────────────

    protected function send(array $payload): MessageResponse
    {
        try {
            $response = $this->buildRequest()
                ->post("{$this->baseUrl}/api/v1/messages/send", $payload);
        } catch (ConnectionException $e) {
            throw CubeConnectException::connectionFailed($e);
        }

        $this->handleErrors($response);

        return MessageResponse::fromResponse($response->json('data', []));
    }

    protected function buildRequest(): PendingRequest
    {
        return Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->accept('application/json');
    }

    protected function handleErrors(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $error   = $response->json('error', []);
        $code    = $error['code'] ?? '';
        $message = $error['message'] ?? '';
        $details = $error['details'] ?? [];
        $status  = $response->status();

        match ($status) {
            401     => throw AuthenticationException::invalidKey($code, $message),
            403     => throw AuthenticationException::forbidden($code, $message),
            404     => throw NotFoundException::resource($code, $message),
            422     => throw ValidationException::withErrors($code, $message, $details),
            429     => throw RateLimitException::exceeded($code, $message),
            default => throw CubeConnectException::serverError($status, $code, $message),
        };
    }
}
