<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\References\CreateReferenceRequest;
use App\Http\Requests\References\DeleteReferenceRequest;
use App\Http\Requests\References\UpdateReferenceRequest;
use App\Services\ReferenceService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

final class ReferenceController extends BaseApiController
{
    /** Создать запись справочника modules, features, icons или files. Модули и возможности общие, иконки и файлы — своей организации. */
    #[BodyParameter('shortname', 'Краткое название: modules, features', required: false, type: 'string|null')]
    #[BodyParameter('descr', 'Описание: modules', required: false, type: 'string|null')]
    #[BodyParameter('fn', 'Функция: modules', required: false, type: 'string|null')]
    #[BodyParameter('domain', 'Домен: modules', required: false, type: 'string|null')]
    #[BodyParameter('module_id', 'ID общего модуля: features', required: false, type: 'integer|null')]
    #[BodyParameter('is_resource', 'Ресурс (0/1): features', required: false, type: 'integer|null')]
    #[BodyParameter('company_id', 'ID компании своей организации: icons, files', required: false, type: 'integer|null')]
    #[BodyParameter('user_id', 'ID пользователя своей организации: icons, files', required: false, type: 'integer|null')]
    #[BodyParameter('size', 'Размер в байтах: icons, files', required: false, type: 'integer|null')]
    #[BodyParameter('is_s3', 'Хранится в S3 (0/1): files', required: false, type: 'integer|null')]
    public function store(CreateReferenceRequest $request, string $reference, ReferenceService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $reference, $request->validated())], 201);
    }

    /** Изменить запись справочника с проверкой версии. */
    #[Response(409, 'Запись изменена', type: 'array{message: string, current: array<string, mixed>}')]
    #[BodyParameter('shortname', 'Краткое название: modules, features', required: false, type: 'string|null')]
    #[BodyParameter('descr', 'Описание: modules', required: false, type: 'string|null')]
    #[BodyParameter('fn', 'Функция: modules', required: false, type: 'string|null')]
    #[BodyParameter('domain', 'Домен: modules', required: false, type: 'string|null')]
    #[BodyParameter('module_id', 'ID общего модуля: features', required: false, type: 'integer|null')]
    #[BodyParameter('is_resource', 'Ресурс (0/1): features', required: false, type: 'integer|null')]
    #[BodyParameter('company_id', 'ID компании своей организации: icons, files', required: false, type: 'integer|null')]
    #[BodyParameter('user_id', 'ID пользователя своей организации: icons, files', required: false, type: 'integer|null')]
    #[BodyParameter('size', 'Размер в байтах: icons, files', required: false, type: 'integer|null')]
    #[BodyParameter('is_s3', 'Хранится в S3 (0/1): files', required: false, type: 'integer|null')]
    public function update(UpdateReferenceRequest $request, string $reference, string $id, ReferenceService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $reference, $request->validated(), $id)]);
    }

    /** Мягко удалить запись справочника. Используемые модули и возможности защищены от удаления. */
    public function destroy(DeleteReferenceRequest $request, string $reference, string $id, ReferenceService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $reference, $request->validated(), $id, true)]);
    }
}
