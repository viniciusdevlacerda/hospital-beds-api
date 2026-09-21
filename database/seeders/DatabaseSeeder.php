<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Bed;
use App\Models\BedOccupation;
use App\Services\BedOccupationService;
use App\ValueObjects\Cpf;
use Database\Factories\Support\CpfGenerator;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    private const SECTORS = [
        ['name' => 'UTI', 'prefix' => 'UTI', 'beds' => 6],
        ['name' => 'Enfermaria', 'prefix' => 'ENF', 'beds' => 12],
        ['name' => 'Apartamento', 'prefix' => 'APT', 'beds' => 4],
    ];

    private const PATIENTS_TO_ADMIT = 7;

    /**
     * O entrypoint semeia a cada boot do container, então semear precisa ser
     * idempotente: um restart não pode derrubar a aplicação no índice único.
     */
    public function run(BedOccupationService $occupations): void
    {
        $this->createBeds();
        $this->admitPatients($occupations);
    }

    private function createBeds(): void
    {
        foreach (self::SECTORS as $sector) {
            for ($number = 1; $number <= $sector['beds']; $number++) {
                Bed::query()->firstOrCreate(
                    ['code' => sprintf('%s-%02d', $sector['prefix'], $number)],
                    [
                        'sector' => $sector['name'],
                        'description' => "Leito {$number} — {$sector['name']}",
                    ],
                );
            }
        }
    }

    private function admitPatients(BedOccupationService $occupations): void
    {
        if (BedOccupation::query()->active()->exists()) {
            return;
        }

        Bed::query()
            ->inRandomOrder()
            ->limit(self::PATIENTS_TO_ADMIT)
            ->get()
            ->each(fn (Bed $bed) => $occupations->occupy(
                $bed,
                Cpf::fromString(CpfGenerator::generate()),
                fake()->name(),
            ));
    }
}
