<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\ChannelRepository;
use App\Repositories\ChannelRepositoryInterface;
use App\Repositories\CustomerRepository;
use App\Repositories\CustomerRepositoryInterface;
use App\Services\ChannelService;
use App\Services\ChannelServiceInterface;
use App\Services\CustomerService;
use App\Services\CustomerServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(ChannelRepositoryInterface::class, ChannelRepository::class);
        
        // Service bindings
        $this->app->bind(CustomerServiceInterface::class, CustomerService::class);
        $this->app->bind(ChannelServiceInterface::class, ChannelService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
