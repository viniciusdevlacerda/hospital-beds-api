<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bed;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bed>
 */
final class BedFactory extends Factory
{
    protected $model = Bed::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('???-##')),
            'sector' => fake()->randomElement(['UTI', 'Enfermaria', 'Apartamento']),
            'description' => null,
        ];
    }

    public function inSector(string $sector): self
    {
        return $this->state(fn (): array => ['sector' => $sector]);
    }

    public function withCode(string $code): self
    {
        return $this->state(fn (): array => ['code' => $code]);
    }
}
