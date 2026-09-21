<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ReleaseReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithOccupations;
use Tests\TestCase;

final class TransferPatientTest extends TestCase
{
    use InteractsWithOccupations;
    use RefreshDatabase;

    public function test_transfere_um_paciente_para_outro_leito(): void
    {
        $cpf = $this->cpf();
        $origin = $this->bed('UTI-01');
        $target = $this->bed('ENF-07', 'Enfermaria');

        $this->admit($origin, $cpf, 'Carla Nogueira');

        $this->postJson('/api/transfers', [
            'cpf' => $cpf->digits,
            'to_bed' => $target->code,
        ])
            ->assertCreated()
            ->assertJsonPath('data.active', true)
            ->assertJsonPath('data.bed.code', 'ENF-07')
            ->assertJsonPath('data.patient.cpf', $cpf->masked());

        $this->assertSame(0, $this->activeOccupationsOf($origin));
        $this->assertSame(1, $this->activeOccupationsOf($target));
        $this->assertSame(1, $this->activeOccupationsOfPatient($cpf));
    }

    public function test_encerra_a_ocupacao_de_origem_com_motivo_transferencia(): void
    {
        $cpf = $this->cpf();
        $origin = $this->bed('UTI-01');
        $target = $this->bed('ENF-07', 'Enfermaria');

        $original = $this->admit($origin, $cpf);

        $this->postJson('/api/transfers', [
            'cpf' => $cpf->digits,
            'to_bed' => $target->code,
        ])->assertCreated();

        $this->assertSame(ReleaseReason::Transfer, $original->refresh()->release_reason);
        $this->assertNotNull($original->released_at);
    }

    public function test_recusa_transferir_para_leito_ocupado(): void
    {
        $cpf = $this->cpf();
        $origin = $this->bed('UTI-01');
        $target = $this->bed('ENF-07', 'Enfermaria');

        $this->admit($origin, $cpf);
        $this->admit($target);

        $this->postJson('/api/transfers', [
            'cpf' => $cpf->digits,
            'to_bed' => $target->code,
        ])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'BED_ALREADY_OCCUPIED');

        $this->assertSame(1, $this->activeOccupationsOf($origin));
    }

    public function test_recusa_transferir_para_o_mesmo_leito(): void
    {
        $cpf = $this->cpf();
        $bed = $this->bed('UTI-01');

        $this->admit($bed, $cpf);

        $this->postJson('/api/transfers', [
            'cpf' => $cpf->digits,
            'to_bed' => $bed->code,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'TRANSFER_TO_SAME_BED');

        $this->assertSame(1, $this->activeOccupationsOf($bed));
    }

    public function test_recusa_transferir_paciente_que_nao_esta_internado(): void
    {
        $cpf = $this->cpf();
        $origin = $this->bed('UTI-01');
        $target = $this->bed('ENF-07', 'Enfermaria');

        $this->admit($origin, $cpf);
        $this->deleteJson("/api/beds/{$origin->code}/occupation")->assertOk();

        $this->postJson('/api/transfers', [
            'cpf' => $cpf->digits,
            'to_bed' => $target->code,
        ])
            ->assertNotFound()
            ->assertJsonPath('error.code', 'PATIENT_NOT_ADMITTED');
    }

    public function test_retorna_404_ao_transferir_paciente_desconhecido(): void
    {
        $this->bed('ENF-07', 'Enfermaria');

        $this->postJson('/api/transfers', [
            'cpf' => $this->cpf()->digits,
            'to_bed' => 'ENF-07',
        ])
            ->assertNotFound()
            ->assertJsonPath('error.code', 'PATIENT_NOT_FOUND');
    }

    public function test_retorna_404_quando_o_leito_de_destino_nao_existe(): void
    {
        $cpf = $this->cpf();
        $this->admit($this->bed('UTI-01'), $cpf);

        $this->postJson('/api/transfers', [
            'cpf' => $cpf->digits,
            'to_bed' => 'NAO-EXISTE',
        ])
            ->assertNotFound()
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND');
    }
}
