<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Concerns\InteractsWithOccupations;
use Tests\TestCase;

final class ListBedsTest extends TestCase
{
    use InteractsWithOccupations;
    use RefreshDatabase;

    public function test_mostra_o_status_de_um_leito_livre(): void
    {
        $this->bed('UTI-01');

        $this->getJson('/api/beds/UTI-01')
            ->assertOk()
            ->assertJsonPath('data.status', 'free')
            ->assertJsonPath('data.status_label', 'Livre')
            ->assertJsonPath('data.occupation', null);
    }

    public function test_mostra_o_status_de_um_leito_ocupado(): void
    {
        $bed = $this->bed('UTI-01');
        $this->admit($bed, $this->cpf(), 'Ana Souza');

        $this->getJson('/api/beds/UTI-01')
            ->assertOk()
            ->assertJsonPath('data.status', 'occupied')
            ->assertJsonPath('data.occupation.patient.name', 'Ana Souza');
    }

    public function test_lista_todos_os_leitos_com_o_respectivo_status(): void
    {
        $this->admit($this->bed('UTI-01'));
        $this->bed('UTI-02');
        $this->bed('ENF-01', 'Enfermaria');

        $response = $this->getJson('/api/beds')->assertOk();

        $response->assertJsonCount(3, 'data');

        /** @var Collection<string, string> $statuses */
        $statuses = collect($response->json('data'))->pluck('status', 'code');

        $this->assertSame('occupied', $statuses['UTI-01']);
        $this->assertSame('free', $statuses['UTI-02']);
        $this->assertSame('free', $statuses['ENF-01']);
    }

    public function test_filtra_a_listagem_por_status(): void
    {
        $this->admit($this->bed('UTI-01'));
        $this->bed('UTI-02');

        $this->getJson('/api/beds?status=occupied')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'UTI-01');

        $this->getJson('/api/beds?status=free')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'UTI-02');
    }

    public function test_filtra_a_listagem_por_setor(): void
    {
        $this->bed('UTI-01');
        $this->bed('ENF-01', 'Enfermaria');

        $this->getJson('/api/beds?sector=Enfermaria')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'ENF-01');
    }

    public function test_rejeita_status_desconhecido_no_filtro(): void
    {
        $this->getJson('/api/beds?status=quebrado')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_expoe_o_historico_de_ocupacoes_do_leito(): void
    {
        $bed = $this->bed('UTI-01');

        $this->admit($bed, $this->cpf(), 'Primeiro Paciente');
        $this->deleteJson("/api/beds/{$bed->code}/occupation")->assertOk();
        $this->admit($bed, $this->cpf(), 'Segundo Paciente');

        $this->getJson("/api/beds/{$bed->code}/occupations")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.patient.name', 'Segundo Paciente')
            ->assertJsonPath('data.0.active', true)
            ->assertJsonPath('data.1.active', false);
    }
}
