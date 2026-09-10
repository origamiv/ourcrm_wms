<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Kiz extends BaseModel
{
    protected $table = 'goods.kizes';

    protected $guarded = ['*'];

    protected $casts = ['entranced_at' => 'datetime', 'leaving_at' => 'datetime', 'printed_at' => 'datetime'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function good(): BelongsTo
    {
        return $this->belongsTo(Good::class, 'good_id');
    }

    public function kindKiz(): BelongsTo
    {
        return $this->belongsTo(KindKiz::class, 'kind_kiz_id');
    }
}
