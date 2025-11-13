<?php

namespace App\Http\Controllers;

use OurCRM\BaseApiController;
use App\Models\DummyModel;
use Illuminate\Http\Request;
use App\Http\Requests\DummyRequest;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\ScopeBindings;

class DummyClass extends BaseApiController
{
    public $model = 'App\\Models\\DummyModel';
    public $requestClass = DummyRequest::class;
    public $resource = '{{ modelVariable }}';


    /**
     * @OA\Post(
     *     path="/api/{{ modelVariable }}/list",
     *     summary="Возвращает список {descrManyR}",
     *     description="Возвращает список {descrManyR}",
     *     tags={"DummyModel"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
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
    #[Post('/api/{{ modelVariable }}/list', name: '{{ modelVariable }}.index', middleware: 'auth:sanctum')]
    public function index(Request $request)
    {
        return parent::index($request);
    }

    /**
     * @OA\Post(
     *     path="/api/{{ modelVariable }}/create",
     *     summary="Создает {descrOne}",
     *     description="Создает {descrOne}",
     *     operationId="store{{ model }}",
     *     tags={"DummyModel"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
    //{$swagger}
     *                 example={"name": "Jessica Smith"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             @OA\Examples(example="result", value={"success": true}, summary="An result object.")
     *         )
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Нет доступа"
     *     )
     * )
     */
    #[Post('/api/{{ modelVariable }}/create', name: '{{ modelVariable }}.store', middleware: 'auth:sanctum')]
    public function store(Request $request, bool $isAuthNeeded = true)
    {
        return parent::store($request);
    }

    /**
     * @OA\Put(
     *     path="/api/{{ modelVariable }}/{id}",
     *     summary="Измененяет информацию о {descrAbout}",
     *     description="Измененяет информацию о {descrAbout}",
     *     operationId="update{{ model }}",
     *     tags={"DummyModel"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *          name="id",
     *          description="{{ model }} id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
    //{$swagger}
     *                 example={"name": "Jessica Smith"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             @OA\Examples(example="result", value={"success": true}, summary="An result object."),
     *         )
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Нет доступа"
     *     )
     * )
     */
    #[Put('/api/{{ modelVariable }}/{id}', name: '{{ modelVariable }}.update', middleware: 'auth:sanctum')]
    public function update($id, Request $request, bool $isAuthNeeded = true)
    {
        return parent::update($id, $request);
    }

    /**
     * @OA\Get(
     *     path="/api/{{ modelVariable }}/{id}",
     *     summary="Показывает информацию о {descrAbout}",
     *     description="Показывает информацию о {descrAbout}",
     *     operationId="show{{ model }}",
     *     tags={"DummyModel"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *          name="id",
     *          description="{{ model }} id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             @OA\Examples(example="result", value={"success": true}, summary="An result object."),
     *         )
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Нет доступа"
     *     )
     * )
     */
    #[Get('/api/{{ modelVariable }}/{id}', name: '{{ modelVariable }}.show', middleware: 'auth:sanctum')]
    public function show($id, bool $isAuthNeeded = true)
    {
        return parent::show($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/{{ modelVariable }}/{id}/delete",
     *     summary="Удаляет {descrOne} (soft)",
     *     description="Помечает {descrOne} в БД удаленным",
     *     operationId="delete{{ model }}",
     *     tags={"DummyModel"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *          name="id",
     *          description="{{ model }} id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             @OA\Examples(example="result", value={"success": true}, summary="An result object."),
     *         )
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Нет доступа"
     *     )
     * )
     */
    #[Delete('/api/{{ modelVariable }}/{id}/delete', name: '{{ modelVariable }}.delete', middleware: 'auth:sanctum')]
    public function delete($id)
    {
        return parent::delete($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/{{ modelVariable }}/{id}/destroy",
     *     summary="Удаляет {descrOne} (hard)",
     *     description="Удаляет {descrOne} из БД ",
     *     operationId="destroy{{ model }}",
     *     tags={"DummyModel"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *          name="id",
     *          description="{{ model }} id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             @OA\Examples(example="result", value={"success": true}, summary="An result object."),
     *         )
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Нет доступа"
     *     )
     * )
     */
    #[Delete('/api/{{ modelVariable }}/{id}/destroy', name: '{{ modelVariable }}.destroy', middleware: 'auth:sanctum')]
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
