<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Your CubeConnect API key. Generate one from the dashboard:
    | Settings > API Keys
    |
    | Each key is automatically scoped to the tenant it was created under —
    | no separate tenant ID is needed.
    |
    */

    'api_key' => env('CUBECONNECT_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Account ID
    |--------------------------------------------------------------------------
    |
    | Your WhatsApp account ID. Find it in the dashboard:
    | Dashboard → WhatsApp Numbers → click the copy icon next to "API ID:"
    |
    | Required for all message sends and campaigns.
    |
    */

    'whatsapp_account_id' => env('CUBECONNECT_WHATSAPP_ACCOUNT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The CubeConnect API base URL. Override for staging/testing environments.
    |
    */

    'base_url' => env('CUBECONNECT_URL', 'https://cubeconnect.io'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time in seconds to wait for an API response.
    |
    */

    'timeout' => env('CUBECONNECT_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Your webhook signing secret from the CubeConnect dashboard.
    | Used to verify that incoming webhook requests are from CubeConnect.
    | Settings > Webhook > Signing Secret
    |
    */

    'webhook_secret' => env('CUBECONNECT_WEBHOOK_SECRET'),

];
