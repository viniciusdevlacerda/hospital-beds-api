<?php

declare(strict_types=1);

use App\Exceptions\DomainException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware()
    ->withExceptions(function (Exceptions $exceptions): void {
        // Regra de negócio violada é resposta esperada, não incidente: um 409 de
        // leito ocupado não deve virar alerta no monitoramento.
        $exceptions->dontReport(DomainException::class);

        $problem = static fn (string $code, string $message, array $details, int $status): JsonResponse => response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status);

        $exceptions->render(fn (ValidationException $e) => $problem(
            'VALIDATION_FAILED',
            'Os dados enviados são inválidos.',
            $e->errors(),
            422,
        ));

        $exceptions->render(fn (NotFoundHttpException $e) => $problem(
            'RESOURCE_NOT_FOUND',
            'Recurso não encontrado.',
            [],
            404,
        ));

        $exceptions->render(function (HttpExceptionInterface $e) use ($problem): ?JsonResponse {
            if ($e->getStatusCode() < 500) {
                return $problem('HTTP_ERROR', $e->getMessage() ?: 'Requisição inválida.', [], $e->getStatusCode());
            }

            return null;
        });
    })->create();
