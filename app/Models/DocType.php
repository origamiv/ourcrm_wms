<?php

declare(strict_types=1);

namespace App\Models;

final class DocType extends BaseModel
{
    protected $table = 'clients.doc_types';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer'];

    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Document::class);
    }
}
