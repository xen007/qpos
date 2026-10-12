<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('qpos-login', function (Request $request) {
            if (!$request->isMethod('post')) return Limit::none();
            $email=$request->input('email');
            $account=hash('sha256',is_string($email)?mb_strtolower(trim($email)):'invalid');
            return [Limit::perMinute(10)->by('ip:'.$request->ip()), Limit::perMinute(5)->by('account:'.$account.'|'.$request->ip())];
        });
        foreach (['qpos-forgot'=>5,'qpos-reset'=>5,'qpos-new-password'=>5,'qpos-resend'=>3] as $name=>$maximum) {
            RateLimiter::for($name, fn(Request $request)=>$request->isMethod('post')?Limit::perMinute($maximum)->by($request->ip()):Limit::none());
        }

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
