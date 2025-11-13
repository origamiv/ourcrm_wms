<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Requests\AccountRequest;
use App\Jobs\CommandsJob;
use App\Models\Account;
use App\Models\Menu;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OurCRM\BaseApiController;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;

final class StartController extends Controller
{
    public Client $client;

    public function index(Request $request)
    {
        $module = 'messenger';
        $chapter = 'channels';
        //$r=User::find(2)->tokens()->delete();
        $token=User::find(2)->createToken('api')->plainTextToken;

        $page=$request->getRequestUri();
        $menu=Menu::query()->where('page','=',$page)->first();

        if (!empty($menu->api)) {

            $api=str_replace('itstaffer.ru','our24.ru',$menu->api);
//            $api=str_replace('itstaffer.ru','ourtest.net',$menu->api);

            $this->client = new Client();
            $response = $this->client->request('POST', $api . '/list', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json', // Optional: Common header for API requests
                ],
            ]);

            //dd($response);

            $json = $response->getBody()->getContents();
            $data = json_decode($json, true);
            //dd($data['data']);


            //$rows=\Illuminate\Support\Facades\DB::table($module.'.'.$chapter)->get()->toArray();
            return view('start', ['rows' => $data['data'], 'module' => $module, 'chapter' => $chapter]);
        }
        else {
            return view('start', ['rows' => [], 'module' => $module, 'chapter' => $chapter]);
        }

    }
}
