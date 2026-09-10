<?php

declare(strict_types=1);

namespace App\Models;

final class CompanyContact extends BaseModel
{
    protected $table = 'main.company_contacts';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer'];

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->where('tenant_id', $this->tenant_id);
    }
}
