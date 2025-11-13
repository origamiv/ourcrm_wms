<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\AssistantChatUpdated;
use App\Http\Requests\AssistantChatRequest;
use App\Models\AssistantChat;
use App\Services\CailaService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use OurCRM\BaseApiController;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;

final class AssistantChatController extends BaseApiController
{
    public $model = 'App\\Models\\AssistantChat';

    public $requestClass = AssistantChatRequest::class;

    public $resource = 'assistantchat';
    public CailaService $cailaService;

    public function __construct(CailaService $cailaService)
    {
        $this->cailaService = $cailaService;
        return parent::__construct();
    }

    /**
     * @OA\Post(
     *     path="/api/assistantchat/list",
     *     summary="Возвращает список чатов AI ассистента",
     *     description="Возвращает список чатов AI ассистента",
     *     tags={"AssistantChat"},
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
    #[Post('/api/assistantchat/list', name: 'assistantchat.index', middleware: 'auth:sanctum')]
    public function index(Request $request)
    {
        return parent::index($request);
    }

    /**
     * @OA\Post(
     *     path="/api/assistantchat/create",
     *     summary="Создает чат AI ассистента",
     *     description="Создает чат AI ассистента",
     *     operationId="storeAssistantChat",
     *     tags={"AssistantChat"},
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
     *                     property="assistant_id",
     *                     type="integer",
     *                     description="Ассистент",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="account_id",
     *                     type="integer",
     *                     description="Аккаунт",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="channel_id",
     *                     type="integer",
     *                     description="Канал",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="ext_thread_id",
     *                     type="string",
     *                     description="Тред ассистента",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="direction",
     *                     type="string",
     *                     description="Направление",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="options",
     *                     type="jsonb",
     *                     description="Настройки",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="integer",
     *                     description="Статус",
     *                     example=""
     *                 ),
     *                 example={
     * "name": "Диалог с клиентом",
     * "shortname": "client_dialog",
     * "assistant_id": 2,
     * "account_id": 1,
     * "channel_id": 23,
     * "direction": "in"
     * }
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
    #[Post('/api/assistantchat/create', name: 'assistantchat.store')]
    public function store(Request $request, bool $isAuthNeeded = true)
    {

        $data=$this->cailaService->post('/threads', [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Здравствуйте',
                ],
            ],
        ]);


        $request->request->add(['ext_thread_id'=>$data['id']]);
        $request->request->add(['status'=>1]);
        return parent::store($request);
    }

    /**
     * @OA\Put(
     *     path="/api/assistantchat/{id}",
     *     summary="Измененяет информацию о чате AI ассистента",
     *     description="Измененяет информацию о чате AI ассистента",
     *     operationId="updateAssistantChat",
     *     tags={"AssistantChat"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="AssistantChat id",
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
     *                     property="assistant_id",
     *                     type="integer",
     *                     description="Ассистент",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="account_id",
     *                     type="integer",
     *                     description="Аккаунт",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="channel_id",
     *                     type="integer",
     *                     description="Канал",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="ext_thread_id",
     *                     type="string",
     *                     description="Тред ассистента",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="direction",
     *                     type="string",
     *                     description="Направление",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="options",
     *                     type="jsonb",
     *                     description="Настройки",
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
    #[Put('/api/assistantchat/{id}', name: 'assistantchat.update', middleware: 'auth:sanctum')]

    public function update($id, Request $request, bool $isAuthNeeded = true)
    {
        $assistantChat = AssistantChat::findOrFail($id);
        $oldDirection = $assistantChat->direction;

        $response = parent::update($id, $request);

        $assistantChat->refresh();

        if ($assistantChat->direction !== $oldDirection) {
            event(new AssistantChatUpdated($assistantChat));
        }

        return $response;
    }

    /**
     * @OA\Get(
     *     path="/api/assistantchat/{id}",
     *     summary="Показывает информацию о чате AI ассистента",
     *     description="Показывает информацию о чате AI ассистента",
     *     operationId="showAssistantChat",
     *     tags={"AssistantChat"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="AssistantChat id",
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
    #[Get('/api/assistantchat/{id}', name: 'assistantchat.show', middleware: 'auth:sanctum')]
    public function show($id, bool $isAuthNeeded = true)
    {
        return parent::show($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/assistantchat/{id}/delete",
     *     summary="Удаляет чат AI ассистента (soft)",
     *     description="Помечает чат AI ассистента в БД удаленным",
     *     operationId="deleteAssistantChat",
     *     tags={"AssistantChat"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="AssistantChat id",
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
    #[Delete('/api/assistantchat/{id}/delete', name: 'assistantchat.delete', middleware: 'auth:sanctum')]
    public function delete($id)
    {
        return parent::delete($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/assistantchat/{id}/destroy",
     *     summary="Удаляет чат AI ассистента (hard)",
     *     description="Удаляет чат AI ассистента из БД ",
     *     operationId="destroyAssistantChat",
     *     tags={"AssistantChat"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="AssistantChat id",
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
    #[Delete('/api/assistantchat/{id}/destroy', name: 'assistantchat.destroy', middleware: 'auth:sanctum')]
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
