<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ClientCompany;
use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final class DocumentPartiesService
{
    public function document(User $actor, string $id): Document
    {
        abort_unless(app(AccessService::class)->isAdmin($actor), 403);

        return Document::visibleTo($actor->tenant_id)
            ->whereHas('client', fn ($q) => $q->visibleTo($actor->tenant_id))
            ->findOrFail($id);
    }

    public function executors(Document $document): Builder
    {
        return Company::visibleTo($this->viewer($document))
            ->whereRaw("src->>'is_own' IN ('true', '1')");
    }

    public function customers(Document $document): Builder
    {
        return ClientCompany::visibleTo($this->viewer($document))->where('client_id', $document->client_id);
    }

    public function resolve(Document $document, array $selection): array
    {
        $parties = [];
        foreach (['executor_id' => $this->executors($document), 'customer_id' => $this->customers($document)] as $key => $query) {
            $rows = (isset($selection[$key]) ? $query->whereKey($selection[$key]) : $query)->limit(2)->get();
            if ($rows->count() !== 1) {
                throw ValidationException::withMessages([$key => $key === 'executor_id'
                    ? 'Выберите компанию своей организации с признаком «Наша».'
                    : 'Выберите юридическое лицо этого клиента.']);
            }
            $parties[$key === 'executor_id' ? 'executor' : 'customer'] = $rows->first();
        }

        return $parties;
    }

    private function viewer(Document $document): string
    {
        return (string) (auth()->user()?->tenant_id ?? $document->tenant_id);
    }
}
