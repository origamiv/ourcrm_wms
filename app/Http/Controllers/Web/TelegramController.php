<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Requests\AccountRequest;
use App\Jobs\CommandsJob;
use App\Models\Account;
use App\Models\Menu;
use App\Models\Message;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OurCRM\BaseApiController;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;

final class TelegramController extends Controller
{
    public Client $client;

    public function store(Request $request) {

    }
    public function index($id, Request $request) {
        dd(123);
//        $data=implode("\r\n", $request->all());
//        file_put_contents(date('Y_m_d_H_i_s').'_data.txt', $data);
//        $account=Account::query()->where('login','=',$id)->where('messenger_id','=',1)->first();
//        Message::query()->create([
//            'account_id' => $account->id,
//            'meessager_id' => 1,
//            'src'=>json_encode($request->all(), JSON_UNESCAPED_UNICODE),
//        ]);
//        return response()->json($request->all());
    }
}
