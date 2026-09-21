<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ReleaseReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithOccupations;
use Tests\TestCase;

final class ReleaseBedTest extends TestCase
{
    use InteractsWithOccupations;
    use RefreshDatabase;

    public function test_desocupa_um_leito_e_registra_a_alta(): void
    {
        $bed = $this->bed('ENF-03', 'Enfermaria');
        $occupation = $this->admit($bed);

        $this->deleteJson("/api/beds/{$bed->code}/occupation")
            ->assertOk()
            ->assertJsonPath('data.active', false)
            ->assertJsonPath('data.release_reason', ReleaseReason::Discharge->value)
            ->assertJsonPath('data.bed.code', 'ENF-03');

        $this->assertSame(0, $this->activeOccupationsOf($bed));
        $this->assertNotNull($occupation->refresh()->released_at);
    }

    public function test_deixa_o_leito_disponivel_para_nova_internacao_apos_a_alta(): void
    {
        $bed = $this->bed('ENF-03', 'Enfermaria');
        $this->admit($bed);

        $this->deleteJson("/api/beds/{$bed->code}/occupation")->assertOk();

        $this->postJson("/api/beds/{$bed->code}/occupation", [
            'cpf' => $this->cpf()->digits,
            'name' => 'Novo Paciente',
        ])->assertCreated();

        $this->assertSame(1, $this->activeOccupationsOf($bed));
        $this->assertSame(2, $bed->occupations()->count());
    }

    public function test_recusa_desocupar_leito_que_ja_esta_livre(): void
    {
        $bed = $this->bed('ENF-03', 'Enfermaria');

        $this->deleteJson("/api/beds/{$bed->code}/occupation")
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'BED_NOT_OCCUPIED')
            ->assertJsonPath('error.details.bed', 'ENF-03');
    }

    public function test_retorna_404_ao_desocupar_leito_inexistente(): void
    {
        $this->deleteJson('/api/beds/NAO-EXISTE/occupation')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND');
    }
}
