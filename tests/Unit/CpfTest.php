<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\InvalidCpf;
use App\ValueObjects\Cpf;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CpfTest extends TestCase
{
    #[DataProvider('cpfsValidos')]
    public function test_aceita_cpf_valido_com_e_sem_formatacao(string $input): void
    {
        $this->assertSame('52998224725', Cpf::fromString($input)->digits);
    }

    /** @return array<string, array{string}> */
    public static function cpfsValidos(): array
    {
        return [
            'sem formatação' => ['52998224725'],
            'com pontuação' => ['529.982.247-25'],
            'com espaços' => [' 529 982 247 25 '],
        ];
    }

    public function test_rejeita_cpf_com_digito_verificador_incorreto(): void
    {
        $this->assertFalse(Cpf::isValid('52998224726'));
    }

    #[DataProvider('cpfsInvalidos')]
    public function test_rejeita_cpf_invalido(string $input): void
    {
        $this->assertFalse(Cpf::isValid($input));
    }

    /** @return array<string, array{string}> */
    public static function cpfsInvalidos(): array
    {
        return [
            'todos os dígitos zero' => ['00000000000'],
            'todos os dígitos um' => ['11111111111'],
            'todos os dígitos nove' => ['99999999999'],
            'vazio' => [''],
            'curto demais' => ['123'],
            'longo demais' => ['529982247250'],
        ];
    }

    public function test_lanca_excecao_ao_construir_a_partir_de_valor_invalido(): void
    {
        $this->expectException(InvalidCpf::class);

        Cpf::fromString('11111111111');
    }

    public function test_formata_o_cpf_com_mascara(): void
    {
        $this->assertSame('529.982.247-25', Cpf::fromString('52998224725')->masked());
    }
}
