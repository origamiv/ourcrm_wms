<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\EnsureWmsAccess;
use App\Models\PersonalAccessToken;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

final class WmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config(['database.connections.pgsql.search_path' => config('wms.postgres_search_path')]);
    }

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        Sanctum::getAccessTokenFromRequestUsing(fn (Request $request) => $request->bearerToken());
        Scramble::configure()->expose(false)->withDocumentTransformers(function ($document) {
            $document->secure(SecurityScheme::http('bearer'));
        });
        Scramble::registerUiRoute('docs/api')->middleware(['web', EnsureWmsAccess::class.':admin']);
        Scramble::registerJsonSpecificationRoute('docs/api.json')->middleware(['web', EnsureWmsAccess::class.':admin']);
        if (! $this->app->routesAreCached()) {
            Route::middleware('api')->prefix('api')->group(base_path('routes/api.php'));
        }
    }
}
