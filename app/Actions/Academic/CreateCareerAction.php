<?php

namespace App\Actions\Academic;

use App\Http\Wrappers\Academic\CareerWrapper;
use App\Models\Career;
use Illuminate\Support\Facades\DB;

class CreateCareerAction
{
    /** @var string[] */
    private const STOP_WORDS = [
        'a', 'al', 'con', 'de', 'del', 'e', 'el', 'en',
        'la', 'las', 'lo', 'los', 'o', 'para', 'por',
        'sin', 'su', 'un', 'una', 'y',
    ];

    public function handle(CareerWrapper $wrapper): Career
    {
        return DB::transaction(function () use ($wrapper): Career {
            $career = Career::create([
                'career_category_id' => $wrapper->getCategoryId(),
                'name' => $wrapper->getName(),
                'active' => $wrapper->isActive(),
            ]);

            $career->update(['code' => $this->generateCode($career->name, $career->id)]);

            return $career;
        });
    }

    private function generateCode(string $name, int $id): string
    {
        $words = preg_split('/\s+/', mb_strtolower(trim($name))) ?: [];
        $significant = array_values(array_filter($words, fn ($w) => ! in_array($w, self::STOP_WORDS, true)));

        if (empty($significant)) {
            $significant = $words;
        }

        $initials = implode('', array_map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)), $significant));
        $idPart = '-'.str_pad((string) $id, 2, '0', STR_PAD_LEFT);

        return mb_substr($initials.$idPart, 0, 10);
    }
}
