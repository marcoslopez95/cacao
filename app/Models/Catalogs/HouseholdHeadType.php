<?php

namespace App\Models\Catalogs;

use Illuminate\Database\Eloquent\Model;

// TODO: extend Catalog once Task 7 is done
class HouseholdHeadType extends Model
{
    /** @var list<string> */
    protected $fillable = ['code', 'name', 'description', 'active', 'sort_order'];

    public $timestamps = false;

    protected $table = 'household_head_types';

    /** @var array<string, string> */
    protected $casts = ['active' => 'boolean'];
}
