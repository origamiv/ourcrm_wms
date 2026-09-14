<?php

declare(strict_types=1);

namespace App\Providers;

use App\Console\CreatorCommand;
use App\Console\DbConnectionsCommand;
use App\Console\DbSyncCommand;
use App\Console\EmbeddingsCommand;
use App\Console\ExportTableCommand;
use App\Console\FreshCommand;
use App\Console\ImportTableCommand;
use App\Console\InitializeWmsTenantCommand;
use App\Console\MessengerChannelsCommand;
use App\Console\PostingCommand;
use App\Console\ProjectMenuCommand;
use App\Console\ProjectSyncPermissionsCommand;
use App\Console\StartAutomateCommand;
use App\Console\SubscribeToTriggersCommand;
use App\Console\ToolsAllYamlCommand;
use App\Console\ToolsCreateCommand;
use App\Console\ToolsCreateModuleCommand;
use App\Console\ToolsYamlCommand;
use App\Mixins\SchemaMixin;
use App\Models\Message;
use App\Observers\MessageObserver;
use App\Observers\Sync\SyncMessageObserver;
use App\View\Components\Menu;
use App\View\Components\Table;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Sanctum::getAccessTokenFromRequestUsing(function (Request $request) {
            return $request->bearerToken() ?? $request->input('token');
        });

//        Queue::before(function (JobProcessing $event) {
//            dump($event->job->payload());
//            $json=json_decode($event->job->payload(),true);
//
//            $command=$json['data']['command'];
//            $commandData=unserialize($command);
//            /** var ProcessTelegramUpdate $commandData */
//            //dump($commandData);
//            $commandData->handle();
//            //$message=json_decode();
//            //dump($message);
//            // $event->connectionName
//            // $event->job
//            // $event->job->payload()
//        });

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        $this->registerCommands();


        Blade::component('menu', Menu::class);
        Blade::component('table', Table::class);

        //Schema::mixin(new SchemaMixin);
    }

    public function registerCommands(): void
    {
        $this->commands([
            FreshCommand::class,
            ExportTableCommand::class,
            ImportTableCommand::class,
            ToolsCreateCommand::class,
            ToolsCreateModuleCommand::class,
            ToolsYamlCommand::class,
            ToolsAllYamlCommand::class,
            ProjectMenuCommand::class,
            ProjectSyncPermissionsCommand::class,
            DbConnectionsCommand::class,
            InitializeWmsTenantCommand::class,
        ]);
    }
}
