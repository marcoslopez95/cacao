<?php

namespace App\Http\Wrappers\Security;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserWrapper extends Collection
{
    private ?string $resolvedPlainPassword = null;

    public function getName(): string
    {
        return $this->get('name');
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
            'name' => $this->getName(),
            'email' => $this->getEmail(),
            'password' => $this->getHashedPassword(),
            'active' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getUpdateData(): array
    {
        return [
            'name' => $this->getName(),
            'email' => $this->getEmail(),
        ];
    }
}
