<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Patient;
use Database\Factories\Support\CpfGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
final class PatientFactory extends Factory
{
    protected $model = Patient::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'cpf' => CpfGenerator::generate(),
        ];
    }
}
