<?php

declare(strict_types=1);

namespace App\Models;

final class Document extends BaseModel
{
    protected $table = 'clients.documents';

    protected $guarded = ['*'];

    protected $casts = ['src' => 'array', 'status' => 'integer', 'doc_date' => 'date:Y-m-d', 'accepted_at' => 'datetime:Y-m-d H:i:s', 'payed_at' => 'datetime:Y-m-d H:i:s', 'canceled_at' => 'datetime:Y-m-d H:i:s'];

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function docType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DocType::class);
    }
}
