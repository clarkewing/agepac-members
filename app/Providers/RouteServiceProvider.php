<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use App\Channel;
use App\Http\Controllers\PagesController;
use App\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));

            Route::get('pages/{page}', [PagesController::class, 'show'])
                ->where('page', '[a-z0-9]+([a-z0-9-\/][a-z0-9]+)*')
                ->middleware('web')
                ->name('pages.show');
        });

        Route::bind('channel', function ($value) {
            return Channel::withoutGlobalScopes()->where('slug', $value)->firstOrFail();
        });

        Route::bind('post', function ($value) {
            if (Auth::user()->can('restore', Post::class)
                || Auth::user()->can('forceDelete', Post::class)) {
                return Post::withTrashed()->findOrFail($value);
            }

            return Post::findOrFail($value);
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
