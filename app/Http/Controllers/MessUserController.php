<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MessUserRequest;
use App\Models\MessUser;
use Illuminate\Http\Request;
use OurCRM\BaseApiController;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;

final class MessUserController extends BaseApiController
{
    public $model = 'App\\Models\\MessUser';

    public $requestClass = MessUserRequest::class;

    public $resource = 'messuser';

    /**
     * @OA\Post(
     *     path="/api/messuser/list",
     *     summary="Возвращает список пользователей мессенжера",
     *     description="Возвращает список пользователей мессенжера",
     *     tags={"MessUser"},
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
    #[Post('/api/messuser/list', name: 'messuser.index', middleware: 'auth:sanctum')]
    public function index(Request $request)
    {
        return parent::index($request);
    }

    /**
     * @OA\Post(
     *     path="/api/messuser/create",
     *     summary="Создает пользователь мессенжера",
     *     description="Создает пользователь мессенжера",
     *     operationId="storeMessUser",
     *     tags={"MessUser"},
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
     *                     property="bot_id",
     *                     type="integer",
     *                     description="bot_id",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="account_id",
     *                     type="integer",
     *                     description="account_id",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="name",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="messenger_id",
     *                     type="integer",
     *                     description="messenger_id",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="peer_id",
     *                     type="string",
     *                     description="peer_id",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="first_name",
     *                     type="string",
     *                     description="first_name",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="last_name",
     *                     type="string",
     *                     description="last_name",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="photo_id",
     *                     type="string",
     *                     description="photo_id",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="username",
     *                     type="string",
     *                     description="username",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="src",
     *                     type="json",
     *                     description="src",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="string",
     *                     description="Статус",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="activity",
     *                     type="integer",
     *                     description="Активность",
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
    #[Post('/api/messuser/create', name: 'messuser.store', middleware: 'auth:sanctum')]
    public function store(Request $request, bool $isAuthNeeded = true)
    {
        return parent::store($request);
    }

    /**
     * @OA\Put(
     *     path="/api/messuser/{id}",
     *     summary="Измененяет информацию о пользователе мессенжера",
     *     description="Измененяет информацию о пользователе мессенжера",
     *     operationId="updateMessUser",
     *     tags={"MessUser"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="MessUser id",
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
     *                     property="bot_id",
     *                     type="integer",
     *                     description="bot_id",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="account_id",
     *                     type="integer",
     *                     description="account_id",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="name",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="messenger_id",
     *                     type="integer",
     *                     description="messenger_id",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="peer_id",
     *                     type="string",
     *                     description="peer_id",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="first_name",
     *                     type="string",
     *                     description="first_name",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="last_name",
     *                     type="string",
     *                     description="last_name",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="photo_id",
     *                     type="string",
     *                     description="photo_id",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="username",
     *                     type="string",
     *                     description="username",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="src",
     *                     type="json",
     *                     description="src",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="string",
     *                     description="Статус",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="activity",
     *                     type="integer",
     *                     description="Активность",
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
    #[Put('/api/messuser/{id}', name: 'messuser.update', middleware: 'auth:sanctum')]
    public function update($id, Request $request, bool $isAuthNeeded = true)
    {
        return parent::update($id, $request);
    }

    /**
     * @OA\Get(
     *     path="/api/messuser/{id}",
     *     summary="Показывает информацию о пользователе мессенжера",
     *     description="Показывает информацию о пользователе мессенжера",
     *     operationId="showMessUser",
     *     tags={"MessUser"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="MessUser id",
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
    #[Get('/api/messuser/{id}', name: 'messuser.show', middleware: 'auth:sanctum')]
    public function show($id, bool $isAuthNeeded = true)
    {
        return parent::show($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/messuser/{id}/delete",
     *     summary="Удаляет пользователь мессенжера (soft)",
     *     description="Помечает пользователь мессенжера в БД удаленным",
     *     operationId="deleteMessUser",
     *     tags={"MessUser"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="MessUser id",
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
    #[Delete('/api/messuser/{id}/delete', name: 'messuser.delete', middleware: 'auth:sanctum')]
    public function delete($id)
    {
        return parent::delete($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/messuser/{id}/destroy",
     *     summary="Удаляет пользователь мессенжера (hard)",
     *     description="Удаляет пользователь мессенжера из БД ",
     *     operationId="destroyMessUser",
     *     tags={"MessUser"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="MessUser id",
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
    #[Delete('/api/messuser/{id}/destroy', name: 'messuser.destroy', middleware: 'auth:sanctum')]
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
