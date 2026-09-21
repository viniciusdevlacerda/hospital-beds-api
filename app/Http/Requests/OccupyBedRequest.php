<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\ValidCpf;
use App\ValueObjects\Cpf;
use Illuminate\Foundation\Http\FormRequest;

final class OccupyBedRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'cpf' => ['required', 'string', new ValidCpf],
            'name' => ['required', 'string', 'min:2', 'max:255'],
        ];
    }

    public function cpf(): Cpf
    {
        return Cpf::fromString($this->string('cpf')->toString());
    }

    public function patientName(): string
    {
        return trim($this->string('name')->toString());
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'cpf' => 'CPF',
            'name' => 'nome do paciente',
        ];
    }
}
