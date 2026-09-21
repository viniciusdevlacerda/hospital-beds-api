<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\ValidCpf;
use App\ValueObjects\Cpf;
use Illuminate\Foundation\Http\FormRequest;

final class TransferPatientRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'cpf' => ['required', 'string', new ValidCpf],
            'to_bed' => ['required', 'string'],
        ];
    }

    public function cpf(): Cpf
    {
        return Cpf::fromString($this->string('cpf')->toString());
    }

    public function targetBedCode(): string
    {
        return $this->string('to_bed')->toString();
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'cpf' => 'CPF',
            'to_bed' => 'leito de destino',
        ];
    }
}
