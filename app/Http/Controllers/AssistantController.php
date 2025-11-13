<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AssistantRequest;
use App\Models\Assistant;
use App\Models\AssistantDocument;
use App\Models\AssistantHistory;
use App\Services\CailaService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OurCRM\BaseApiController;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;

final class AssistantController extends BaseApiController
{
    public $model = 'App\\Models\\Assistant';

    public $requestClass = AssistantRequest::class;

    public $resource = 'assistant';

    public CailaService $cailaService;

    public function __construct(CailaService $cailaService)
    {
        $this->cailaService = $cailaService;
        return parent::__construct();
    }

    /**
     * @OA\Post(
     *     path="/api/assistant/list",
     *     summary="Возвращает список ассистентов",
     *     description="Возвращает список ассистентов",
     *     tags={"Assistant"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\RequestBody(
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(
     *                     property="filter[one][field]",
     *                     type="string",
     *                     description="поле для фильтрации"
     *                 ),
     *                 @OA\Property(
     *                     property="filter[one][op]",
     *                     type="string",
     *                     description="операция фильтра: =, >, <, итд"
     *                 ),
     *                 @OA\Property(
     *                     property="filter[one][val]",
     *                     type="string",
     *                     description="значение поля для фильтрации"
     *                 ),
     *                 example={"filter[one][field]": "status","filter[one][op]": "=","filter[one][val]":"1"},
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="OK"
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Нет доступа"
     *     )
     * )
     */
    #[Post('/api/assistant/list', name: 'assistant.index', middleware: 'auth:sanctum')]
    public function index(Request $request)
    {
        return parent::index($request);
    }

    /**
     * @OA\Post(
     *     path="/api/assistant/create",
     *     summary="Создает ассистент",
     *     description="Создает ассистент",
     *     operationId="storeAssistant",
     *     tags={"Assistant"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\RequestBody(
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="Название",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="shortname",
     *                     type="string",
     *                     description="Короткое",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="prompt",
     *                     type="text",
     *                     description="Промпт",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="data",
     *                     type="text",
     *                     description="Данные",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="func",
     *                     type="text",
     *                     description="Функция",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="ext_id",
     *                     type="string",
     *                     description="ID ассистента",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="ext_model",
     *                     type="string",
     *                     description="Модель",
     *                     example="gpt-4o-mini"
     *                 ),
     *                 @OA\Property(
     *                     property="options",
     *                     type="jsonb",
     *                     description="Опции",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="integer",
     *                     description="Статус",
     *                     example=""
     *                 ),
     *                 example={
     *     "name": "hr",
     * "shortname": "hr",
     * "prompt": "Ты - HR нанимающий бэкенд разработчиков PHP",
     * "data": null,
     * "func": null,
     * "options": null,
     * "ext_model": "gpt-4o-mini",
     * "status": 1
     *     }
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Examples(example="result", value={"success": true}, summary="An result object.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="401",
     *         description="Нет доступа"
     *     )
     * )
     */
    #[Post('/api/assistant/create', name: 'assistant.store', middleware: 'auth:sanctum')]
    public function store(Request $request, bool $isAuthNeeded = true)
    {
        try {

            $data=$this->cailaService->post('/assistants',[
                'instructions' => $request->prompt,
                'name' => $request->name,
                'tools' => (!empty($request->func)) ? json_decode($request->func) : [],
                'model' => (!empty($request->ext_model)) ? $request->ext_model : 'gpt-4o-mini'
            ],);

            $request->request->add(['ext_id' => $data['id']]);
            $request->request->add(['status' => 1]);
            $resp = parent::store($request);
            $history = json_decode($resp->getContent(), true)['data'];
            //dd($history);
            $history['assistant_id'] = $history['id'];
            unset($history['id']);
            $history['version'] = 1;
            AssistantHistory::create($history);
            return $resp;
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    public function getEmbedding($text) {
        $data=$this->cailaService->post('/embeddings', [
            'model' => 'text-embedding-3-small', // или vectorizer-caila-roberta
            'input' => $text,
        ]);

        $embedding = $data['data'][0]['embedding'] ?? null;
        return $embedding;
    }

    public function afterStore(Request $request): int
    {
        foreach ($this->item->data as $file) {
            if (empty($file)) {continue;}
            $content=file_get_contents(storage_path('/app/public/uploads/'.$file));
            if (!empty($content)) {
                $embedding=$this->getEmbedding($content) ?? null;
            }
            //dd($content);
            AssistantDocument::query()->updateOrCreate([
                'assistant_id' => $this->item->id,
                'name' => $file,
            ],
                [
                    'assistant_id' => $this->item->id,
                    'name' => $file,
                    'title' => 'Заголовок',
                    'content' => $content ?? null,
                    'embedding' => $embedding ?? null,
                    'status' => 1,
                ]);
        }
        return parent::afterStore($request);
    }

    public function afterUpdate(Request $request): int
    {
        foreach ($this->item->data as $file) {
            $content=file_get_contents(storage_path('/app/public/uploads/'.$file));
            if (!empty($content)) {
                $embedding=$this->getEmbedding($content) ?? null;
            }
            //dd($content);
            AssistantDocument::query()->updateOrCreate([
                'assistant_id' => $this->item->id,
                'name' => $file,
            ],
                [
                    'assistant_id' => $this->item->id,
                    'name' => $file,
                    'title' => 'Заголовок',
                    'content' => $content ?? null,
                    'embedding' => $embedding ?? null,
                    'status' => 1,
                ]);
        }
        return parent::afterUpdate($request);
    }

    /**
     * @OA\Put(
     *     path="/api/assistant/{id}",
     *     summary="Измененяет информацию о ассистенте",
     *     description="Измененяет информацию о ассистенте",
     *     operationId="updateAssistant",
     *     tags={"Assistant"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Assistant id",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *     @OA\RequestBody(
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="Название",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="shortname",
     *                     type="string",
     *                     description="Короткое",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="prompt",
     *                     type="text",
     *                     description="Промпт",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="data",
     *                     type="text",
     *                     description="Данные",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="func",
     *                     type="text",
     *                     description="Функция",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="ext_id",
     *                     type="string",
     *                     description="ID ассистента",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="ext_model",
     *                     type="string",
     *                     description="Модель",
     *                     example="gpt-4o-mini"
     *                 ),
     *                 @OA\Property(
     *                     property="options",
     *                     type="jsonb",
     *                     description="Опции",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="integer",
     *                     description="Статус",
     *                     example=""
     *                 ),
     *                 example={"name": "Jessica Smith"}
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Examples(example="result", value={"success": true}, summary="An result object."),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="401",
     *         description="Нет доступа"
     *     )
     * )
     */
    #[Put('/api/assistant/{id}', name: 'assistant.update', middleware: 'auth:sanctum')]
    public function update($id, Request $request, bool $isAuthNeeded = true)
    {

        $client = new Client();
        $token = env('CAILA_TOKEN');

        $assistant = Assistant::query()->where('id', $id)->first();

        $data=$this->cailaService->post('/assistants/'.$assistant->ext_id,[
            'instructions' => $request->prompt,
            'name' => $request->name,
            'tools' => (!empty($request->func)) ? json_decode($request->func) : [],
            'model' => (!empty($request->ext_model)) ? $request->ext_model : 'gpt-4o-mini'
        ],);

        $request->request->add(['ext_id' => $data['id']]);
        $request->request->add(['status' => 1]);

        $resp = parent::update($id, $request);
        $history = json_decode($resp->getContent(), true)['data'];
        $history['assistant_id'] = $history['id'];
        $assistantHistory = AssistantHistory::query()
            ->where('assistant_id', $id)
            ->orderBy('version', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        unset($history['id']);
        $history['version'] = 1 + $assistantHistory->version;
        AssistantHistory::create($history);
        return $resp;

    }

    /**
     * @OA\Get(
     *     path="/api/assistant/{id}",
     *     summary="Показывает информацию о ассистенте",
     *     description="Показывает информацию о ассистенте",
     *     operationId="showAssistant",
     *     tags={"Assistant"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Assistant id",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Examples(example="result", value={"success": true}, summary="An result object."),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="401",
     *         description="Нет доступа"
     *     )
     * )
     */
    #[Get('/api/assistant/{id}', name: 'assistant.show', middleware: 'auth:sanctum')]
    public function show($id, bool $isAuthNeeded = true)
    {
        return parent::show($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/assistant/{id}/delete",
     *     summary="Удаляет ассистент (soft)",
     *     description="Помечает ассистент в БД удаленным",
     *     operationId="deleteAssistant",
     *     tags={"Assistant"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Assistant id",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Examples(example="result", value={"success": true}, summary="An result object."),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="401",
     *         description="Нет доступа"
     *     )
     * )
     */
    #[Delete('/api/assistant/{id}/delete', name: 'assistant.delete', middleware: 'auth:sanctum')]
    public function delete($id)
    {
        return parent::delete($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/assistant/{id}/destroy",
     *     summary="Удаляет ассистент (hard)",
     *     description="Удаляет ассистент из БД ",
     *     operationId="destroyAssistant",
     *     tags={"Assistant"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Assistant id",
     *          required=true,
     *          in="path",
     *
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Examples(example="result", value={"success": true}, summary="An result object."),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="401",
     *         description="Нет доступа"
     *     )
     * )
     */
    #[Delete('/api/assistant/{id}/destroy', name: 'assistant.destroy', middleware: 'auth:sanctum')]
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
