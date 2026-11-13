<?php

namespace App\Providers;

use App\Contracts\ErpCustomerSource;
use App\Contracts\ErpDeliveryNoteSource;
use App\Contracts\ErpOrderSource;
use App\Models\DeliveryNote;
use App\Services\AccentDictionary;
use App\Services\Erp\SqlServerCustomerSource;
use App\Services\Erp\SqlServerDeliveryNoteSource;
use App\Services\Erp\SqlServerOrderSource;
use App\Services\UnitCatalog;
use App\Support\VietnameseAccentRestorer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            VietnameseAccentRestorer::class,
            fn ($app) => $app->make(AccentDictionary::class)->restorer(),
        );
        $this->app->singleton(AccentDictionary::class);
        $this->app->bind(ErpCustomerSource::class, SqlServerCustomerSource::class);
        $this->app->bind(ErpDeliveryNoteSource::class, SqlServerDeliveryNoteSource::class);
        $this->app->bind(ErpOrderSource::class, SqlServerOrderSource::class);
        $this->app->scoped(UnitCatalog::class); // nạp bảng alias một lần mỗi request
    }

    public function boot(): void
    {
        // Chống dò mật khẩu: 5 lần đăng nhập/phút cho mỗi cặp email + IP.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));

        // Thanh trên của app: lần đồng bộ phiếu từ ERP gần nhất, theo giờ Việt Nam.
        View::composer('layouts.app', function (ViewInstance $view) {
            $syncedAt = DeliveryNote::max('synced_at');
            $view->with('erpSyncedAt', $syncedAt ? Carbon::parse($syncedAt)->setTimezone(config('delivery_note.timezone')) : null);
        });
    }
}
