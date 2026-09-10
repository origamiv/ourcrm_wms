<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\Documents\CreateDocumentRequest;
use App\Http\Requests\Documents\DeleteDocumentRequest;
use App\Http\Requests\Documents\UpdateDocumentRequest;
use App\Services\DocumentService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

final class DocumentController extends BaseApiController
{
    /** Создать документ своей организации или общий тип документа. */
    #[BodyParameter('client_id', 'Обязателен для documents: клиент своей организации. Не используется для doc_types.', required: false, type: 'integer')]
    #[BodyParameter('doc_type_id', 'Обязателен для documents: ID общего типа документа. Не используется для doc_types.', required: false, type: 'integer')]
    #[BodyParameter('status', 'documents: 0 Новый, 1 Активен, 2 Отменен, 3 Отправлен; doc_types: 1 Активен, 2 Отключен.', required: true, type: 'integer')]
    #[BodyParameter('src', 'Дополнительные JSON-данные документа (объект или массив).', required: false, type: 'object|array<mixed>|null', infer: false)]
    public function store(CreateDocumentRequest $request, string $document_catalog, DocumentService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $document_catalog, $request->validated())], 201);
    }

    /** Изменить документ своей организации или общий тип документа с проверкой версии. */
    #[Response(409, 'Запись изменена', type: 'array{message: string, current: array<string, mixed>}')]
    #[BodyParameter('client_id', 'Обязателен для documents: клиент своей организации. Не используется для doc_types.', required: false, type: 'integer')]
    #[BodyParameter('doc_type_id', 'Обязателен для documents: ID общего типа документа. Не используется для doc_types.', required: false, type: 'integer')]
    #[BodyParameter('status', 'documents: 0 Новый, 1 Активен, 2 Отменен, 3 Отправлен; doc_types: 1 Активен, 2 Отключен.', required: true, type: 'integer')]
    #[BodyParameter('src', 'Дополнительные JSON-данные документа (объект или массив).', required: false, type: 'object|array<mixed>|null', infer: false)]
    public function update(UpdateDocumentRequest $request, string $document_catalog, string $id, DocumentService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $document_catalog, $request->validated(), $id)]);
    }

    /** Мягко удалить документ своей организации или общий тип документа с проверкой версии. */
    public function destroy(DeleteDocumentRequest $request, string $document_catalog, string $id, DocumentService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $document_catalog, $request->validated(), $id, true)]);
    }
}
