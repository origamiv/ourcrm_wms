<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TagRequest;
use App\Models\Tag;
use Illuminate\Http\Request;
use OurCRM\BaseApiController;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;

final class TagController extends BaseApiController
{
    public $model = 'App\\Models\\Tag';

    public $requestClass = TagRequest::class;

    public $resource = 'tag';

    /**
     * @OA\Post(
     *     path="/api/tag/list",
     *     summary="Возвращает список тэгов",
     *     description="Возвращает список тэгов",
     *     tags={"Tag"},
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
    #[Post('/api/tag/list', name: 'tag.index', middleware: 'auth:sanctum')]
    public function index(Request $request)
    {
        return parent::index($request);
    }

    /**
     * @OA\Post(
     *     path="/api/tag/create",
     *     summary="Создает тэг",
     *     description="Создает тэг",
     *     operationId="storeTag",
     *     tags={"Tag"},
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
     *                     property="categoty",
     *                     type="string",
     *                     description="Категория",
     *                     example=""
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
    #[Post('/api/tag/create', name: 'tag.store', middleware: 'auth:sanctum')]
    public function store(Request $request, bool $isAuthNeeded = true)
    {
        return parent::store($request);
    }

    /**
     * @OA\Put(
     *     path="/api/tag/{id}",
     *     summary="Измененяет информацию о тэге",
     *     description="Измененяет информацию о тэге",
     *     operationId="updateTag",
     *     tags={"Tag"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Tag id",
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
     *                     property="categoty",
     *                     type="string",
     *                     description="Категория",
     *                     example=""
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
    #[Put('/api/tag/{id}', name: 'tag.update', middleware: 'auth:sanctum')]
    public function update($id, Request $request, bool $isAuthNeeded = true)
    {
        return parent::update($id, $request);
    }

    /**
     * @OA\Get(
     *     path="/api/tag/{id}",
     *     summary="Показывает информацию о тэге",
     *     description="Показывает информацию о тэге",
     *     operationId="showTag",
     *     tags={"Tag"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Tag id",
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
    #[Get('/api/tag/{id}', name: 'tag.show', middleware: 'auth:sanctum')]
    public function show($id, bool $isAuthNeeded = true)
    {
        return parent::show($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/tag/{id}/delete",
     *     summary="Удаляет тэг (soft)",
     *     description="Помечает тэг в БД удаленным",
     *     operationId="deleteTag",
     *     tags={"Tag"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Tag id",
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
    #[Delete('/api/tag/{id}/delete', name: 'tag.delete', middleware: 'auth:sanctum')]
    public function delete($id)
    {
        return parent::delete($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/tag/{id}/destroy",
     *     summary="Удаляет тэг (hard)",
     *     description="Удаляет тэг из БД ",
     *     operationId="destroyTag",
     *     tags={"Tag"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Tag id",
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
    #[Delete('/api/tag/{id}/destroy', name: 'tag.destroy', middleware: 'auth:sanctum')]
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
