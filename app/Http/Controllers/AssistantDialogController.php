<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AssistantDialogRequest;
use App\Models\AssistantDialog;
use Illuminate\Http\Request;
use OurCRM\BaseApiController;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;

final class AssistantDialogController extends BaseApiController
{
    public $model = 'App\\Models\\AssistantDialog';

    public $requestClass = AssistantDialogRequest::class;

    public $resource = 'assistantdialog';

    /**
     * @OA\Post(
     *     path="/api/assistantdialog/list",
     *     summary="Возвращает список диалогов AI ассистента",
     *     description="Возвращает список диалогов AI ассистента",
     *     tags={"AssistantDialog"},
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
    #[Post('/api/assistantdialog/list', name: 'assistantdialog.index', middleware: 'auth:sanctum')]
    public function index(Request $request)
    {
        return parent::index($request);
    }

    /**
     * @OA\Post(
     *     path="/api/assistantdialog/create",
     *     summary="Создает диалоги AI ассистента",
     *     description="Создает диалоги AI ассистента",
     *     operationId="storeAssistantDialog",
     *     tags={"AssistantDialog"},
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
     *                     property="query",
     *                     type="text",
     *                     description="Запрос",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="answer",
     *                     type="text",
     *                     description="Ответ",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="direction",
     *                     type="string",
     *                     description="направление",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="ext_run_id",
     *                     type="string",
     *                     description="ID запуска",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="ext_run_data",
     *                     type="jsonb",
     *                     description="Инфо запуска",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="total_tokens",
     *                     type="integer",
     *                     description="Потрачено токенов",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="price",
     *                     type="float",
     *                     description="Цена",
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
    #[Post('/api/assistantdialog/create', name: 'assistantdialog.store', middleware: 'auth:sanctum')]
    public function store(Request $request, bool $isAuthNeeded = true)
    {
        return parent::store($request);
    }

    /**
     * @OA\Put(
     *     path="/api/assistantdialog/{id}",
     *     summary="Измененяет информацию о диалогах AI ассистента",
     *     description="Измененяет информацию о диалогах AI ассистента",
     *     operationId="updateAssistantDialog",
     *     tags={"AssistantDialog"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="AssistantDialog id",
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
     *                     property="query",
     *                     type="text",
     *                     description="Запрос",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="answer",
     *                     type="text",
     *                     description="Ответ",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="direction",
     *                     type="string",
     *                     description="направление",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="ext_run_id",
     *                     type="string",
     *                     description="ID запуска",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="ext_run_data",
     *                     type="jsonb",
     *                     description="Инфо запуска",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="total_tokens",
     *                     type="integer",
     *                     description="Потрачено токенов",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="price",
     *                     type="float",
     *                     description="Цена",
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
    #[Put('/api/assistantdialog/{id}', name: 'assistantdialog.update', middleware: 'auth:sanctum')]
    public function update($id, Request $request, bool $isAuthNeeded = true)
    {
        return parent::update($id, $request);
    }

    /**
     * @OA\Get(
     *     path="/api/assistantdialog/{id}",
     *     summary="Показывает информацию о диалогах AI ассистента",
     *     description="Показывает информацию о диалогах AI ассистента",
     *     operationId="showAssistantDialog",
     *     tags={"AssistantDialog"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="AssistantDialog id",
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
    #[Get('/api/assistantdialog/{id}', name: 'assistantdialog.show', middleware: 'auth:sanctum')]
    public function show($id, bool $isAuthNeeded = true)
    {
        return parent::show($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/assistantdialog/{id}/delete",
     *     summary="Удаляет диалоги AI ассистента (soft)",
     *     description="Помечает диалоги AI ассистента в БД удаленным",
     *     operationId="deleteAssistantDialog",
     *     tags={"AssistantDialog"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="AssistantDialog id",
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
    #[Delete('/api/assistantdialog/{id}/delete', name: 'assistantdialog.delete', middleware: 'auth:sanctum')]
    public function delete($id)
    {
        return parent::delete($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/assistantdialog/{id}/destroy",
     *     summary="Удаляет диалоги AI ассистента (hard)",
     *     description="Удаляет диалоги AI ассистента из БД ",
     *     operationId="destroyAssistantDialog",
     *     tags={"AssistantDialog"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="AssistantDialog id",
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
    #[Delete('/api/assistantdialog/{id}/destroy', name: 'assistantdialog.destroy', middleware: 'auth:sanctum')]
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
