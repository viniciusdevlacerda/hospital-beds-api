<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithOccupations;
use Tests\TestCase;

final class OccupyBedTest extends TestCase
{
    use InteractsWithOccupations;
    use RefreshDatabase;

    public function test_inclui_um_paciente_em_um_leito_livre(): void
    {
        $bed = $this->bed('UTI-01');
        $cpf = $this->cpf();

        $response = $this->postJson("/api/beds/{$bed->code}/occupation", [
            'cpf' => $cpf->digits,
            'name' => 'Maria Andrade',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.active', true)
            ->assertJsonPath('data.bed.code', 'UTI-01')
            ->assertJsonPath('data.patient.name', 'Maria Andrade')
            ->assertJsonPath('data.patient.cpf', $cpf->masked())
            ->assertJsonPath('data.release_reason', null);

        $this->assertSame(1, $this->activeOccupationsOf($bed));
    }

    public function test_cadastra_o_paciente_na_primeira_internacao_e_reaproveita_pelo_cpf(): void
    {
        $cpf = $this->cpf();
        $first = $this->bed('UTI-01');
        $second = $this->bed('UTI-02');

        $this->admit($first, $cpf, 'João Pereira');
        $this->deleteJson("/api/beds/{$first->code}/occupation")->assertOk();

        $this->postJson("/api/beds/{$second->code}/occupation", [
            'cpf' => $cpf->masked(),
            'name' => 'João Pereira',
        ])->assertCreated();

        $this->assertSame(1, Patient::query()->count());
    }

    public function test_recusa_incluir_paciente_em_leito_ja_ocupado(): void
    {
        $bed = $this->bed('UTI-01');
        $this->admit($bed);

        $this->postJson("/api/beds/{$bed->code}/occupation", [
            'cpf' => $this->cpf()->digits,
            'name' => 'Outro Paciente',
        ])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'BED_ALREADY_OCCUPIED')
            ->assertJsonPath('error.details.bed', 'UTI-01');

        $this->assertSame(1, $this->activeOccupationsOf($bed));
    }

    public function test_recusa_internar_paciente_que_ja_ocupa_outro_leito(): void
    {
        $cpf = $this->cpf();
        $occupied = $this->bed('UTI-01');
        $free = $this->bed('ENF-05', 'Enfermaria');

        $this->admit($occupied, $cpf);

        $this->postJson("/api/beds/{$free->code}/occupation", [
            'cpf' => $cpf->digits,
            'name' => 'Paciente de Teste',
        ])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'PATIENT_ALREADY_ADMITTED')
            ->assertJsonPath('error.details.current_bed', 'UTI-01');

        $this->assertSame(1, $this->activeOccupationsOfPatient($cpf));
        $this->assertSame(0, $this->activeOccupationsOf($free));
    }

    public function test_recusa_cpf_invalido(): void
    {
        $bed = $this->bed('UTI-01');

        $this->postJson("/api/beds/{$bed->code}/occupation", [
            'cpf' => '11111111111',
            'name' => 'Paciente de Teste',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['details' => ['cpf']]]);
    }

    public function test_exige_o_nome_do_paciente(): void
    {
        $bed = $this->bed('UTI-01');

        $this->postJson("/api/beds/{$bed->code}/occupation", ['cpf' => $this->cpf()->digits])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_retorna_404_para_leito_inexistente(): void
    {
        $this->postJson('/api/beds/NAO-EXISTE/occupation', [
            'cpf' => $this->cpf()->digits,
            'name' => 'Paciente de Teste',
        ])
            ->assertNotFound()
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND');
    }
}
