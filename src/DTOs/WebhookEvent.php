<?php

namespace CubeConnect\DTOs;

class WebhookEvent
{
    public readonly string $event;
    public readonly int $tenantId;
    public readonly string $timestamp;
    public readonly array $data;

    public function __construct(array $payload)
    {
        $this->event = $payload['event'] ?? '';
        $this->tenantId = (int) ($payload['tenant_id'] ?? 0);
        $this->timestamp = $payload['timestamp'] ?? '';
        $this->data = $payload;
    }

    /**
     * إنشاء من طلب Laravel.
     */
    public static function fromRequest(\Illuminate\Http\Request $request): static
    {
        return new static($request->all());
    }

    /**
     * هل الحدث من نوع معين؟
     */
    public function is(string $eventName): bool
    {
        return $this->event === $eventName;
    }

    /**
     * هل الحدث ينتمي لفئة معينة؟ (مثلاً: "message", "campaign", "template", "flow", "account")
     */
    public function category(): string
    {
        return explode('.', $this->event)[0] ?? '';
    }

    /**
     * هل هو حدث اختبار؟
     */
    public function isTest(): bool
    {
        return $this->event === 'webhook.test';
    }

    /**
     * الحصول على قيمة من بيانات الحدث.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    // ── أحداث الرسائل ──

    /** @return bool هل تم تحديث حالة رسالة صادرة */
    public function isMessageStatusUpdated(): bool
    {
        return $this->event === 'message.status_updated';
    }

    /** @return bool هل تم استلام رسالة من عميل */
    public function isMessageReceived(): bool
    {
        return $this->event === 'message.received';
    }

    // ── أحداث الحملات ──

    /** @return bool هل تم إنشاء حملة جديدة */
    public function isCampaignCreated(): bool
    {
        return $this->event === 'campaign.created';
    }

    /** @return bool هل بدأت حملة مجدولة */
    public function isCampaignStarted(): bool
    {
        return $this->event === 'campaign.started';
    }

    /** @return bool هل اكتملت حملة */
    public function isCampaignCompleted(): bool
    {
        return $this->event === 'campaign.completed';
    }

    // ── أحداث القوالب ──

    /** @return bool هل تم تقديم قالب جديد */
    public function isTemplateSubmitted(): bool
    {
        return $this->event === 'template.submitted';
    }

    /** @return bool هل تغيرت حالة قالب */
    public function isTemplateStatusChanged(): bool
    {
        return $this->event === 'template.status_changed';
    }

    // ── أحداث الفلو (الشات بوت) ──

    /** @return bool هل بدأت جلسة فلو */
    public function isFlowSessionStarted(): bool
    {
        return $this->event === 'flow.session_started';
    }

    /** @return bool هل اكتملت جلسة فلو */
    public function isFlowSessionCompleted(): bool
    {
        return $this->event === 'flow.session_completed';
    }

    /** @return bool هل تم إلغاء جلسة فلو */
    public function isFlowSessionCancelled(): bool
    {
        return $this->event === 'flow.session_cancelled';
    }

    // ── أحداث الجودة ──

    /** @return bool هل وقع حدث جودة (حظر أو بلاغ) */
    public function isQualityEvent(): bool
    {
        return $this->event === 'account.quality_event';
    }
}
