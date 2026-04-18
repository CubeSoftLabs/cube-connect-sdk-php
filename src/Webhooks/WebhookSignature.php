<?php

namespace CubeConnect\Webhooks;

class WebhookSignature
{
    /**
     * التحقق من توقيع الويب هوك.
     *
     * @param  string  $payload  محتوى الطلب الخام
     * @param  string  $signature  قيمة هيدر X-Webhook-Signature
     * @param  string  $timestamp  قيمة هيدر X-Webhook-Timestamp
     * @param  string  $secret  مفتاح التوقيع السري
     * @param  int  $tolerance  أقصى فرق زمني بالثواني (الافتراضي: 300 = 5 دقائق)
     * @return bool
     */
    public static function verify(
        string $payload,
        string $signature,
        string $timestamp,
        string $secret,
        int $tolerance = 300,
    ): bool {
        if ($signature === '' || $timestamp === '' || $secret === '') {
            return false;
        }

        // منع هجمات الإعادة — رفض الطلبات الأقدم من tolerance
        $requestTime = strtotime($timestamp);
        if ($requestTime === false || abs(time() - $requestTime) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
