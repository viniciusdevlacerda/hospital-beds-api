<?php

declare(strict_types=1);

namespace App\Exceptions\Concerns;

use App\Exceptions\DomainException;
use Illuminate\Http\JsonResponse;

/**
 * O Laravel invoca render() na própria exceção, então nenhuma precisa ser
 * registrada em um handler.
 *
 * @mixin DomainException
 */
trait RendersProblemResponse
{
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $this->errorCode(),
                'message' => $this->getMessage(),
                'details' => $this->context(),
            ],
        ], $this->httpStatus());
    }
}
