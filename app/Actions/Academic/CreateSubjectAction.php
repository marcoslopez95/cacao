<?php

namespace App\Actions\Academic;

use App\Http\Wrappers\Academic\SubjectWrapper;
use App\Models\Career;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class CreateSubjectAction
{
    public function handle(SubjectWrapper $wrapper, Career $career): Subject
    {
        return DB::transaction(function () use ($wrapper, $career): Subject {
            $subject = Subject::create([
                'pensum_id' => $wrapper->getPensumId(),
                'name' => $wrapper->getName(),
                'code' => '',
                'credits_uc' => $wrapper->getCreditsUc(),
                'period_number' => $wrapper->getPeriodNumber(),
                'description' => $wrapper->getDescription(),
            ]);

            $code = $this->generateCode(
                $career->code,
                $wrapper->getPeriodNumber(),
                $wrapper->getPensumId(),
                $subject->id,
            );

            $subject->update(['code' => $code]);

            return $subject;
        });
    }

    private function generateCode(string $careerCode, int $period, int $pensumId, int $subjectId): string
    {
        $count = Subject::where('pensum_id', $pensumId)
            ->where('period_number', $period)
            ->where('id', '!=', $subjectId)
            ->count();

        $sequence = $count + 1;

        do {
            $code = "{$careerCode}-{$period}".str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
            $exists = Subject::where('pensum_id', $pensumId)
                ->where('code', $code)
                ->where('id', '!=', $subjectId)
                ->exists();

            if ($exists) {
                $sequence++;
            }
        } while ($exists);

        return $code;
    }
}
