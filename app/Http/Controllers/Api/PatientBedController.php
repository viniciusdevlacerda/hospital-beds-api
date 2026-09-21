<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\PatientNotAdmitted;
use App\Exceptions\PatientNotFound;
use App\Http\Resources\BedResource;
use App\Models\Patient;
use App\ValueObjects\Cpf;

final class PatientBedController
{
    public function show(string $cpf): BedResource
    {
        $document = Cpf::fromString($cpf);

        $patient = Patient::query()->withCpf($document)->first()
            ?? throw new PatientNotFound($document);

        $occupation = $patient->currentOccupation
            ?? throw new PatientNotAdmitted($document);

        return BedResource::make($occupation->bed->load('currentOccupation.patient'));
    }
}
