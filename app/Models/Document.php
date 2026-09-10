<?php

declare(strict_types=1);

namespace App\Models;

final class Document extends BaseModel
{
    protected $table = 'clients.documents';

    protected $guarded = ['*'];

    protected $casts = ['amount' => 'decimal:2', 'src' => 'array', 'status' => 'integer', 'doc_date' => 'date:Y-m-d', 'accepted_at' => 'datetime:Y-m-d H:i:s', 'payed_at' => 'datetime:Y-m-d H:i:s', 'canceled_at' => 'datetime:Y-m-d H:i:s'];

    public function executor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class, 'executor_id');
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ClientCompany::class, 'customer_id');
    }

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function docType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DocType::class);
    }
}
