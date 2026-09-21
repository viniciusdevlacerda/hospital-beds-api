<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ReleaseReason;
use App\Exceptions\BedAlreadyOccupied;
use App\Exceptions\BedNotOccupied;
use App\Exceptions\PatientAlreadyAdmitted;
use App\Exceptions\PatientNotAdmitted;
use App\Exceptions\PatientNotFound;
use App\Exceptions\TransferToSameBed;
use App\Models\Bed;
use App\Models\BedOccupation;
use App\Models\Patient;
use App\ValueObjects\Cpf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class BedOccupationService
{
    public function occupy(Bed $bed, Cpf $cpf, string $patientName): BedOccupation
    {
        return DB::transaction(function () use ($bed, $cpf, $patientName): BedOccupation {
            $bed = $this->lockBed($bed);
            $patient = $this->findOrCreatePatient($cpf, $patientName);

            $this->guardBedIsFree($bed);
            $this->guardPatientIsNotAdmitted($patient);

            return $this->openOccupation($bed, $patient);
        });
    }

    public function release(Bed $bed): BedOccupation
    {
        return DB::transaction(function () use ($bed): BedOccupation {
            $bed = $this->lockBed($bed);

            $occupation = $this->activeOccupationOfBed($bed) ?? throw new BedNotOccupied($bed);

            return $this->closeOccupation($occupation, ReleaseReason::Discharge);
        });
    }

    public function transfer(Cpf $cpf, Bed $target): BedOccupation
    {
        return DB::transaction(function () use ($cpf, $target): BedOccupation {
            $target = $this->lockBed($target);
            $patient = $this->lockPatient($cpf);

            $current = $this->activeOccupationOfPatient($patient) ?? throw new PatientNotAdmitted($cpf);

            if ($current->bed_id === $target->id) {
                throw new TransferToSameBed($target);
            }

            $this->guardBedIsFree($target);

            $this->closeOccupation($current, ReleaseReason::Transfer);

            return $this->openOccupation($target, $patient);
        });
    }

    private function guardBedIsFree(Bed $bed): void
    {
        if ($this->activeOccupationOfBed($bed) !== null) {
            throw new BedAlreadyOccupied($bed);
        }
    }

    private function guardPatientIsNotAdmitted(Patient $patient): void
    {
        $occupation = $this->activeOccupationOfPatient($patient);

        if ($occupation !== null) {
            throw new PatientAlreadyAdmitted($patient, $occupation->bed);
        }
    }

    private function openOccupation(Bed $bed, Patient $patient): BedOccupation
    {
        return BedOccupation::query()->create([
            'bed_id' => $bed->id,
            'patient_id' => $patient->id,
            'occupied_at' => CarbonImmutable::now(),
        ]);
    }

    private function closeOccupation(BedOccupation $occupation, ReleaseReason $reason): BedOccupation
    {
        $occupation->update([
            'released_at' => CarbonImmutable::now(),
            'release_reason' => $reason,
        ]);

        return $occupation;
    }

    private function activeOccupationOfBed(Bed $bed): ?BedOccupation
    {
        return BedOccupation::query()->active()->where('bed_id', $bed->id)->first();
    }

    private function activeOccupationOfPatient(Patient $patient): ?BedOccupation
    {
        return BedOccupation::query()->active()->where('patient_id', $patient->id)->first();
    }

    private function lockBed(Bed $bed): Bed
    {
        return Bed::query()->whereKey($bed->getKey())->lockForUpdate()->firstOrFail();
    }

    private function lockPatient(Cpf $cpf): Patient
    {
        return Patient::query()->withCpf($cpf)->lockForUpdate()->first()
            ?? throw new PatientNotFound($cpf);
    }

    private function findOrCreatePatient(Cpf $cpf, string $name): Patient
    {
        Patient::query()->firstOrCreate(['cpf' => $cpf->digits], ['name' => $name]);

        return $this->lockPatient($cpf);
    }
}
