<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MessageRequest;
use App\Models\Message;
use Illuminate\Http\Request;
use OurCRM\BaseApiController;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;

final class MessageController extends BaseApiController
{
    public $model = 'App\\Models\\Message';

    public $requestClass = MessageRequest::class;

    public $resource = 'message';

    /**
     * @OA\Post(
     *     path="/api/message/list",
     *     summary="Возвращает список сообщений",
     *     description="Возвращает список сообщений",
     *     tags={"Message"},
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
    #[Post('/api/message/list', name: 'message.index', middleware: 'auth:sanctum')]
    public function index(Request $request)
    {
        return parent::index($request);
    }

    /**
     * @OA\Post(
     *     path="/api/message/create",
     *     summary="Создает сообщение",
     *     description="Создает сообщение",
     *     operationId="storeMessage",
     *     tags={"Message"},
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
     *                     description="Аккаунт",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="bot_id",
     *                     type="integer",
     *                     description="Бот",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="messenger_id",
     *                     type="integer",
     *                     description="Мессенджер",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="message_id",
     *                     type="string",
     *                     description="ИД сообщения",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="channel_id",
     *                     type="integer",
     *                     description="Канал",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="channel",
     *                     type="string",
     *                     description="ИД канала в мессенджере",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="channel_name",
     *                     type="string",
     *                     description="Название канала",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="thread_id",
     *                     type="integer",
     *                     description="ID темы",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="thread",
     *                     type="string",
     *                     description="Тема в мессенджере",
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
     *                     description="Сообщение",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="msg_date",
     *                     type="date",
     *                     description="Время сообщения",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="napr",
     *                     type="integer",
     *                     description="Направление",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="from_id",
     *                     type="string",
     *                     description="Отправитель",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="from_name",
     *                     type="string",
     *                     description="Имя отправителя",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="src",
     *                     type="jsonb",
     *                     description="Исходник",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="type_msg",
     *                     type="string",
     *                     description="Тип сообщения",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="user",
     *                     type="string",
     *                     description="Псевдоним",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="comment",
     *                     type="string",
     *                     description="Примечание",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="resend_status",
     *                     type="integer",
     *                     description="Статус пересылки",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="string",
     *                     description="Телефон",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="channel_id_our",
     *                     type="integer",
     *                     description="ID канала",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="date_view",
     *                     type="date",
     *                     description="Время чтения сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="parent_message_id",
     *                     type="string",
     *                     description="Связанное сообщение",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="messenger_user_id",
     *                     type="string",
     *                     description="Пользователь мессенджера",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="is_hidden_for_user",
     *                     type="integer",
     *                     description="Скрытое",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="integer",
     *                     description="Статус",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="is_media",
     *                     type="integer",
     *                     description="Есть вложения",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="comments_cnt",
     *                     type="integer",
     *                     description="кол-во комментариев",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="replies_cnt",
     *                     type="integer",
     *                     description="кол-во ответов",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="type_msg_id",
     *                     type="integer",
     *                     description="тип сообщения",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="is_read",
     *                     type="integer",
     *                     description="Сообщение прочитано или нет",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="parent_message_src",
     *                     type="json",
     *                     description="parent_message_src",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="is_tagged",
     *                     type="integer",
     *                     description="is_tagged",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="reaction",
     *                     type="json",
     *                     description="reaction",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="edited_status",
     *                     type="integer",
     *                     description="edited_status",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="edited_cnt",
     *                     type="integer",
     *                     description="edited_cnt",
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
    #[Post('/api/message/create', name: 'message.store')]
    public function store(Request $request, bool $isAuthNeeded = true)
    {
        return parent::store($request);
    }

    /**
     * @OA\Put(
     *     path="/api/message/{id}",
     *     summary="Измененяет информацию о сообщении",
     *     description="Измененяет информацию о сообщении",
     *     operationId="updateMessage",
     *     tags={"Message"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Message id",
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
     *                     description="Аккаунт",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="bot_id",
     *                     type="integer",
     *                     description="Бот",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="messenger_id",
     *                     type="integer",
     *                     description="Мессенджер",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="message_id",
     *                     type="string",
     *                     description="ИД сообщения",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="channel_id",
     *                     type="integer",
     *                     description="Канал",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="channel",
     *                     type="string",
     *                     description="ИД канала в мессенджере",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="channel_name",
     *                     type="string",
     *                     description="Название канала",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="thread_id",
     *                     type="integer",
     *                     description="ID темы",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="thread",
     *                     type="string",
     *                     description="Тема в мессенджере",
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
     *                     description="Сообщение",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="msg_date",
     *                     type="date",
     *                     description="Время сообщения",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="napr",
     *                     type="integer",
     *                     description="Направление",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="from_id",
     *                     type="string",
     *                     description="Отправитель",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="from_name",
     *                     type="string",
     *                     description="Имя отправителя",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="src",
     *                     type="jsonb",
     *                     description="Исходник",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="type_msg",
     *                     type="string",
     *                     description="Тип сообщения",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="user",
     *                     type="string",
     *                     description="Псевдоним",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="comment",
     *                     type="string",
     *                     description="Примечание",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="resend_status",
     *                     type="integer",
     *                     description="Статус пересылки",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="string",
     *                     description="Телефон",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="channel_id_our",
     *                     type="integer",
     *                     description="ID канала",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="date_view",
     *                     type="date",
     *                     description="Время чтения сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="parent_message_id",
     *                     type="string",
     *                     description="Связанное сообщение",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="messenger_user_id",
     *                     type="string",
     *                     description="Пользователь мессенджера",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="is_hidden_for_user",
     *                     type="integer",
     *                     description="Скрытое",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="integer",
     *                     description="Статус",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="is_media",
     *                     type="integer",
     *                     description="Есть вложения",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="comments_cnt",
     *                     type="integer",
     *                     description="кол-во комментариев",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="replies_cnt",
     *                     type="integer",
     *                     description="кол-во ответов",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="type_msg_id",
     *                     type="integer",
     *                     description="тип сообщения",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="is_read",
     *                     type="integer",
     *                     description="Сообщение прочитано или нет",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="parent_message_src",
     *                     type="json",
     *                     description="parent_message_src",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="is_tagged",
     *                     type="integer",
     *                     description="is_tagged",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="reaction",
     *                     type="json",
     *                     description="reaction",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="edited_status",
     *                     type="integer",
     *                     description="edited_status",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="edited_cnt",
     *                     type="integer",
     *                     description="edited_cnt",
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
    #[Put('/api/message/{id}', name: 'message.update', middleware: 'auth:sanctum')]
    public function update($id, Request $request, bool $isAuthNeeded = true)
    {
        return parent::update($id, $request);
    }

    /**
     * @OA\Get(
     *     path="/api/message/{id}",
     *     summary="Показывает информацию о сообщении",
     *     description="Показывает информацию о сообщении",
     *     operationId="showMessage",
     *     tags={"Message"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Message id",
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
    #[Get('/api/message/{id}', name: 'message.show', middleware: 'auth:sanctum')]
    public function show($id, bool $isAuthNeeded = true)
    {
        return parent::show($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/message/{id}/delete",
     *     summary="Удаляет сообщение (soft)",
     *     description="Помечает сообщение в БД удаленным",
     *     operationId="deleteMessage",
     *     tags={"Message"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Message id",
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
    #[Delete('/api/message/{id}/delete', name: 'message.delete', middleware: 'auth:sanctum')]
    public function delete($id)
    {
        return parent::delete($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/message/{id}/destroy",
     *     summary="Удаляет сообщение (hard)",
     *     description="Удаляет сообщение из БД ",
     *     operationId="destroyMessage",
     *     tags={"Message"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Message id",
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
    #[Delete('/api/message/{id}/destroy', name: 'message.destroy', middleware: 'auth:sanctum')]
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
