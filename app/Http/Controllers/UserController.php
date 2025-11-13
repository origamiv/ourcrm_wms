<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreNewsRequest;
use App\Http\Requests\UpdateNewsRequest;
use App\Http\Resources\NewsResource;
use App\Models\News;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use OurCRM\BaseController;
use Spatie\RouteAttributes\Attributes\Resource;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Delete;
use Symfony\Component\HttpFoundation\Request;


final class UserController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *      path="/api/user/{id}",
     *      operationId="indexUser",
     *      tags={"Token"},
     *      summary="Получение токена авторизации",
     *      description="Возвращает токен пользователя",
     *           @OA\Parameter(
     *           name="id",
     *           description="User id",
     *           required=true,
     *           in="path",
     *
     *           @OA\Schema(
     *               type="integer",
     *               format="int64"
     *           ),
     *
     *           @OA\Examples(example="int", value="2", summary="An int value.")
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *              @OA\JsonContent(
     *
     *                  @OA\Property(property="token", type="object"),
     *              ),
     *       ),
     *
     *
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="Forbidden")
     *          )
     *      )
     * )
     */
    #[Get('/api/user/{id}')]
    public function index($id)
    {
        $user=\App\Models\User::query()->find($id);
        $token = $user->createToken('api');
        return \response(['token' => $token->plainTextToken]);
    }
}
