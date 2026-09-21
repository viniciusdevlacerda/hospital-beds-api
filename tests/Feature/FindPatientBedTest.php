<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithOccupations;
use Tests\TestCase;

final class FindPatientBedTest extends TestCase
{
    use InteractsWithOccupations;
    use RefreshDatabase;

    public function test_descobre_o_leito_de_um_paciente_a_partir_do_cpf(): void
    {
        $cpf = $this->cpf();
        $bed = $this->bed('APT-02', 'Apartamento');

        $this->admit($bed, $cpf, 'Rafael Lima');

        $this->getJson("/api/patients/{$cpf->digits}/bed")
            ->assertOk()
            ->assertJsonPath('data.code', 'APT-02')
            ->assertJsonPath('data.sector', 'Apartamento')
            ->assertJsonPath('data.status', 'occupied')
            ->assertJsonPath('data.occupation.patient.name', 'Rafael Lima');
    }

    public function test_aceita_o_cpf_formatado_na_url(): void
    {
        $cpf = $this->cpf();
        $this->admit($this->bed('APT-02', 'Apartamento'), $cpf);

        $this->getJson("/api/patients/{$cpf->masked()}/bed")
            ->assertOk()
            ->assertJsonPath('data.code', 'APT-02');
    }

    public function test_retorna_404_quando_o_paciente_nao_esta_internado(): void
    {
        $cpf = $this->cpf();
        $bed = $this->bed('APT-02', 'Apartamento');

        $this->admit($bed, $cpf);
        $this->deleteJson("/api/beds/{$bed->code}/occupation")->assertOk();

        $this->getJson("/api/patients/{$cpf->digits}/bed")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'PATIENT_NOT_ADMITTED');
    }

    public function test_retorna_404_quando_o_cpf_nao_pertence_a_nenhum_paciente(): void
    {
        $this->getJson('/api/patients/'.$this->cpf()->digits.'/bed')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'PATIENT_NOT_FOUND');
    }

    public function test_retorna_422_quando_o_cpf_da_url_e_invalido(): void
    {
        $this->getJson('/api/patients/11111111111/bed')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_CPF');
    }
}
