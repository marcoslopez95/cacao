<?php

namespace App\Http\Wrappers\Security;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserWrapper extends Collection
{
    private ?string $resolvedPlainPassword = null;

    public function getFirstName(): string
    {
        // Support both the split fields and the legacy single 'name' field.
        if ($this->has('first_name')) {
            return $this->get('first_name');
        }

        $parts = explode(' ', (string) $this->get('name', ''), 2);

        return $parts[0] ?? '';
    }

    public function getLastName(): string
    {
        if ($this->has('last_name')) {
            return $this->get('last_name');
        }

        $parts = explode(' ', (string) $this->get('name', ''), 2);

        return $parts[1] ?? '';
    }

    public function getEmail(): string
    {
        return $this->get('email');
    }

    public function getRoleName(): string
    {
        return $this->get('role');
    }

    /**
     * Returns the roles array (plural) used in update operations.
     *
     * @return array<int, string>
     */
    public function getRoles(): array
    {
        return $this->get('roles', []);
    }

    public function getDocumentTypeId(): ?int
    {
        return $this->has('document_type_id') && $this->get('document_type_id') !== null
            ? (int) $this->get('document_type_id')
            : null;
    }

    public function getDocumentNumber(): ?string
    {
        return $this->get('document_number');
    }

    public function getBirthDate(): ?string
    {
        return $this->get('birth_date');
    }

    public function getGenderId(): ?int
    {
        return $this->has('gender_id') && $this->get('gender_id') !== null
            ? (int) $this->get('gender_id')
            : null;
    }

    public function getNationalityId(): ?int
    {
        return $this->has('nationality_id') && $this->get('nationality_id') !== null
            ? (int) $this->get('nationality_id')
            : null;
    }

    public function getPhonePrimary(): ?string
    {
        return $this->get('phone_primary');
    }

    public function getPhonePrimaryDial(): ?string
    {
        return $this->get('phone_primary_dial');
    }

    public function getPhoneSecondary(): ?string
    {
        return $this->get('phone_secondary');
    }

    public function getPhoneSecondaryDial(): ?string
    {
        return $this->get('phone_secondary_dial');
    }

    public function getPasswordMode(): string
    {
        return $this->get('password_mode', 'link');
    }

    public function sendsResetLink(): bool
    {
        return $this->getPasswordMode() === 'link';
    }

    public function getHashedPassword(): string
    {
        return Hash::make($this->resolvePlainPassword());
    }

    public function getPlainPassword(): ?string
    {
        return match ($this->getPasswordMode()) {
            'link' => null,
            default => $this->resolvePlainPassword(),
        };
    }

    private function resolvePlainPassword(): string
    {
        return $this->resolvedPlainPassword ??= match ($this->getPasswordMode()) {
            'manual' => $this->get('password'),
            'random' => Str::random(16),
            default => Str::random(32),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function getStoreData(): array
    {
        return [
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
            'email' => $this->getEmail(),
            'password' => $this->getHashedPassword(),
            'active' => true,
        ];
    }

    /**
     * Build the array used in User::update().
     *
     * Core identity fields (first_name, last_name, email) are always included.
     * S01 nullable fields are only included when the key was present in the
     * incoming payload — this prevents a partial PATCH (e.g. only sending
     * document fields) from accidentally nullifying gender_id, nationality_id
     * or other fields that were not part of the request.
     *
     * @return array<string, mixed>
     */
    public function getUpdateData(): array
    {
        $data = [
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
            'email' => $this->getEmail(),
        ];

        // Only include S01 fields that were explicitly sent in the payload.
        if ($this->has('document_type_id')) {
            $data['document_type_id'] = $this->getDocumentTypeId();
        }

        if ($this->has('document_number')) {
            $data['document_number'] = $this->getDocumentNumber();
        }

        if ($this->has('birth_date')) {
            $data['birth_date'] = $this->getBirthDate();
        }

        if ($this->has('gender_id')) {
            $data['gender_id'] = $this->getGenderId();
        }

        if ($this->has('nationality_id')) {
            $data['nationality_id'] = $this->getNationalityId();
        }

        if ($this->has('phone_primary')) {
            $data['phone_primary'] = $this->getPhonePrimary();
        }

        if ($this->has('phone_primary_dial')) {
            $data['phone_primary_dial'] = $this->getPhonePrimaryDial();
        }

        if ($this->has('phone_secondary')) {
            $data['phone_secondary'] = $this->getPhoneSecondary();
        }

        if ($this->has('phone_secondary_dial')) {
            $data['phone_secondary_dial'] = $this->getPhoneSecondaryDial();
        }

        return $data;
    }
}
