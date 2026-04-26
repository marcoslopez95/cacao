<?php

namespace App\Actions\Academic;

use App\Http\Wrappers\Academic\SubjectWrapper;
use App\Models\Career;
use App\Models\Subject;

class CreateSubjectAction
{
    public function handle(SubjectWrapper $wrapper, Career $career): Subject
    {
        $code = $this->generateCode(
            $career->code,
            $wrapper->getPeriodNumber(),
            $wrapper->getPensumId()
        );

        return Subject::create([
            'pensum_id' => $wrapper->getPensumId(),
            'name' => $wrapper->getName(),
            'credits_uc' => $wrapper->getCreditsUc(),
            'period_number' => $wrapper->getPeriodNumber(),
            'description' => $wrapper->getDescription(),
            'code' => $code,
        ]);
    }

    private function generateCode(string $careerCode, int $period, int $pensumId): string
    {
        $count = Subject::where('pensum_id', $pensumId)
            ->where('period_number', $period)
            ->count();

        $sequence = $count + 1;

        for ($i = 0; $i < 100; $i++) {
            $code = "{$careerCode}-{$period}".str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
            $exists = Subject::where('pensum_id', $pensumId)
                ->where('code', $code)
                ->exists();

            if (! $exists) {
                return $code;
            }

            $sequence++;
        }

        throw new \RuntimeException('Could not generate a unique subject code after 100 attempts.');
    }
}
