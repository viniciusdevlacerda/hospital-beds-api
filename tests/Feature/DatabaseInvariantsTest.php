<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Patient;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithOccupations;
use Tests\TestCase;

/**
 * Ataca o banco sem passar pelo service, de propósito: prova que a invariante
 * vive no schema, e não apenas no código da aplicação.
 */
final class DatabaseInvariantsTest extends TestCase
{
    use InteractsWithOccupations;
    use RefreshDatabase;

    public function test_impede_duas_ocupacoes_ativas_no_mesmo_leito(): void
    {
        $bed = $this->bed('UTI-01');
        $this->admit($bed);

        $intruder = Patient::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('bed_occupations')->insert([
            'bed_id' => $bed->id,
            'patient_id' => $intruder->id,
            'occupied_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_impede_o_mesmo_paciente_em_dois_leitos_ao_mesmo_tempo(): void
    {
        $cpf = $this->cpf();
        $this->admit($this->bed('UTI-01'), $cpf);

        $other = $this->bed('ENF-01', 'Enfermaria');
        $patient = Patient::query()->withCpf($cpf)->firstOrFail();

        $this->expectException(QueryException::class);

        DB::table('bed_occupations')->insert([
            'bed_id' => $other->id,
            'patient_id' => $patient->id,
            'occupied_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_permite_varias_ocupacoes_encerradas_no_mesmo_leito(): void
    {
        $bed = $this->bed('UTI-01');

        for ($stay = 1; $stay <= 3; $stay++) {
            $this->admit($bed);
            $this->deleteJson("/api/beds/{$bed->code}/occupation")->assertOk();
        }

        $this->assertSame(3, $bed->occupations()->count());
        $this->assertSame(0, $this->activeOccupationsOf($bed));
    }

    public function test_permite_que_um_paciente_tenha_varias_internacoes_ao_longo_do_tempo(): void
    {
        $cpf = $this->cpf();
        $first = $this->bed('UTI-01');
        $second = $this->bed('ENF-01', 'Enfermaria');

        $this->admit($first, $cpf);
        $this->deleteJson("/api/beds/{$first->code}/occupation")->assertOk();
        $this->admit($second, $cpf);

        $this->assertSame(1, $this->activeOccupationsOfPatient($cpf));
        $this->assertSame(2, Patient::query()->withCpf($cpf)->firstOrFail()->occupations()->count());
    }
}
