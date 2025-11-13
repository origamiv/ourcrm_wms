<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ChannelRequest;
use App\Models\Channel;
use Illuminate\Http\Request;
use OurCRM\BaseApiController;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;

final class ChannelController extends BaseApiController
{
    public $model = 'App\\Models\\Channel';

    public $requestClass = ChannelRequest::class;

    public $resource = 'channel';

    /**
     * @OA\Post(
     *     path="/api/channel/list",
     *     summary="Возвращает список каналов",
     *     description="Возвращает список каналов",
     *     tags={"Channel"},
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
    #[Post('/api/channel/list', name: 'channel.index', middleware: 'auth:sanctum')]
    public function index(Request $request)
    {
        return parent::index($request);
    }

    /**
     * @OA\Post(
     *     path="/api/channel/create",
     *     summary="Создает канал",
     *     description="Создает канал",
     *     operationId="storeChannel",
     *     tags={"Channel"},
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
     *                     property="channel",
     *                     type="string",
     *                     description="Название в мессенджере",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="username",
     *                     type="string",
     *                     description="Псевдоним",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="string",
     *                     description="Телефон",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="fn_avatar",
     *                     type="string",
     *                     description="Аватар",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="type_channel",
     *                     type="integer",
     *                     description="Тип канала",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="cnt",
     *                     type="integer",
     *                     description="Количество сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="cnt_parsed",
     *                     type="integer",
     *                     description="Количество полученных сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="cnt_people",
     *                     type="integer",
     *                     description="Количество участников",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="cnt_people_parsed",
     *                     type="integer",
     *                     description="Количество полученных участников",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="can_view_participants",
     *                     type="integer",
     *                     description="Можно просматривать участников",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="date_last_message",
     *                     type="date",
     *                     description="Время последнего сообщения",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="date_last_check",
     *                     type="date",
     *                     description="Время последней проверки сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="frequency",
     *                     type="integer",
     *                     description="Частота сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="integer",
     *                     description="Статус",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="srcDialog",
     *                     type="jsonb",
     *                     description="Исходник",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="src",
     *                     type="jsonb",
     *                     description="Исходник",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="last_message_id",
     *                     type="integer",
     *                     description="Последнее сообщение",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="cnt_unread",
     *                     type="integer",
     *                     description="Число непрочитанных сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="date_last_read",
     *                     type="date",
     *                     description="Время прочтения сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="last_message_src",
     *                     type="json",
     *                     description="last_message_src",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="tagged_at",
     *                     type="date",
     *                     description="tagged_at",
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
    #[Post('/api/channel/create', name: 'channel.store', middleware: 'auth:sanctum')]
    public function store(Request $request, bool $isAuthNeeded = true)
    {
        return parent::store($request);
    }

    /**
     * @OA\Put(
     *     path="/api/channel/{id}",
     *     summary="Измененяет информацию о канале",
     *     description="Измененяет информацию о канале",
     *     operationId="updateChannel",
     *     tags={"Channel"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Channel id",
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
     *                     property="channel",
     *                     type="string",
     *                     description="Название в мессенджере",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="username",
     *                     type="string",
     *                     description="Псевдоним",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="string",
     *                     description="Телефон",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="fn_avatar",
     *                     type="string",
     *                     description="Аватар",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="type_channel",
     *                     type="integer",
     *                     description="Тип канала",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="cnt",
     *                     type="integer",
     *                     description="Количество сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="cnt_parsed",
     *                     type="integer",
     *                     description="Количество полученных сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="cnt_people",
     *                     type="integer",
     *                     description="Количество участников",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="cnt_people_parsed",
     *                     type="integer",
     *                     description="Количество полученных участников",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="can_view_participants",
     *                     type="integer",
     *                     description="Можно просматривать участников",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="date_last_message",
     *                     type="date",
     *                     description="Время последнего сообщения",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="date_last_check",
     *                     type="date",
     *                     description="Время последней проверки сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="frequency",
     *                     type="integer",
     *                     description="Частота сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="integer",
     *                     description="Статус",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="srcDialog",
     *                     type="jsonb",
     *                     description="Исходник",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="src",
     *                     type="jsonb",
     *                     description="Исходник",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="last_message_id",
     *                     type="integer",
     *                     description="Последнее сообщение",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="cnt_unread",
     *                     type="integer",
     *                     description="Число непрочитанных сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="date_last_read",
     *                     type="date",
     *                     description="Время прочтения сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="last_message_src",
     *                     type="json",
     *                     description="last_message_src",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="tagged_at",
     *                     type="date",
     *                     description="tagged_at",
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
    #[Put('/api/channel/{id}', name: 'channel.update', middleware: 'auth:sanctum')]
    public function update($id, Request $request, bool $isAuthNeeded = true)
    {
        return parent::update($id, $request);
    }

    /**
     * @OA\Get(
     *     path="/api/channel/{id}",
     *     summary="Показывает информацию о канале",
     *     description="Показывает информацию о канале",
     *     operationId="showChannel",
     *     tags={"Channel"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Channel id",
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
    #[Get('/api/channel/{id}', name: 'channel.show', middleware: 'auth:sanctum')]
    public function show($id, bool $isAuthNeeded = true)
    {
        return parent::show($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/channel/{id}/delete",
     *     summary="Удаляет канал (soft)",
     *     description="Помечает канал в БД удаленным",
     *     operationId="deleteChannel",
     *     tags={"Channel"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Channel id",
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
    #[Delete('/api/channel/{id}/delete', name: 'channel.delete', middleware: 'auth:sanctum')]
    public function delete($id)
    {
        return parent::delete($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/channel/{id}/destroy",
     *     summary="Удаляет канал (hard)",
     *     description="Удаляет канал из БД ",
     *     operationId="destroyChannel",
     *     tags={"Channel"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Channel id",
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
    #[Delete('/api/channel/{id}/destroy', name: 'channel.destroy', middleware: 'auth:sanctum')]
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
