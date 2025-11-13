<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ServiceAccountRequest;
use App\Models\ServiceAccount;
use Illuminate\Http\Request;
use OurCRM\BaseApiController;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;

final class ServiceAccountController extends BaseApiController
{
    public $model = 'App\\Models\\ServiceAccount';

    public $requestClass = ServiceAccountRequest::class;

    public $resource = 'serviceaccount';

    /**
     * @OA\Post(
     *     path="/api/serviceaccount/list",
     *     summary="Возвращает список много кого чего?",
     *     description="Возвращает список много кого чего?",
     *     tags={"ServiceAccount"},
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
    #[Post('/api/serviceaccount/list', name: 'serviceaccount.index', middleware: 'auth:sanctum')]
    public function index(Request $request)
    {
        return parent::index($request);
    }

    /**
     * @OA\Post(
     *     path="/api/serviceaccount/create",
     *     summary="Создает сервисный аккаунт",
     *     description="Создает сервисный аккаунт",
     *     operationId="storeServiceAccount",
     *     tags={"ServiceAccount"},
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
     *                     description="Короткое название",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="service_id",
     *                     type="integer",
     *                     description="Сервис",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="login",
     *                     type="string",
     *                     description="Логин",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     description="Пароль",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="token",
     *                     type="string",
     *                     description="Токен",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="options",
     *                     type="json",
     *                     description="Параметры",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="integer",
     *                     description="Статус",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="balance",
     *                     type="double",
     *                     description="Баланс",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="cnt",
     *                     type="integer",
     *                     description="Количество",
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
    #[Post('/api/serviceaccount/create', name: 'serviceaccount.store', middleware: 'auth:sanctum')]
    public function store(Request $request, bool $isAuthNeeded = true)
    {
        return parent::store($request);
    }

    /**
     * @OA\Put(
     *     path="/api/serviceaccount/{id}",
     *     summary="Измененяет информацию о о ком чем?",
     *     description="Измененяет информацию о о ком чем?",
     *     operationId="updateServiceAccount",
     *     tags={"ServiceAccount"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="ServiceAccount id",
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
     *                     description="Короткое название",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="service_id",
     *                     type="integer",
     *                     description="Сервис",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="login",
     *                     type="string",
     *                     description="Логин",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     description="Пароль",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="token",
     *                     type="string",
     *                     description="Токен",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="options",
     *                     type="json",
     *                     description="Параметры",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="integer",
     *                     description="Статус",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="balance",
     *                     type="double",
     *                     description="Баланс",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="cnt",
     *                     type="integer",
     *                     description="Количество",
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
    #[Put('/api/serviceaccount/{id}', name: 'serviceaccount.update', middleware: 'auth:sanctum')]
    public function update($id, Request $request, bool $isAuthNeeded = true)
    {
        return parent::update($id, $request);
    }

    /**
     * @OA\Get(
     *     path="/api/serviceaccount/{id}",
     *     summary="Показывает информацию о о ком чем?",
     *     description="Показывает информацию о о ком чем?",
     *     operationId="showServiceAccount",
     *     tags={"ServiceAccount"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="ServiceAccount id",
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
    #[Get('/api/serviceaccount/{id}', name: 'serviceaccount.show', middleware: 'auth:sanctum')]
    public function show($id, bool $isAuthNeeded = true)
    {
        return parent::show($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/serviceaccount/{id}/delete",
     *     summary="Удаляет сервисный аккаунт (soft)",
     *     description="Помечает сервисный аккаунт в БД удаленным",
     *     operationId="deleteServiceAccount",
     *     tags={"ServiceAccount"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="ServiceAccount id",
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
    #[Delete('/api/serviceaccount/{id}/delete', name: 'serviceaccount.delete', middleware: 'auth:sanctum')]
    public function delete($id)
    {
        return parent::delete($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/serviceaccount/{id}/destroy",
     *     summary="Удаляет сервисный аккаунт (hard)",
     *     description="Удаляет сервисный аккаунт из БД ",
     *     operationId="destroyServiceAccount",
     *     tags={"ServiceAccount"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="ServiceAccount id",
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
    #[Delete('/api/serviceaccount/{id}/destroy', name: 'serviceaccount.destroy', middleware: 'auth:sanctum')]
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
