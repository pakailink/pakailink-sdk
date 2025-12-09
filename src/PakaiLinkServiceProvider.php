<?php

namespace PakaiLink;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use PakaiLink\Http\Controllers\PakaiLinkCallbackController;
use PakaiLink\Http\Middleware\ValidatePakaiLinkSignature;
use PakaiLink\Services\PakaiLinkAuthService;
use PakaiLink\Services\PakaiLinkBalanceService;
use PakaiLink\Services\PakaiLinkCallbackService;
use PakaiLink\Services\PakaiLinkEmoneyService;
use PakaiLink\Services\PakaiLinkHttpClient;
use PakaiLink\Services\PakaiLinkMerchantService;
use PakaiLink\Services\PakaiLinkQrisService;
use PakaiLink\Services\PakaiLinkRetailService;
use PakaiLink\Services\PakaiLinkService;
use PakaiLink\Services\PakaiLinkSignatureService;
use PakaiLink\Services\PakaiLinkTopupService;
use PakaiLink\Services\PakaiLinkTransferService;
use PakaiLink\Services\PakaiLinkVirtualAccountService;

class PakaiLinkServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/pakailink.php',
            'pakailink'
        );

        // Signature Service
        $this->app->singleton(PakaiLinkSignatureService::class, function ($app) {
            return new PakaiLinkSignatureService(
                privateKeyPath: config('pakailink.keys.private_key_path'),
                clientSecret: config('pakailink.credentials.client_secret'),
            );
        });

        // Auth Service
        $this->app->singleton(PakaiLinkAuthService::class, function ($app) {
            return new PakaiLinkAuthService(
                signatureService: $app->make(PakaiLinkSignatureService::class),
                baseUrl: config('pakailink.base_url'),
                clientId: config('pakailink.credentials.client_id'),
                clientSecret: config('pakailink.credentials.client_secret'),
            );
        });

        // HTTP Client
        $this->app->singleton(PakaiLinkHttpClient::class, function ($app) {
            return new PakaiLinkHttpClient(
                authService: $app->make(PakaiLinkAuthService::class),
                signatureService: $app->make(PakaiLinkSignatureService::class),
                baseUrl: config('pakailink.base_url'),
                clientId: config('pakailink.credentials.client_id'),
                partnerId: config('pakailink.credentials.partner_id'),
                channelId: config('pakailink.credentials.channel_id'),
                timeout: config('pakailink.timeout'),
                retryTimes: config('pakailink.retry_times'),
                retryDelay: config('pakailink.retry_delay'),
            );
        });

        // Main Service
        $this->app->singleton(PakaiLinkService::class, function ($app) {
            return new PakaiLinkService(
                client: $app->make(PakaiLinkHttpClient::class),
            );
        });

        // Payment Method Services
        $this->app->singleton(PakaiLinkVirtualAccountService::class, function ($app) {
            return new PakaiLinkVirtualAccountService(
                client: $app->make(PakaiLinkHttpClient::class),
                partnerServiceId: config('pakailink.credentials.partner_id'),
            );
        });

        $this->app->singleton(PakaiLinkQrisService::class, function ($app) {
            return new PakaiLinkQrisService(
                client: $app->make(PakaiLinkHttpClient::class),
            );
        });

        $this->app->singleton(PakaiLinkTransferService::class, function ($app) {
            return new PakaiLinkTransferService(
                client: $app->make(PakaiLinkHttpClient::class),
            );
        });

        $this->app->singleton(PakaiLinkEmoneyService::class, function ($app) {
            return new PakaiLinkEmoneyService(
                client: $app->make(PakaiLinkHttpClient::class),
            );
        });

        $this->app->singleton(PakaiLinkRetailService::class, function ($app) {
            return new PakaiLinkRetailService(
                client: $app->make(PakaiLinkHttpClient::class),
            );
        });

        $this->app->singleton(PakaiLinkTopupService::class, function ($app) {
            return new PakaiLinkTopupService(
                client: $app->make(PakaiLinkHttpClient::class),
            );
        });

        $this->app->singleton(PakaiLinkBalanceService::class, function ($app) {
            return new PakaiLinkBalanceService(
                client: $app->make(PakaiLinkHttpClient::class),
            );
        });

        $this->app->singleton(PakaiLinkMerchantService::class, function ($app) {
            return new PakaiLinkMerchantService(
                client: $app->make(PakaiLinkHttpClient::class),
            );
        });

        // Callback Service
        $this->app->singleton(PakaiLinkCallbackService::class, function ($app) {
            return new PakaiLinkCallbackService(
                signatureService: $app->make(PakaiLinkSignatureService::class),
            );
        });
    }

    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/pakailink.php' => config_path('pakailink.php'),
            ], 'pakailink');
        }

        // Register routes
        $this->registerRoutes();

        // Register middleware
        $this->app['router']->aliasMiddleware('pakailink.signature', ValidatePakaiLinkSignature::class);
    }

    protected function registerRoutes(): void
    {
        if (! config('pakailink.callbacks.enabled', true)) {
            return;
        }

        $prefix = config('pakailink.callbacks.prefix', 'api/pakailink/callbacks');

        Route::prefix($prefix)
            ->middleware(['api', 'pakailink.signature'])
            ->group(function () {
                Route::post('/virtual-account', [PakaiLinkCallbackController::class, 'virtualAccount'])
                    ->name('pakailink.callbacks.virtual-account');

                Route::post('/qris', [PakaiLinkCallbackController::class, 'qris'])
                    ->name('pakailink.callbacks.qris');

                Route::post('/emoney', [PakaiLinkCallbackController::class, 'emoney'])
                    ->name('pakailink.callbacks.emoney');

                Route::post('/transfer', [PakaiLinkCallbackController::class, 'transfer'])
                    ->name('pakailink.callbacks.transfer');

                Route::post('/retail', [PakaiLinkCallbackController::class, 'retail'])
                    ->name('pakailink.callbacks.retail');

                Route::post('/topup', [PakaiLinkCallbackController::class, 'topup'])
                    ->name('pakailink.callbacks.topup');
            });
    }
}
