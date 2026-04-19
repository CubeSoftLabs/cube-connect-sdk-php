<?php

namespace CubeConnect;

use CubeConnect\Contracts\Messaging;
use CubeConnect\DTOs\CampaignResponse;
use CubeConnect\DTOs\MessageResponse;
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
    /**
     * The API key used for authentication.
     *
     * @var string
     */
    protected string $apiKey;

    /**
     * The base URL for the CubeConnect API.
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * The request timeout in seconds.
     *
     * @var int
     */
    protected int $timeout;

    /**
     * Create a new CubeConnect client instance.
     *
     * The tenant is resolved automatically from the API key — no tenant ID header needed.
     * Each API key is scoped to a single tenant at creation time.
     *
     * @param  string  $apiKey
     * @param  string  $baseUrl
     * @param  int     $timeout
     */
    public function __construct(string $apiKey, string $baseUrl, int $timeout = 30)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Send a text message to a WhatsApp number.
     *
     * Text messages can only be sent within 24 hours of the customer's
     * last inbound message. Outside this window, use sendTemplate() instead.
     * Pass $scheduledAt (ISO 8601) to schedule the message for future delivery.
     *
     * @param  string       $phone
     * @param  string       $body
     * @param  string|null  $scheduledAt  ISO 8601 (e.g. "2026-05-01T10:00:00Z")
     * @return \CubeConnect\DTOs\MessageResponse
     *
     * @throws \CubeConnect\Exceptions\AuthenticationException
     * @throws \CubeConnect\Exceptions\ValidationException
     * @throws \CubeConnect\Exceptions\RateLimitException
     * @throws \CubeConnect\Exceptions\CubeConnectException
     */
    public function sendText(string $phone, string $body, ?string $scheduledAt = null): MessageResponse
    {
        $payload = [
            'phone'        => $phone,
            'message_type' => 'text',
            'data'         => ['text' => $body],
        ];

        if ($scheduledAt !== null) {
            $payload['scheduled_at'] = $scheduledAt;
        }

        return $this->send($payload);
    }

    /**
     * Send a pre-approved template message.
     *
     * Template messages can be sent at any time, regardless of the
     * 24-hour messaging window. Parameters map to {{1}}, {{2}}, etc.
     * Pass $scheduledAt (ISO 8601) to schedule the message for future delivery.
     *
     * @param  string       $phone
     * @param  string       $name
     * @param  array<int, string>  $params
     * @param  string       $languageCode
     * @param  string|null  $scheduledAt  ISO 8601 (e.g. "2026-05-01T10:00:00Z")
     * @return \CubeConnect\DTOs\MessageResponse
     *
     * @throws \CubeConnect\Exceptions\AuthenticationException
     * @throws \CubeConnect\Exceptions\ValidationException
     * @throws \CubeConnect\Exceptions\RateLimitException
     * @throws \CubeConnect\Exceptions\CubeConnectException
     */
    public function sendTemplate(string $phone, string $name, array $params = [], string $languageCode = 'en_US', ?string $scheduledAt = null): MessageResponse
    {
        $data = [
            'name'          => $name,
            'language_code' => $languageCode,
        ];

        if (! empty($params)) {
            // Convert simple params to the Meta components format required by the API
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
            'phone'        => $phone,
            'message_type' => 'template',
            'data'         => $data,
        ];

        if ($scheduledAt !== null) {
            $payload['scheduled_at'] = $scheduledAt;
        }

        return $this->send($payload);
    }

    /**
     * Create a bulk campaign.
     *
     * Recipients is an array of ['phone' => '...', 'name' => '...', 'variables' => [...]].
     * Optionally pass 'scheduled_at' (ISO 8601) in the payload to schedule the campaign.
     *
     * @param  array<string, mixed>  $payload
     * @return \CubeConnect\DTOs\CampaignResponse
     *
     * @throws \CubeConnect\Exceptions\AuthenticationException
     * @throws \CubeConnect\Exceptions\ValidationException
     * @throws \CubeConnect\Exceptions\RateLimitException
     * @throws \CubeConnect\Exceptions\CubeConnectException
     */
    public function createCampaign(array $payload): CampaignResponse
    {
        try {
            $response = $this->buildRequest()
                ->post("{$this->baseUrl}/api/v1/campaigns", $payload);
        } catch (ConnectionException $e) {
            throw CubeConnectException::connectionFailed($e);
        }

        $this->handleErrors($response);

        return CampaignResponse::fromResponse($response->json('data', []));
    }

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

    /**
     * Check the platform health status.
     *
     * This endpoint does not require authentication.
     *
     * @return array{status: string, checks: array{app: bool, database: bool, cache: bool}, timestamp: string}
     *
     * @throws \CubeConnect\Exceptions\CubeConnectException
     */
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

    /**
     * Send a message payload to the CubeConnect API.
     *
     * @param  array<string, mixed>  $payload
     * @return \CubeConnect\DTOs\MessageResponse
     *
     * @throws \CubeConnect\Exceptions\AuthenticationException
     * @throws \CubeConnect\Exceptions\ValidationException
     * @throws \CubeConnect\Exceptions\RateLimitException
     * @throws \CubeConnect\Exceptions\CubeConnectException
     */
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

    /**
     * Build an authenticated HTTP request instance.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function buildRequest(): PendingRequest
    {
        return Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->accept('application/json');
    }

    /**
     * Handle error responses from the API.
     *
     * @param  \Illuminate\Http\Client\Response  $response
     * @return void
     *
     * @throws \CubeConnect\Exceptions\AuthenticationException
     * @throws \CubeConnect\Exceptions\ValidationException
     * @throws \CubeConnect\Exceptions\RateLimitException
     * @throws \CubeConnect\Exceptions\NotFoundException
     * @throws \CubeConnect\Exceptions\CubeConnectException
     */
    protected function handleErrors(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $error = $response->json('error', []);
        $code = $error['code'] ?? '';
        $message = $error['message'] ?? '';
        $details = $error['details'] ?? [];
        $status = $response->status();

        match ($status) {
            401 => throw AuthenticationException::invalidKey($code, $message),
            403 => throw AuthenticationException::forbidden($code, $message),
            404 => throw NotFoundException::resource($code, $message),
            422 => throw ValidationException::withErrors($code, $message, $details),
            429 => throw RateLimitException::exceeded($code, $message),
            default => throw CubeConnectException::serverError($status, $code, $message),
        };
    }
}
