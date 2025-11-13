<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ChannelUserRequest;
use App\Models\ChannelUser;
use Illuminate\Http\Request;
use OurCRM\BaseApiController;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;

final class ChannelUserController extends BaseApiController
{
    public $model = 'App\\Models\\ChannelUser';

    public $requestClass = ChannelUserRequest::class;

    public $resource = 'channeluser';

    /**
     * @OA\Post(
     *     path="/api/channeluser/list",
     *     summary="Возвращает список пользователей канала",
     *     description="Возвращает список пользователей канала",
     *     tags={"ChannelUser"},
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
    #[Post('/api/channeluser/list', name: 'channeluser.index', middleware: 'auth:sanctum')]
    public function index(Request $request)
    {
        return parent::index($request);
    }

    /**
     * @OA\Post(
     *     path="/api/channeluser/create",
     *     summary="Создает пользователи канала",
     *     description="Создает пользователи канала",
     *     operationId="storeChannelUser",
     *     tags={"ChannelUser"},
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
     *                     property="user_id",
     *                     type="integer",
     *                     description="ID польз у нас",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="channel_id",
     *                     type="integer",
     *                     description="ID канала у нас",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="mess_user_id",
     *                     type="string",
     *                     description="ID польз в мессенджере",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="mess_channel",
     *                     type="string",
     *                     description="ID канала в мессенджере",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="src",
     *                     type="json",
     *                     description="src",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="account_id",
     *                     type="integer",
     *                     description="account_id",
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
    #[Post('/api/channeluser/create', name: 'channeluser.store', middleware: 'auth:sanctum')]
    public function store(Request $request, bool $isAuthNeeded = true)
    {
        return parent::store($request);
    }

    /**
     * @OA\Put(
     *     path="/api/channeluser/{id}",
     *     summary="Измененяет информацию о пользователях канала",
     *     description="Измененяет информацию о пользователях канала",
     *     operationId="updateChannelUser",
     *     tags={"ChannelUser"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="ChannelUser id",
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
     *                     property="user_id",
     *                     type="integer",
     *                     description="ID польз у нас",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="channel_id",
     *                     type="integer",
     *                     description="ID канала у нас",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="mess_user_id",
     *                     type="string",
     *                     description="ID польз в мессенджере",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="mess_channel",
     *                     type="string",
     *                     description="ID канала в мессенджере",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="src",
     *                     type="json",
     *                     description="src",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="account_id",
     *                     type="integer",
     *                     description="account_id",
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
    #[Put('/api/channeluser/{id}', name: 'channeluser.update', middleware: 'auth:sanctum')]
    public function update($id, Request $request, bool $isAuthNeeded = true)
    {
        return parent::update($id, $request);
    }

    /**
     * @OA\Get(
     *     path="/api/channeluser/{id}",
     *     summary="Показывает информацию о пользователях канала",
     *     description="Показывает информацию о пользователях канала",
     *     operationId="showChannelUser",
     *     tags={"ChannelUser"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="ChannelUser id",
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
    #[Get('/api/channeluser/{id}', name: 'channeluser.show', middleware: 'auth:sanctum')]
    public function show($id, bool $isAuthNeeded = true)
    {
        return parent::show($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/channeluser/{id}/delete",
     *     summary="Удаляет пользователи канала (soft)",
     *     description="Помечает пользователи канала в БД удаленным",
     *     operationId="deleteChannelUser",
     *     tags={"ChannelUser"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="ChannelUser id",
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
    #[Delete('/api/channeluser/{id}/delete', name: 'channeluser.delete', middleware: 'auth:sanctum')]
    public function delete($id)
    {
        return parent::delete($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/channeluser/{id}/destroy",
     *     summary="Удаляет пользователи канала (hard)",
     *     description="Удаляет пользователи канала из БД ",
     *     operationId="destroyChannelUser",
     *     tags={"ChannelUser"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="ChannelUser id",
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
    #[Delete('/api/channeluser/{id}/destroy', name: 'channeluser.destroy', middleware: 'auth:sanctum')]
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
