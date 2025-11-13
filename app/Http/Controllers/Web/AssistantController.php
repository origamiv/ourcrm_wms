<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Requests\AccountRequest;
use App\Jobs\CommandsJob;
use App\Models\Account;
use App\Models\Assistant;
use App\Models\AssistantHistory;
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

final class AssistantController extends Controller
{
    public Client $client;

    public function create(Request $request) {
        return view('assistants.create');
    }

    public function update($id, Request $request) {
        $validated=$request->all();
        if (!empty($validated['data'])) {
            $data = explode("\r\n", trim($validated['data']));
            $items = [];
            foreach ($data as $item) {
                if (!empty(trim($item))) {
                    $items[] = $item;
                }
            }
            $validated['data'] = $items;
        }
        else {
            $validated['data'] = [];
        }

        $assistant=Assistant::query()->where('id', $id)->first();
        $assistant->update($validated);


        $history=$assistant->toArray();
        $history['assistant_id']=$history['id'];
        $assistantHistory=AssistantHistory::query()
            ->where('assistant_id',$id)
            ->orderBy('version', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        unset($history['id']);
        $history['version']=1+$assistantHistory->version;
        if(is_array($history['data'])) { $history['data']=json_encode($history['data']);};
        AssistantHistory::create($history);
        //$json=json_encode($items, JSON_UNESCAPED_UNICODE);
        return redirect('/web/chats/ai/assistants/'.$id);
    }
    public function edit($id, Request $request) {
        $assistant=Assistant::query()->where('id', $id)->first();
        //dd($assistant);
        return view('assistants.edit', ['assistant'=>$assistant->toArray()]);
    }
}
