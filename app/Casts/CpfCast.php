<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\Cpf;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<Cpf, Cpf|string>
 */
final class CpfCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Cpf
    {
        return $value === null ? null : Cpf::fromString((string) $value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, string|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        $cpf = $value instanceof Cpf ? $value : Cpf::fromString((string) $value);

        return [$key => $cpf->digits];
    }
}
