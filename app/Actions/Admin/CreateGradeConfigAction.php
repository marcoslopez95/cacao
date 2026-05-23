<?php

namespace App\Actions\Admin;

use App\Http\Wrappers\Admin\GradeConfigWrapper;
use App\Models\GradeConfig;
use Illuminate\Support\Facades\DB;

class CreateGradeConfigAction
{
    public function handle(GradeConfigWrapper $wrapper): GradeConfig
    {
        return DB::transaction(function () use ($wrapper): GradeConfig {
            $config = GradeConfig::create($wrapper->getConfigData());

            foreach ($wrapper->getSlots() as $slot) {
                $config->slots()->create($slot);
            }

            if ($wrapper->isLetterScale()) {
                foreach ($wrapper->getLetterValues() as $letterValue) {
                    $config->letterValues()->create($letterValue);
                }
            }

            return $config;
        });
    }
}
