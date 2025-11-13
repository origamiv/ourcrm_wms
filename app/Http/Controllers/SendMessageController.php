<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SendMessageRequest;
use App\Models\SendMessage;
use Illuminate\Http\Request;
use OurCRM\BaseApiController;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;

final class SendMessageController extends BaseApiController
{
    public $model = 'App\\Models\\SendMessage';

    public $requestClass = SendMessageRequest::class;

    public $resource = 'sendmessage';

    /**
     * @OA\Post(
     *     path="/api/sendmessage/list",
     *     summary="Возвращает список отправленных сообщений",
     *     description="Возвращает список отправленных сообщений",
     *     tags={"SendMessage"},
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
    #[Post('/api/sendmessage/list', name: 'sendmessage.index', middleware: 'auth:sanctum')]
    public function index(Request $request)
    {
        return parent::index($request);
    }

    /**
     * @OA\Post(
     *     path="/api/sendmessage/create",
     *     summary="Создает отправленное сообщение",
     *     description="Создает отправленное сообщение",
     *     operationId="storeSendMessage",
     *     tags={"SendMessage"},
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
     *                     property="account_id",
     *                     type="integer",
     *                     description="account_id",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="channel_id",
     *                     type="integer",
     *                     description="channel_id",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="text",
     *                     description="Сообщение",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="files",
     *                     type="json",
     *                     description="Файл",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="response",
     *                     type="json",
     *                     description="Ответ после отправки",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="integer",
     *                     description="0 - new, 1 - sent, 2 - blocked, 3 - in progress",
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
    #[Post('/api/sendmessage/create', name: 'sendmessage.store', middleware: 'auth:sanctum')]
    public function store(Request $request, bool $isAuthNeeded = true)
    {
        return parent::store($request);
    }

    /**
     * @OA\Put(
     *     path="/api/sendmessage/{id}",
     *     summary="Измененяет информацию о отправленном сообщении",
     *     description="Измененяет информацию о отправленном сообщении",
     *     operationId="updateSendMessage",
     *     tags={"SendMessage"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="SendMessage id",
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
     *                     property="account_id",
     *                     type="integer",
     *                     description="account_id",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="channel_id",
     *                     type="integer",
     *                     description="channel_id",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="text",
     *                     description="Сообщение",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="files",
     *                     type="json",
     *                     description="Файл",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="response",
     *                     type="json",
     *                     description="Ответ после отправки",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="integer",
     *                     description="0 - new, 1 - sent, 2 - blocked, 3 - in progress",
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
    #[Put('/api/sendmessage/{id}', name: 'sendmessage.update', middleware: 'auth:sanctum')]
    public function update($id, Request $request, bool $isAuthNeeded = true)
    {
        return parent::update($id, $request);
    }

    /**
     * @OA\Get(
     *     path="/api/sendmessage/{id}",
     *     summary="Показывает информацию о отправленном сообщении",
     *     description="Показывает информацию о отправленном сообщении",
     *     operationId="showSendMessage",
     *     tags={"SendMessage"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="SendMessage id",
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
    #[Get('/api/sendmessage/{id}', name: 'sendmessage.show', middleware: 'auth:sanctum')]
    public function show($id, bool $isAuthNeeded = true)
    {
        return parent::show($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/sendmessage/{id}/delete",
     *     summary="Удаляет отправленное сообщение (soft)",
     *     description="Помечает отправленное сообщение в БД удаленным",
     *     operationId="deleteSendMessage",
     *     tags={"SendMessage"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="SendMessage id",
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
    #[Delete('/api/sendmessage/{id}/delete', name: 'sendmessage.delete', middleware: 'auth:sanctum')]
    public function delete($id)
    {
        return parent::delete($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/sendmessage/{id}/destroy",
     *     summary="Удаляет отправленное сообщение (hard)",
     *     description="Удаляет отправленное сообщение из БД ",
     *     operationId="destroySendMessage",
     *     tags={"SendMessage"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="SendMessage id",
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
    #[Delete('/api/sendmessage/{id}/destroy', name: 'sendmessage.destroy', middleware: 'auth:sanctum')]
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
