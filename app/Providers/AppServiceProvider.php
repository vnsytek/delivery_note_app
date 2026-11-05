<?php

namespace App\Providers;

use App\Contracts\ErpCustomerSource;
use App\Contracts\ErpDeliveryNoteSource;
use App\Services\Erp\SqlServerCustomerSource;
use App\Services\Erp\SqlServerDeliveryNoteSource;
use App\Services\UnitCatalog;
use App\Support\VietnameseAccentRestorer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            VietnameseAccentRestorer::class,
            fn () => new VietnameseAccentRestorer(config('accent_phrases')),
        );
        $this->app->bind(ErpCustomerSource::class, SqlServerCustomerSource::class);
        $this->app->bind(ErpDeliveryNoteSource::class, SqlServerDeliveryNoteSource::class);
        $this->app->scoped(UnitCatalog::class); // nạp bảng alias một lần mỗi request
    }

    public function boot(): void
    {
        // Chống dò mật khẩu: 5 lần đăng nhập/phút cho mỗi cặp email + IP.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}
