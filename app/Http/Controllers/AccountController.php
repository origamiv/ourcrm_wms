<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Jobs\CommandsJob;
use App\Models\Account;
use Illuminate\Http\Request;
use OurCRM\BaseApiController;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;

final class AccountController extends BaseApiController
{
    public $model = 'App\\Models\\Account';

    public $requestClass = AccountRequest::class;

    public $resource = 'account';

    /**
     * @OA\Post(
     *     path="/api/account/list",
     *     summary="Возвращает список аккаунтов",
     *     description="Возвращает список аккаунтов",
     *     tags={"Account"},
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
    #[Post('/api/account/list', name: 'account.index', middleware: 'auth:sanctum')]
    public function index(Request $request)
    {
        return parent::index($request);
    }

    /**
     * @OA\Post(
     *     path="/api/account/create",
     *     summary="Создает аккаунт",
     *     description="Создает аккаунт",
     *     operationId="storeAccount",
     *     tags={"Account"},
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
     *                     property="user_id",
     *                     type="integer",
     *                     description="Пользователь",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="messenger_id",
     *                     type="integer",
     *                     description="Мессенджер",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="login",
     *                     type="string",
     *                     description="Логин",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     description="Код",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     description="Пароль",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="is_2fa",
     *                     type="integer",
     *                     description="Включена 2ФА",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="pass2fa",
     *                     type="string",
     *                     description="Пароль 2FA",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="telegram_id",
     *                     type="string",
     *                     description="ID в Телеграмм",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="tas_session_status",
     *                     type="text",
     *                     description="Статус сессии",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="tas_session_expires",
     *                     type="string",
     *                     description="Дата истечения сессии",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="first_name",
     *                     type="string",
     *                     description="Имя",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="last_name",
     *                     type="string",
     *                     description="Фамилия",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="username",
     *                     type="string",
     *                     description="Псевдоним",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="src",
     *                     type="jsonb",
     *                     description="Инфо об аккаунте",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="options",
     *                     type="jsonb",
     *                     description="Опции",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="slot",
     *                     type="integer",
     *                     description="Слот ",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="last_used_at",
     *                     type="date",
     *                     description="Время последнего использования ",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="new_messages_last_check",
     *                     type="date",
     *                     description="Время последней проверки сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="phone_code_hash",
     *                     type="string",
     *                     description="phone_code_hash",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="cnt",
     *                     type="integer",
     *                     description="Количество сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="integer",
     *                     description="Статус",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="status_messenger",
     *                     type="integer",
     *                     description="Статус в мессенджере",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="cnt_people",
     *                     type="integer",
     *                     description="cnt_people",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="tas_port",
     *                     type="string",
     *                     description="Port для сессии",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="port",
     *                     type="integer",
     *                     description="port",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="fn_avatar",
     *                     type="string",
     *                     description="fn_avatar",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="icon",
     *                     type="string",
     *                     description="icon",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="tagged_at",
     *                     type="date",
     *                     description="tagged_at",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="mode",
     *                     type="string",
     *                     description="mode",
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
    #[Post('/api/account/create', name: 'account.store', middleware: 'auth:sanctum')]
    public function store(Request $request, bool $isAuthNeeded = true)
    {
        return parent::store($request);
    }

    /**
     * @OA\Put(
     *     path="/api/account/{id}",
     *     summary="Измененяет информацию о аккаунте",
     *     description="Измененяет информацию о аккаунте",
     *     operationId="updateAccount",
     *     tags={"Account"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Account id",
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
     *                     property="user_id",
     *                     type="integer",
     *                     description="Пользователь",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="messenger_id",
     *                     type="integer",
     *                     description="Мессенджер",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="login",
     *                     type="string",
     *                     description="Логин",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     description="Код",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     description="Пароль",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="is_2fa",
     *                     type="integer",
     *                     description="Включена 2ФА",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="pass2fa",
     *                     type="string",
     *                     description="Пароль 2FA",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="telegram_id",
     *                     type="string",
     *                     description="ID в Телеграмм",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="tas_session_status",
     *                     type="text",
     *                     description="Статус сессии",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="tas_session_expires",
     *                     type="string",
     *                     description="Дата истечения сессии",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="first_name",
     *                     type="string",
     *                     description="Имя",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="last_name",
     *                     type="string",
     *                     description="Фамилия",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="username",
     *                     type="string",
     *                     description="Псевдоним",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="src",
     *                     type="jsonb",
     *                     description="Инфо об аккаунте",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="options",
     *                     type="jsonb",
     *                     description="Опции",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="slot",
     *                     type="integer",
     *                     description="Слот ",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="last_used_at",
     *                     type="date",
     *                     description="Время последнего использования ",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="new_messages_last_check",
     *                     type="date",
     *                     description="Время последней проверки сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="phone_code_hash",
     *                     type="string",
     *                     description="phone_code_hash",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="cnt",
     *                     type="integer",
     *                     description="Количество сообщений",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="integer",
     *                     description="Статус",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="status_messenger",
     *                     type="integer",
     *                     description="Статус в мессенджере",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="cnt_people",
     *                     type="integer",
     *                     description="cnt_people",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="tas_port",
     *                     type="string",
     *                     description="Port для сессии",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="port",
     *                     type="integer",
     *                     description="port",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="fn_avatar",
     *                     type="string",
     *                     description="fn_avatar",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="icon",
     *                     type="string",
     *                     description="icon",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="tagged_at",
     *                     type="date",
     *                     description="tagged_at",
     *                     example=""
     *                 ),
     *                 @OA\Property(
     *                     property="mode",
     *                     type="string",
     *                     description="mode",
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
    #[Put('/api/account/{id}', name: 'account.update', middleware: 'auth:sanctum')]
    public function update($id, Request $request, bool $isAuthNeeded = true)
    {
        return parent::update($id, $request);
    }

    /**
     * @OA\Get(
     *     path="/api/account/{id}",
     *     summary="Показывает информацию о аккаунте",
     *     description="Показывает информацию о аккаунте",
     *     operationId="showAccount",
     *     tags={"Account"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Account id",
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
    #[Get('/api/account/{id}', name: 'account.show', middleware: 'auth:sanctum')]
    public function show($id, bool $isAuthNeeded = true)
    {
        return parent::show($id);
    }

    /**
     * @OA\Post(
     *     path="/api/account/{id}/send",
     *     summary="Отправляет сообщение от имени аккаунта",
     *     description="Запускает задачу отправки сообщения",
     *     operationId="sendMessageFromAccount",
     *     tags={"Account"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID аккаунта",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"peer_id", "message"},
     *             @OA\Property(property="peer_id", type="string", example="@Veniamin1980"),
     *             @OA\Property(property="message", type="string", example="Привет, это тестовое сообщение")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Сообщение отправлено",
     *         @OA\JsonContent(
     *             @OA\Examples(example="success", value={"success": true}, summary="Успешная отправка")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Нет доступа"
     *     )
     * )
     */
    #[Post('/api/account/{id}/send', name: 'account.send', middleware: 'auth:sanctum')]
    public function send(int $id, Request $request)
    {
        $validated = $request->validate([
            'peer_id' => 'required',
            'message' => 'required|string',
        ]);

        CommandsJob::dispatch([
            'command' => 'send',
            'account_id' => $id,
            'peer_id' => $validated['peer_id'],
            'message' => $validated['message'],
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * @OA\Post(
     *     path="/api/account/{id}/createGroup",
     *     summary="Создает группу от имени аккаунта",
     *     description="Запускает задачу на создание группы с пользователями",
     *     operationId="createGroupFromAccount",
     *     tags={"Account"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID аккаунта",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "users"},
     *             @OA\Property(property="title", type="string", example="Тестовая группа"),
     *             @OA\Property(
     *                 property="users",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"@Veniamin1980", "+79161112233", 5772012875}
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Группа создается",
     *         @OA\JsonContent(
     *             @OA\Examples(example="success", value={"success": true}, summary="Успешный ответ")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Нет доступа"
     *     )
     * )
     */
    #[Post('/api/account/{id}/createGroup', name: 'account.createGroup', middleware: 'auth:sanctum')]
    public function createGroup(int $id, Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'users' => 'required|array|min:1',
            //'users.*' => 'string', // username, phone или ID в string
        ]);

        CommandsJob::dispatch([
            'command' => 'create_group',
            'account_id' => $id,
            'title' => $validated['title'],
            'users' => $validated['users'],
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * @OA\Post(
     *     path="/api/account/{id}/createСhannel",
     *     summary="Создает канал от имени аккаунта",
     *     description="Запускает задачу на создание Telegram-канала",
     *     operationId="createChannelFromAccount",
     *     tags={"Account"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID аккаунта",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "about"},
     *             @OA\Property(property="title", type="string", example="Тестовый канал"),
     *             @OA\Property(property="about", type="string", example="Описание канала")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Канал создается",
     *         @OA\JsonContent(
     *             @OA\Examples(example="success", value={"success": true}, summary="Успешный ответ")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Нет доступа"
     *     )
     * )
     */
    #[Post('/api/account/{id}/createСhannel', name: 'account.createChannel', middleware: 'auth:sanctum')]
    public function createChannel(int $id, Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'about' => 'required|string|max:255',
        ]);

        CommandsJob::dispatch([
            'command' => 'create_channel',
            'account_id' => $id,
            'title' => $validated['title'],
            'about' => $validated['about'],
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * @OA\Post(
     *     path="/api/account/{id}/addUsersToGroup",
     *     summary="Добавляет пользователей в Telegram-группу",
     *     description="Отправляет задание на добавление пользователей в группу от имени аккаунта",
     *     operationId="addUsersToGroup",
     *     tags={"Account"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID аккаунта",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         @OA\Examples(example="int", value="47", summary="An int value.")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"group_id", "users"},
     *             @OA\Property(property="group_id", type="integer", example=4866982502),
     *             @OA\Property(
     *                 property="users",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"@vsmorodinsky", "+79651591199", "5772012875"}
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Пользователи добавлены в группу",
     *         @OA\JsonContent(
     *             @OA\Examples(example="success", value={"success": true}, summary="Успешный ответ")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Нет доступа"
     *     )
     * )
     */
    #[Post('/api/account/{id}/addUsersToGroup', name: 'account.addUsersToGroup', middleware: 'auth:sanctum')]
    public function addUsersToGroup(int $id, Request $request)
    {
        $validated = $request->validate([
            'group_id' => 'required',
            'users' => 'required|array|min:1',
            //'users.*' => 'string',
        ]);

        CommandsJob::dispatch([
            'command' => 'add_users_to_group',
            'account_id' => $id,
            'group_id' => $validated['group_id'],
            'users' => array_values($validated['users']),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * @OA\Post(
     *     path="/api/account/{id}/addUsersToChannel",
     *     summary="Добавляет пользователей в канал",
     *     description="Отправляет задание на добавление пользователей в канал от имени аккаунта",
     *     operationId="addUsersToChannel",
     *     tags={"Account"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID аккаунта",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"channel_id", "users"},
     *             @OA\Property(property="channel_id", type="string", example="my_channel"),
     *             @OA\Property(
     *                 property="users",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"+79991112233", "1234567890", "username"}
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Пользователи добавлены в канал",
     *         @OA\JsonContent(
     *             @OA\Examples(example="success", value={"success": true}, summary="Успешный ответ")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Нет доступа"
     *     )
     * )
     */
    #[Post('/api/account/{id}/addUsersToChannel', name: 'account.addUsersToChannel', middleware: 'auth:sanctum')]
    public function addUsersToChannel(int $id, Request $request)
    {
        $validated = $request->validate([
            'channel_id' => 'required', // может быть ID или username
            'users' => 'required|array|min:1',
        ]);

        CommandsJob::dispatch([
            'command' => 'add_users_to_channel',
            'account_id' => $id,
            'channel_id' => $validated['channel_id'],
            'users' => array_values($validated['users']),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * @OA\Post(
     *     path="/api/account/{id}/fetchChannelInfo",
     *     summary="Запрашивает информацию о канале",
     *     description="Отправляет задание на получение информации о канале от имени аккаунта",
     *     operationId="fetchChannelInfo",
     *     tags={"Account"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID аккаунта",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"channel_id"},
     *             @OA\Property(property="channel_id", type="string", example="my_channel")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Задание отправлено",
     *         @OA\JsonContent(
     *             @OA\Examples(example="success", value={"success": true}, summary="Успешный ответ")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Нет доступа"
     *     )
     * )
     */
    #[Post('/api/account/{id}/fetchChannelInfo', name: 'account.fetchChannelInfo', middleware: 'auth:sanctum')]
    public function fetchChannelInfo(int $id, Request $request)
    {
        $validated = $request->validate([
            'channel_id' => 'required',
        ]);

        CommandsJob::dispatch([
            'command' => 'fetch_channel_info',
            'account_id' => $id,
            'channel_id' => $validated['channel_id'],
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * @OA\Delete(
     *     path="/api/account/{id}/delete",
     *     summary="Удаляет аккаунт (soft)",
     *     description="Помечает аккаунт в БД удаленным",
     *     operationId="deleteAccount",
     *     tags={"Account"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Account id",
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
    #[Delete('/api/account/{id}/delete', name: 'account.delete', middleware: 'auth:sanctum')]
    public function delete($id)
    {
        return parent::delete($id);
    }

    /**
     * @OA\Delete(
     *     path="/api/account/{id}/destroy",
     *     summary="Удаляет аккаунт (hard)",
     *     description="Удаляет аккаунт из БД ",
     *     operationId="destroyAccount",
     *     tags={"Account"},
     *     security={
     *     {"bearerAuth": {}}
     *     },
     *
     *     @OA\Parameter(
     *          name="id",
     *          description="Account id",
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
    #[Delete('/api/account/{id}/destroy', name: 'account.destroy', middleware: 'auth:sanctum')]
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
