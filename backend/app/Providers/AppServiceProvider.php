<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use App\Policies\BannerPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CollectionPolicy;
use App\Policies\CouponPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\SettingPolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Banner::class, BannerPolicy::class);
        Gate::policy(Collection::class, CollectionPolicy::class);
        Gate::policy(Coupon::class, CouponPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Purchase::class, PurchasePolicy::class);

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
