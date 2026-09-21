<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\TransferPatientRequest;
use App\Http\Resources\OccupationResource;
use App\Models\Bed;
use App\Services\BedOccupationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class TransferController
{
    public function __construct(private readonly BedOccupationService $occupations) {}

    public function store(TransferPatientRequest $request): JsonResponse
    {
        $target = Bed::query()->where('code', $request->targetBedCode())->firstOrFail();

        $occupation = $this->occupations->transfer($request->cpf(), $target);

        return OccupationResource::make($occupation->load('bed', 'patient'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
