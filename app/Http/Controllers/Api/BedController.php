<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\ListBedsRequest;
use App\Http\Resources\BedResource;
use App\Http\Resources\OccupationResource;
use App\Models\Bed;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class BedController
{
    private const HISTORY_PER_PAGE = 20;

    public function index(ListBedsRequest $request): AnonymousResourceCollection
    {
        $query = Bed::query()->with('currentOccupation.patient');

        if (($status = $request->status()) !== null) {
            $query->withStatus($status);
        }

        if (($sector = $request->sector()) !== null) {
            $query->inSector($sector);
        }

        $beds = $query->orderBy('sector')
            ->orderBy('code')
            ->paginate($request->perPage())
            ->withQueryString();

        return BedResource::collection($beds);
    }

    public function show(Bed $bed): BedResource
    {
        return BedResource::make($bed->load('currentOccupation.patient'));
    }

    public function history(Bed $bed): AnonymousResourceCollection
    {
        $occupations = $bed->occupations()
            ->with('patient')
            ->orderByDesc('occupied_at')
            ->orderByDesc('id')
            ->paginate(self::HISTORY_PER_PAGE);

        return OccupationResource::collection($occupations);
    }
}
