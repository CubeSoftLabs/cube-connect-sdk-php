# CubeConnect for Laravel

<p>
<a href="https://packagist.org/packages/cubesoftware/cube-connect-sdk-php"><img src="https://img.shields.io/packagist/v/cubesoftware/cube-connect-sdk-php.svg" alt="Latest Version"></a>
<a href="https://packagist.org/packages/cubesoftware/cube-connect-sdk-php"><img src="https://img.shields.io/packagist/l/cubesoftware/cube-connect-sdk-php.svg" alt="License"></a>
<a href="https://packagist.org/packages/cubesoftware/cube-connect-sdk-php"><img src="https://img.shields.io/packagist/php-v/cubesoftware/cube-connect-sdk-php.svg" alt="PHP Version"></a>
</p>

Official Laravel SDK for the [CubeConnect](https://cubeconnect.io) WhatsApp Business Platform.

## Installation

```bash
composer require cubesoftware/cube-connect-sdk-php
```

The package auto-discovers its service provider and facade. No manual registration required.

### Publish Configuration

```bash
php artisan vendor:publish --tag=cubeconnect-config
```

### Environment Variables

```
CUBECONNECT_API_KEY=your_api_key_here
CUBECONNECT_WHATSAPP_ACCOUNT_ID=your_account_id_here
```

| Variable | Default | Description |
|----------|---------|-------------|
| `CUBECONNECT_API_KEY` | — | Your API key — **Settings → API** in the dashboard |
| `CUBECONNECT_WHATSAPP_ACCOUNT_ID` | — | Your WhatsApp account ID — **Dashboard → WhatsApp Numbers → API ID:** |
| `CUBECONNECT_URL` | `https://cubeconnect.io` | API base URL |
| `CUBECONNECT_TIMEOUT` | `30` | Request timeout in seconds |
| `CUBECONNECT_WEBHOOK_SECRET` | `null` | Webhook signing secret for signature verification |

## Multiple WhatsApp Numbers

If your account has more than one connected number, set a default in your `.env` and override it per call using the `$whatsappAccountId` parameter:

```php
// Send from the default number (CUBECONNECT_WHATSAPP_ACCOUNT_ID)
CubeConnect::sendTemplate('+966501234567', 'order_confirmation', 'ar', ['ORD-1234']);

// Send from a different number
CubeConnect::sendTemplate(
    '+966501234567',
    'offer_reminder',
    'ar',
    ['50%'],
    null,                  // $scheduledAt
    null,                  // $timezone
    '01JX_MARKETING',      // $whatsappAccountId — override
);

// List templates for a specific number
$templates = CubeConnect::getTemplates('APPROVED', '01JX_MARKETING');

// Create a campaign from a specific number
CubeConnect::createCampaign([
    'message_type'        => 'template',
    'template_name'       => 'offer_reminder',
    'template_language'   => 'ar',
    'recipients'          => [...],
    'whatsapp_account_id' => '01JX_MARKETING',  // override
]);
```

Find each number's ID in **Dashboard → WhatsApp Numbers → API ID:**.

## Usage

### sendText()

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `$phone` | string | Yes | Recipient phone number with country code |
| `$body` | string | Yes | Message text (max 4096 characters) |
| `$scheduledAt` | string\|null | No | ISO 8601 datetime for scheduled delivery |
| `$timezone` | string\|null | No | IANA timezone. Required when `$scheduledAt` is set |
| `$whatsappAccountId` | string\|null | No | Override the default WhatsApp account (useful with multiple numbers) |

```php
use CubeConnect\Facades\CubeConnect;

$response = CubeConnect::sendText('+966501234567', 'مرحباً بك في متجرنا!');

$response->status;       // "queued"
->messageLogId; // "01JXXX..."
```

Scheduled delivery:

```php
$response = CubeConnect::sendText(
    '+966501234567',
    'تذكير: موعدك غداً الساعة 10 صباحاً.',
    '2026-05-01T09:00:00', // $scheduledAt (ISO 8601)
    'Asia/Riyadh',         // $timezone (IANA)
);

$response->status;      // "scheduled"
$response->scheduledAt; // "2026-05-01T06:00:00Z" (UTC)
```

### sendTemplate()

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `$phone` | string | Yes | Recipient phone number with country code |
| `$name` | string | Yes | Template name (e.g., `order_confirmation`) |
| `$languageCode` | string | Yes | Language code matching the approved template (e.g., `ar`, `en_US`) |
| `$params` | array | No | Parameters mapping to `{{1}}`, `{{2}}`, etc. |
| `$scheduledAt` | string\|null | No | ISO 8601 datetime for scheduled delivery |
| `$timezone` | string\|null | No | IANA timezone. Required when `$scheduledAt` is set |
| `$whatsappAccountId` | string\|null | No | Override the default WhatsApp account (useful with multiple numbers) |

```php
use CubeConnect\Facades\CubeConnect;

$response = CubeConnect::sendTemplate(
    '+966501234567',          // $phone
    'order_confirmation',     // $name
    'ar',                     // $languageCode
    ['ORD-1234', '500 SAR'],  // $params → {{1}}, {{2}}
);

$response->status;               // "queued"
$response->messageLogId;         // 4521
$response->conversationCategory; // "UTILITY"
$response->queued();             // true
```

Without parameters:

```php
$response = CubeConnect::sendTemplate('+966501234567', 'welcome_message', 'ar');
```

Scheduled delivery:

```php
$response = CubeConnect::sendTemplate(
    '+966501234567',
    'appointment_reminder',
    'ar',                      // $languageCode
    ['Dr. Ahmed', '10:00 AM'], // $params
    '2026-05-01T09:00:00',     // $scheduledAt (ISO 8601)
    'Asia/Riyadh',             // $timezone (IANA)
);

$response->status;      // "scheduled"
$response->scheduledAt; // "2026-05-01T06:00:00Z" (UTC)
```

### createCampaign()

Send a pre-approved template to a large list in a single API call.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `message_type` | string | Yes | Must be `template` |
| `template_name` | string | Yes | Template name (same as `$name` in `sendTemplate()`) |
| `template_language` | string | Yes | Language code (same as `$languageCode` in `sendTemplate()`) |
| `recipients` | array | Yes | List of recipients. Max 50,000 |
| `recipients[].phone` | string | Yes | Recipient phone number |
| `recipients[].name` | string | No | Recipient display name |
| `recipients[].variables` | array | No | Per-recipient variables (e.g., `['1' => 'Ahmed', '2' => 'ORD-1234']`) |
| `campaign_name` | string | No | Human-readable campaign name |
| `scheduled_at` | string | No | ISO 8601 datetime for scheduled delivery |
| `timezone` | string | No | IANA timezone. Required when `scheduled_at` is set |
| `whatsapp_account_id` | string | No | Override the default WhatsApp account |

```php
$campaign = CubeConnect::createCampaign([
    'message_type'      => 'template',
    'template_name'     => 'order_confirmation',
    'template_language' => 'ar',
    'recipients'        => [
        ['phone' => '+966501234567', 'name' => 'Ahmed', 'variables' => ['1' => 'Ahmed', '2' => 'ORD-1234']],
        ['phone' => '+966509876543', 'name' => 'Sara',  'variables' => ['1' => 'Sara',  '2' => 'ORD-5678']],
    ],
    'campaign_name' => 'Order Notifications',
]);

$campaign->campaignId; // "01JX..."
$campaign->status;     // "pending"
$campaign->totalCount; // 2
```

Scheduled delivery:

```php
$campaign = CubeConnect::createCampaign([
    'message_type'      => 'template',
    'template_name'     => 'offer_reminder',
    'template_language' => 'ar',
    'recipients'        => [...],
    'campaign_name'     => 'Flash Sale',
    'scheduled_at'      => '2026-05-01T09:00:00', // ISO 8601
    'timezone'          => 'Asia/Riyadh',            // IANA timezone
]);

$campaign->status;        // "pending"
$campaign->isScheduled(); // true
```

#### Get Campaign Status

```php
$campaign = CubeConnect::getCampaign($campaignId);

$campaign->status;        // "processing", "completed", "cancelled", "failed"
$campaign->totalCount;    // 500
$campaign->sentCount;     // 320
$campaign->failedCount;   // 12
$campaign->isCompleted(); // true
```

#### Cancel a Scheduled Campaign

```php
$ok = CubeConnect::cancelCampaign($campaignId); // true on success
```

### List Templates

```php
$templates = CubeConnect::getTemplates('APPROVED');

foreach ($templates as $t) {
    $t->name;         // "order_confirmation"
    $t->paramsCount;  // 3
    $t->body;         // "Hello {{1}}, your order {{2}} has been shipped."
    $t->header;       // null
    $t->isApproved(); // true
}
```

### Get Message Status

Retrieve the current delivery status of a previously sent message using the `messageLogId` returned by `sendTemplate()`.

```php
$msg = CubeConnect::getMessageStatus(4521);

$msg->messageLogId;  // 4521
$msg->status;        // "delivered"
$msg->toPhone;       // "966501234567"
$msg->messageType;   // "template"
$msg->metaMessageId; // "wamid.HBgN..."
$msg->sentAt;        // "2026-05-01T07:05:00Z"
$msg->scheduledAt;   // null
$msg->costAmount;    // 0.05
$msg->costCurrency;  // "SAR"
$msg->errorMessage;  // null (set if status is "failed")

$msg->isSent();      // true if status is "sent"
$msg->isDelivered(); // true if status is "delivered"
$msg->isRead();      // true if status is "read"
$msg->isFailed();    // true if status is "failed"
$msg->isScheduled(); // true if status is "scheduled"
```

### Health Check

```php
$health = CubeConnect::health();
// ['status' => 'healthy', 'checks' => [...], 'timestamp' => '...']
```

## Webhooks

Receive real-time notifications from CubeConnect for messages, campaigns, templates, chatbot flows, and quality events.

### Setup

```
CUBECONNECT_WEBHOOK_SECRET=your_webhook_secret_here
```

### Signature Verification Middleware

```php
// routes/api.php
use CubeConnect\Webhooks\WebhookHandler;

Route::post('/cubeconnect/webhook', [WebhookController::class, 'handle'])
    ->middleware(WebhookHandler::class);
```

### Handling Webhook Events

```php
use CubeConnect\DTOs\WebhookEvent;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = WebhookEvent::fromRequest($request);

        match (true) {
            $event->isMessageReceived()       => $this->handleMessage($event),
            $event->isMessageStatusUpdated()  => $this->handleStatus($event),
            $event->isCampaignCompleted()     => $this->handleCampaign($event),
            $event->isTemplateStatusChanged() => $this->handleTemplate($event),
            $event->isFlowSessionCompleted()  => $this->handleFlow($event),
            $event->isQualityEvent()          => $this->handleQuality($event),
            default => null,
        };

        return response('OK', 200);
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

## Dependency Injection

```php
use CubeConnect\Contracts\Messaging;

class OrderController extends Controller
{
    public function shipped(Order $order, Messaging $messaging)
    {
        $messaging->sendTemplate(
            $order->customer_phone,
            'order_shipped',
            'ar',
            [$order->id, $order->tracking_number],
        );
    }
}
```

## Response Objects

### MessageResponse

Returned by `sendText()` and `sendTemplate()`:

| Property | Type | Description |
|----------|------|-------------|
| `status` | `string` | `queued` for immediate delivery, `scheduled` for future delivery |
| `messageLogId` | `string` | Unique tracking ID |
| `conversationCategory` | `string` | `MARKETING`, `UTILITY`, or `AUTHENTICATION` |
| `cost` | `float` | Message cost |
| `scheduledAt` | `string\|null` | UTC datetime if scheduled, otherwise `null` |

```php
$response->queued();    // true if status is "queued"
$response->scheduled(); // true if status is "scheduled"
$response->toArray();   // Array representation
```

### CampaignResponse

Returned by `createCampaign()` and `getCampaign()`:

| Property | Type | Description |
|----------|------|-------------|
| `campaignId` | `string` | Unique campaign ULID |
| `name` | `string\|null` | Campaign name |
| `status` | `string` | `pending`, `processing`, `completed`, `cancelled`, `failed` |
| `totalCount` | `int` | Total recipients |
| `sentCount` | `int` | Successfully sent |
| `failedCount` | `int` | Failed deliveries |
| `scheduledAt` | `string\|null` | Scheduled UTC datetime |
| `createdAt` | `string` | Creation timestamp |

```php
$campaign->isScheduled(); // true if pending with a scheduledAt
$campaign->isCompleted(); // true if status is "completed"
$campaign->isCancelled(); // true if status is "cancelled"
$campaign->toArray();     // Array representation
```

### MessageStatusResponse

Returned by `getMessageStatus()`:

| Property | Type | Description |
|----------|------|-------------|
| `messageLogId` | `string` | Unique message log ID |
| `status` | `string` | `queued`, `scheduled`, `sent`, `delivered`, `read`, or `failed` |
| `toPhone` | `string` | Recipient phone number |
| `messageType` | `string` | `template` or `text` |
| `metaMessageId` | `string\|null` | WhatsApp message ID (set after delivery) |
| `sentAt` | `string\|null` | UTC datetime when sent to WhatsApp |
| `scheduledAt` | `string\|null` | UTC datetime of scheduled delivery |
| `costAmount` | `float` | Message cost |
| `costCurrency` | `string` | Currency code (e.g., `SAR`) |
| `errorMessage` | `string\|null` | Error details if status is `failed` |
| `createdAt` | `string` | UTC creation datetime |

```php
$msg->isSent();      // true if status is "sent"
$msg->isDelivered(); // true if status is "delivered"
$msg->isRead();      // true if status is "read"
$msg->isFailed();    // true if status is "failed"
$msg->isScheduled(); // true if status is "scheduled"
$msg->toArray();     // Array representation
```

## Error Reference

| HTTP | Error Code | Cause |
|------|------------|-------|
| 401 | `AUTHENTICATION_REQUIRED` | No API key provided in the request |
| 401 | `INVALID_API_KEY` | API key is invalid or has been revoked |
| 403 | `FORBIDDEN` | API key does not have permission for this action |
| 403 | `API_KEY_NO_TENANT` | API key is not linked to any account |
| 404 | `NOT_FOUND` | The requested resource does not exist |
| 404 | `TEMPLATE_NOT_FOUND` | Template name not found in your account |
| 422 | `VALIDATION_ERROR` | Request failed input validation — check `error.details` for field-level errors |
| 422 | `INVALID_PHONE_NUMBER` | Phone number is not in a valid international format |
| 422 | `NO_ACTIVE_ACCOUNT` | No connected WhatsApp number found for the given `whatsapp_account_id` |
| 422 | `MISSING_ACCESS_TOKEN` | The selected WhatsApp number has no Meta access token configured |
| 422 | `TEMPLATE_LANGUAGE_MISMATCH` | Language code does not match any approved version of this template |
| 422 | `TEMPLATE_PARAMS_MISMATCH` | Fewer parameters provided than the template requires |
| 429 | `RATE_LIMIT_EXCEEDED` | Too many API requests — apply exponential backoff and retry |
| 429 | `PLAN_LIMIT_REACHED` | Monthly message quota reached — upgrade your plan |
| 429 | `SUBSCRIPTION_EXPIRED` | Subscription has expired |
| 500 | `MESSAGE_SEND_FAILED` | WhatsApp API rejected or failed to deliver the message |
| 500 | `INTERNAL_ERROR` | Unexpected server error — contact support if this persists |
| 503 | `SERVICE_DEGRADED` | One or more platform services are temporarily unavailable |

## Error Handling

```php
use CubeConnect\Facades\CubeConnect;
use CubeConnect\Exceptions\AuthenticationException;
use CubeConnect\Exceptions\ValidationException;
use CubeConnect\Exceptions\RateLimitException;
use CubeConnect\Exceptions\NotFoundException;
use CubeConnect\Exceptions\CubeConnectException;

try {
    CubeConnect::sendTemplate('+966501234567', 'order_confirmation', 'ar', ['ORD-1234']);
} catch (AuthenticationException $e) {
    // 401/403 — Invalid API key or permissions
    $e->errorCode;  // "INVALID_API_KEY", "FORBIDDEN", ...
    $e->statusCode; // 401 or 403
} catch (ValidationException $e) {
    // 422 — Invalid request data
    $e->errorCode; // "VALIDATION_ERROR", "INVALID_PHONE_NUMBER", ...
    $e->errors;    // ['phone' => ['The phone field is required.']]
} catch (NotFoundException $e) {
    // 404 — Resource not found
    $e->errorCode; // "NOT_FOUND", "TEMPLATE_NOT_FOUND"
} catch (RateLimitException $e) {
    // 429 — Rate or plan limit exceeded
    $e->errorCode; // "RATE_LIMIT_EXCEEDED", "PLAN_LIMIT_REACHED", ...
} catch (CubeConnectException $e) {
    // 5xx or network errors
    $e->errorCode;  // "INTERNAL_ERROR", "MESSAGE_SEND_FAILED", ...
    $e->statusCode;
}
```

## Documentation

Full API documentation is available at [docs.cubeconnect.io](https://docs.cubeconnect.io).

## License

CubeConnect for Laravel is open-sourced software licensed under the [MIT license](LICENSE).

Copyright © 2026 [Cube Software](https://cubesoftware.io) (CubeSoftLabs). All rights reserved.
