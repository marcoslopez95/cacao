<?php

namespace App\Actions\Admin;

use App\Http\Wrappers\Admin\GradeConfigWrapper;
use App\Models\GradeConfig;
use App\Models\GradeEntry;
use Illuminate\Support\Facades\DB;

class UpdateGradeConfigAction
{
    public function handle(GradeConfig $config, GradeConfigWrapper $wrapper): GradeConfig
    {
        return DB::transaction(function () use ($config, $wrapper): GradeConfig {
            $hasEntries = GradeEntry::whereHas(
                'slot',
                fn ($q) => $q->where('grade_config_id', $config->id)
            )->exists();

            if ($hasEntries && $wrapper->getPeriodId() !== null) {
                return $this->createPeriodOverride($config, $wrapper);
            }

            return $this->updateInPlace($config, $wrapper);
        });
    }

    private function updateInPlace(GradeConfig $config, GradeConfigWrapper $wrapper): GradeConfig
    {
        $config->update([
            'scale_type' => $wrapper->getScaleType(),
            'scale_min' => $wrapper->getScaleMin(),
            'scale_max' => $wrapper->getScaleMax(),
            'passing_value' => $wrapper->getPassingValue(),
        ]);

        $config->slots()->delete();
        foreach ($wrapper->getSlots() as $slot) {
            $config->slots()->create($slot);
        }

        $config->letterValues()->delete();
        if ($wrapper->isLetterScale()) {
            foreach ($wrapper->getLetterValues() as $letterValue) {
                $config->letterValues()->create($letterValue);
            }
        }

        return $config->fresh(['slots', 'letterValues']);
    }

    private function createPeriodOverride(GradeConfig $config, GradeConfigWrapper $wrapper): GradeConfig
    {
        $override = GradeConfig::create([
            'level' => $config->level,
            'period_id' => $wrapper->getPeriodId(),
            'scale_type' => $wrapper->getScaleType(),
            'scale_min' => $wrapper->getScaleMin(),
            'scale_max' => $wrapper->getScaleMax(),
            'passing_value' => $wrapper->getPassingValue(),
        ]);

        foreach ($wrapper->getSlots() as $slot) {
            $override->slots()->create($slot);
        }

        if ($wrapper->isLetterScale()) {
            foreach ($wrapper->getLetterValues() as $letterValue) {
                $override->letterValues()->create($letterValue);
            }
        }

        return $override;
    }
}
