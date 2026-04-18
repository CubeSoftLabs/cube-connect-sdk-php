# CubeConnect for Laravel

<p>
<a href="https://packagist.org/packages/cubesoftware/cube-connect-sdk-php"><img src="https://img.shields.io/packagist/v/cubesoftware/cube-connect-sdk-php.svg" alt="Latest Version"></a>
<a href="https://packagist.org/packages/cubesoftware/cube-connect-sdk-php"><img src="https://img.shields.io/packagist/l/cubesoftware/cube-connect-sdk-php.svg" alt="License"></a>
<a href="https://packagist.org/packages/cubesoftware/cube-connect-sdk-php"><img src="https://img.shields.io/packagist/php-v/cubesoftware/cube-connect-sdk-php.svg" alt="PHP Version"></a>
</p>

Official Laravel SDK for the [CubeConnect](https://cubeconnect.io) WhatsApp Business Platform.

## Installation

Install the package via Composer:

```bash
composer require cubesoftware/cube-connect-sdk-php
```

The package auto-discovers its service provider and facade. No manual registration required.

### Publish Configuration

```bash
php artisan vendor:publish --tag=cubeconnect-config
```

### Environment Variables

Add your API key to `.env`:

```
CUBECONNECT_API_KEY=your_api_key_here
```

| Variable | Default | Description |
|----------|---------|-------------|
| `CUBECONNECT_API_KEY` | — | Your API key from the dashboard |
| `CUBECONNECT_URL` | `https://cubeconnect.io` | API base URL |
| `CUBECONNECT_TIMEOUT` | `30` | Request timeout in seconds |
| `CUBECONNECT_WEBHOOK_SECRET` | `null` | Webhook signing secret for signature verification |

## Usage

### Sending a Text Message

```php
use CubeConnect\Facades\CubeConnect;

$response = CubeConnect::sendText('+966501234567', 'Your order has been confirmed.');

$response->status;               // "queued"
$response->messageLogId;         // 4521
$response->conversationCategory; // "SERVICE"
$response->queued();             // true
```

> **Note:** Text messages require the recipient to have messaged you within the last 24 hours. Outside this window, use a [template message](#sending-a-template-message).

### Sending a Template Message

```php
use CubeConnect\Facades\CubeConnect;

$response = CubeConnect::sendTemplate(
    '+966501234567',
    'order_confirmation',
    ['ORD-1234', '500 SAR']
);

// With explicit language code (default: en_US)
$response = CubeConnect::sendTemplate(
    '+966501234567',
    'order_confirmation',
    ['ORD-1234', '500 SAR'],
    'ar'
);
```

Parameters map to `{{1}}`, `{{2}}`, etc. in the template body. The SDK automatically converts them to the Meta components format. Templates can be sent at any time.

### Health Check

```php
$health = CubeConnect::health();
// ['status' => 'healthy', 'checks' => [...], 'timestamp' => '...']
```

## Webhooks

Receive real-time notifications from CubeConnect for messages, campaigns, templates, chatbot flows, and quality events.

### Setup

1. Configure your webhook URL and signing secret in **Settings → Webhook** on your CubeConnect dashboard
2. Add the secret to your `.env`:

```
CUBECONNECT_WEBHOOK_SECRET=your_webhook_secret_here
```

### Verifying Webhook Signatures

Use the included middleware to automatically verify signatures:

```php
// routes/api.php
use CubeConnect\Webhooks\WebhookHandler;

Route::post('/cubeconnect/webhook', [WebhookController::class, 'handle'])
    ->middleware(WebhookHandler::class);
```

Or register it as a named middleware in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'cubeconnect.webhook' => \CubeConnect\Webhooks\WebhookHandler::class,
    ]);
})
```

Then use it:

```php
Route::post('/cubeconnect/webhook', [WebhookController::class, 'handle'])
    ->middleware('cubeconnect.webhook');
```

### Manual Signature Verification

```php
use CubeConnect\Webhooks\WebhookSignature;

$isValid = WebhookSignature::verify(
    payload: $request->getContent(),
    signature: $request->header('X-Webhook-Signature'),
    timestamp: $request->header('X-Webhook-Timestamp'),
    secret: config('cubeconnect.webhook_secret'),
);
```

### Handling Webhook Events

Use the `WebhookEvent` DTO for clean event handling:

```php
use CubeConnect\DTOs\WebhookEvent;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = WebhookEvent::fromRequest($request);

        match (true) {
            $event->isMessageReceived() => $this->handleMessage($event),
            $event->isMessageStatusUpdated() => $this->handleStatus($event),
            $event->isCampaignCompleted() => $this->handleCampaign($event),
            $event->isTemplateStatusChanged() => $this->handleTemplate($event),
            $event->isFlowSessionCompleted() => $this->handleFlow($event),
            $event->isQualityEvent() => $this->handleQuality($event),
            default => null,
        };

        return response('OK', 200);
    }

    private function handleMessage(WebhookEvent $event)
    {
        $from = $event->get('from');           // "966501234567"
        $content = $event->get('content');     // "Hello, I need help"
        $type = $event->get('type');           // "text"
    }

    private function handleStatus(WebhookEvent $event)
    {
        $messageId = $event->get('message_id');
        $status = $event->get('status');       // "delivered", "read", "failed"
    }

    private function handleCampaign(WebhookEvent $event)
    {
        $name = $event->get('name');
        $sent = $event->get('sent_count');
        $failed = $event->get('failed_count');
    }

    private function handleTemplate(WebhookEvent $event)
    {
        $templateName = $event->get('template_name');
        $status = $event->get('status');       // "approved", "rejected"
    }

    private function handleFlow(WebhookEvent $event)
    {
        $phone = $event->get('customer_phone');
        $flowId = $event->get('flow_id');
    }

    private function handleQuality(WebhookEvent $event)
    {
        $type = $event->get('type');           // "block" or "report"
        $phone = $event->get('user_phone');
    }
}
```

### Supported Events

| Event | Method | Description |
|-------|--------|-------------|
| `message.status_updated` | `isMessageStatusUpdated()` | Message status change (sent, delivered, read, failed) |
| `message.received` | `isMessageReceived()` | Incoming message from a customer |
| `campaign.created` | `isCampaignCreated()` | New campaign created |
| `campaign.started` | `isCampaignStarted()` | Campaign execution started |
| `campaign.completed` | `isCampaignCompleted()` | Campaign finished |
| `template.submitted` | `isTemplateSubmitted()` | Template submitted to Meta |
| `template.status_changed` | `isTemplateStatusChanged()` | Template approved, rejected, or paused |
| `flow.session_started` | `isFlowSessionStarted()` | Chatbot flow session started |
| `flow.session_completed` | `isFlowSessionCompleted()` | Chatbot flow session completed |
| `flow.session_cancelled` | `isFlowSessionCancelled()` | Session cancelled by customer |
| `account.quality_event` | `isQualityEvent()` | Quality event (block or report) |
| `webhook.test` | `isTest()` | Connection test ping |

### WebhookEvent Helpers

```php
$event->event;      // "message.received"
$event->tenantId;   // 1
$event->timestamp;  // "2026-03-10T14:30:00+03:00"
$event->category(); // "message"
$event->is('message.received'); // true
$event->isTest();   // false
$event->toArray();  // Full payload array
```

## Dependency Injection

You may inject the client using the contract or the concrete class:

```php
use CubeConnect\Contracts\Messaging;

class OrderController extends Controller
{
    public function shipped(Order $order, Messaging $messaging)
    {
        $messaging->sendTemplate(
            $order->customer_phone,
            'order_shipped',
            [$order->id, $order->tracking_number]
        );
    }
}
```

## Error Handling

The SDK throws specific exceptions for each error type. All exceptions include an `errorCode` property matching the [unified API error codes](https://docs.cubeconnect.io/api/errors):

```php
use CubeConnect\Facades\CubeConnect;
use CubeConnect\Exceptions\AuthenticationException;
use CubeConnect\Exceptions\ValidationException;
use CubeConnect\Exceptions\RateLimitException;
use CubeConnect\Exceptions\NotFoundException;
use CubeConnect\Exceptions\CubeConnectException;

try {
    CubeConnect::sendText('+966501234567', 'Hello!');
} catch (AuthenticationException $e) {
    // 401 — Invalid or missing API key
    // 403 — Insufficient permissions or tenant issues
    $e->errorCode;  // "INVALID_API_KEY", "AUTHENTICATION_REQUIRED",
                     // "API_KEY_NO_TENANT", "TENANT_NOT_FOUND", "FORBIDDEN"
    $e->statusCode; // 401 or 403
} catch (ValidationException $e) {
    // 422 — Invalid request data
    $e->errorCode; // "VALIDATION_ERROR", "NO_ACTIVE_ACCOUNT",
                   // "MISSING_ACCESS_TOKEN", "INVALID_PHONE_NUMBER"
    $e->errors;    // ['phone' => ['The phone field is required.']]
} catch (NotFoundException $e) {
    // 404 — Resource not found
    $e->errorCode; // "NOT_FOUND", "TEMPLATE_NOT_FOUND"
} catch (RateLimitException $e) {
    // 429 — Rate limit or plan limit exceeded
    $e->errorCode; // "RATE_LIMIT_EXCEEDED", "PLAN_LIMIT_REACHED", "SUBSCRIPTION_EXPIRED"
} catch (CubeConnectException $e) {
    // 5xx or network errors
    $e->errorCode;  // "INTERNAL_ERROR", "MESSAGE_SEND_FAILED", "CONNECTION_FAILED"
    $e->statusCode;
}
```

All exceptions extend `CubeConnectException`, so you can catch the base class for generic handling.

## Response Object

All message methods return a `MessageResponse` with the following properties:

| Property | Type | Description |
|----------|------|-------------|
| `status` | `string` | `queued` on success |
| `messageLogId` | `int` | Unique tracking ID |
| `conversationCategory` | `string` | `SERVICE`, `MARKETING`, `UTILITY`, or `AUTHENTICATION` |
| `cost` | `float` | Message cost |

```php
$response->queued();   // true if status is "queued"
$response->toArray();  // Array representation
```

## Architecture

The package follows SOLID principles:

- **`Contracts\Messaging`** — Interface for the messaging client. Bind your own implementation if needed.
- **`CubeConnect`** — Default implementation using Laravel's HTTP client.
- **`CubeConnectServiceProvider`** — Deferred provider that only loads when the service is used.
- **`Facades\CubeConnect`** — Static proxy backed by the `Messaging` contract.
- **`Webhooks\WebhookHandler`** — Middleware for verifying webhook signatures.
- **`Webhooks\WebhookSignature`** — HMAC-SHA256 signature verification utility.
- **`DTOs\WebhookEvent`** — Value object for parsing and handling webhook events.

## Documentation

Full API documentation is available at [docs.cubeconnect.io](https://docs.cubeconnect.io).

## License

CubeConnect for Laravel is open-sourced software licensed under the [MIT license](LICENSE).

Copyright © 2026 [Cube Software](https://cubesoftware.io) (CubeSoftLabs). All rights reserved.
