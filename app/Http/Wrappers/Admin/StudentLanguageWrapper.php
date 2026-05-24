<?php

namespace App\Http\Wrappers\Admin;

use Illuminate\Support\Collection;

class StudentLanguageWrapper extends Collection
{
    public function __construct(array $validated)
    {
        parent::__construct($validated);
    }

    public function getLanguageId(): int
    {
        return (int) $this->get('language_id');
    }

    public function getLanguageLevelId(): int
    {
        return (int) $this->get('language_level_id');
    }

    public function isMotherTongue(): bool
    {
        return (bool) $this->get('is_mother_tongue', false);
    }
}
