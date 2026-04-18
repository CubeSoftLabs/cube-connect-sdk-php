<?php

namespace CubeConnect;

use CubeConnect\Contracts\Messaging;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class CubeConnectServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the CubeConnect services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cubeconnect.php', 'cubeconnect');

        $this->app->singleton(Messaging::class, function ($app) {
            $config = $app['config']['cubeconnect'];

            return new CubeConnect(
                apiKey: $config['api_key'] ?? '',
                baseUrl: $config['base_url'] ?? 'https://cubeconnect.io',
                timeout: (int) ($config['timeout'] ?? 30),
            );
        });

        $this->app->alias(Messaging::class, CubeConnect::class);
    }

    /**
     * Bootstrap the CubeConnect services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/cubeconnect.php' => $this->app->configPath('cubeconnect.php'),
        ], 'cubeconnect-config');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            Messaging::class,
            CubeConnect::class,
        ];
    }
}
