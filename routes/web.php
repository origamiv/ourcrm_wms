<?php

declare(strict_types=1);

use Livewire\Volt\Volt;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $rows = \Illuminate\Support\Facades\DB::table('messenger.accounts')->get()->toArray();
    return view('start', ['rows' => $rows]);
});

Route::get('/web/{module}', function (string $module) {
    $rows = \Illuminate\Support\Facades\DB::table('messenger.accounts')->get()->toArray();
    return view('start', ['rows' => $rows, 'module' => $module]);
});
Route::get('/web/{module}/{chapter}', 'App\Http\Controllers\Web\StartController@index');
Route::get('/hook/telegram/{ident}', 'App\Http\Controllers\Web\TelegramController@index');
Route::post('/hook/telegram/{ident}', 'App\Http\Controllers\Web\TelegramController@index');

Route::prefix('web/chats/ai/assistants')->group(function () {
    Route::get('/create', 'App\Http\Controllers\Web\AssistantController@create');
    Route::get('/{id}', 'App\Http\Controllers\Web\AssistantController@edit');
    Route::post('/{id}', 'App\Http\Controllers\Web\AssistantController@update');
});

//Volt::route('/', 'users.index');
//$menus=\Modules\Lists\Models\Menu::query()->where('shortname','like','messenger.%')->get();
//foreach($menus as $menu){
//    Volt::route($menu->page, 'table.index');
//    //\Illuminate\Support\Facades\Route::get($menu->page, 'App\Http\Controllers\\ListController@index');
//};
