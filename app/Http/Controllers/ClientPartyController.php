<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\ClientParties\CreateClientPartyRequest;
use App\Http\Requests\ClientParties\DeleteClientPartyRequest;
use App\Http\Requests\ClientParties\UpdateClientPartyRequest;
use App\Services\CompanyDirectoryService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

final class ClientPartyController extends BaseApiController
{
    /** Создать юрлицо или физлицо своей организации. */
    #[BodyParameter('shortname', 'Краткое название: обязательно для companies, необязательно для individuals.', required: false, type: 'string|null')]
    #[BodyParameter('fullname', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('inn', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('kpp', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('ogrn', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('okpo', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('site', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('director_fio', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('director_position', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('bank', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('bik', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('korr_schet', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('rasch_schet', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('src', 'Дополнительные поля юрлица: telegram, opf, accountant_position, accountant_fio, legal_address, is_own, is_client, is_partner.', required: false, type: 'array<string, mixed>')]
    public function store(CreateClientPartyRequest $request, string $party, CompanyDirectoryService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), 'client_'.$party, $request->validated())], 201);
    }

    /** Изменить юрлицо или физлицо своей организации с проверкой версии. */
    #[Response(409, 'Запись изменена', type: 'array{message: string, current: array<string, mixed>}')]
    #[BodyParameter('shortname', 'Краткое название: обязательно для companies, необязательно для individuals.', required: false, type: 'string|null')]
    #[BodyParameter('fullname', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('inn', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('kpp', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('ogrn', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('okpo', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('site', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('director_fio', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('director_position', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('bank', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('bik', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('korr_schet', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('rasch_schet', 'Поле юрлица (party=companies).', required: false, type: 'string|null')]
    #[BodyParameter('src', 'Дополнительные поля юрлица: telegram, opf, accountant_position, accountant_fio, legal_address, is_own, is_client, is_partner.', required: false, type: 'array<string, mixed>')]
    public function update(UpdateClientPartyRequest $request, string $party, string $id, CompanyDirectoryService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), 'client_'.$party, $request->validated(), $id)]);
    }

    /** Мягко удалить юрлицо или физлицо своей организации с проверкой версии. */
    public function destroy(DeleteClientPartyRequest $request, string $party, string $id, CompanyDirectoryService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), 'client_'.$party, $request->validated(), $id, true)]);
    }
}
