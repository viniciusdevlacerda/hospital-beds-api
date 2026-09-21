<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\OccupyBedRequest;
use App\Http\Resources\OccupationResource;
use App\Models\Bed;
use App\Services\BedOccupationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class BedOccupationController
{
    public function __construct(private readonly BedOccupationService $occupations) {}

    public function store(OccupyBedRequest $request, Bed $bed): JsonResponse
    {
        $occupation = $this->occupations->occupy($bed, $request->cpf(), $request->patientName());

        return OccupationResource::make($occupation->load('bed', 'patient'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Bed $bed): OccupationResource
    {
        return OccupationResource::make(
            $this->occupations->release($bed)->load('bed', 'patient'),
        );
    }
}
