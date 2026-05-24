<?php

namespace App\Observers;

use App\Models\Catalog;
use Illuminate\Support\Facades\Cache;

class CatalogObserver
{
    public function saved(Catalog $catalog): void
    {
        Cache::forget('catalog.'.$catalog->getTable());
    }

    public function deleted(Catalog $catalog): void
    {
        Cache::forget('catalog.'.$catalog->getTable());
    }
}
