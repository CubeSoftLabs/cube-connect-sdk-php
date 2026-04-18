<?php

namespace CubeConnect\Webhooks;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookHandler
{
    /**
     * Middleware للتحقق من توقيع webhook الوارد من CubeConnect.
     *
     * الاستخدام:
     *   Route::post('/webhook', ...)->middleware(WebhookHandler::class);
     *
     * أو في Kernel.php:
     *   'cubeconnect.webhook' => \CubeConnect\Webhooks\WebhookHandler::class
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('cubeconnect.webhook_secret');

        // إذا لم يتم ضبط السر، تخطي التحقق
        if (! is_string($secret) || $secret === '') {
            return $next($request);
        }

        $signature = $request->header('X-Webhook-Signature', '');
        $timestamp = $request->header('X-Webhook-Timestamp', '');

        if (! WebhookSignature::verify($request->getContent(), $signature, $timestamp, $secret)) {
            return response()->json(['error' => 'Invalid webhook signature'], 401);
        }

        return $next($request);
    }
}
