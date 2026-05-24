<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Parish extends Model
{
    /** @var list<string> */
    protected $fillable = ['municipality_id', 'code', 'name', 'active'];
    public $timestamps = false;

    /** @var array<string, string> */
    protected $casts = ['active' => 'boolean'];

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }
}
