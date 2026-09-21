<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Bed;
use App\Models\BedOccupation;
use App\Models\Patient;
use App\Services\BedOccupationService;
use App\ValueObjects\Cpf;
use Database\Factories\Support\CpfGenerator;

trait InteractsWithOccupations
{
    protected function bed(string $code = 'UTI-01', string $sector = 'UTI'): Bed
    {
        return Bed::factory()->withCode($code)->inSector($sector)->create();
    }

    protected function cpf(): Cpf
    {
        return Cpf::fromString(CpfGenerator::generate());
    }

    /** Pelo caminho real da aplicação: o cenário montado é sempre um estado alcançável. */
    protected function admit(Bed $bed, ?Cpf $cpf = null, string $name = 'Paciente de Teste'): BedOccupation
    {
        return app(BedOccupationService::class)->occupy($bed, $cpf ?? $this->cpf(), $name);
    }

    protected function activeOccupationsOf(Bed $bed): int
    {
        return BedOccupation::query()->active()->where('bed_id', $bed->id)->count();
    }

    protected function activeOccupationsOfPatient(Cpf $cpf): int
    {
        $patient = Patient::query()->withCpf($cpf)->first();

        return $patient === null
            ? 0
            : BedOccupation::query()->active()->where('patient_id', $patient->id)->count();
    }
}
