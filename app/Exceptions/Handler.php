<?php


namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\RouteNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Testing\Fakes\ExceptionHandlerFake;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException as ExceptionRouteNotFoundException;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{

    /**
     * The inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     *
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',

    ];

    public function render($request, Throwable $e): JsonResponse
    {
        if ($request->is('api/*')) {
            return match (true) {
                $e instanceof AuthenticationException => response()->json([
                    'success' => false,
                    'message' => 'Unauthorized Request.',
                ], Response::HTTP_UNAUTHORIZED),

                $e instanceof ValidationException => response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY),

                $e instanceof NotFoundHttpException => response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                ], Response::HTTP_NOT_FOUND),

                $e instanceof HttpException => response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $e->getStatusCode()),

                $e instanceof ExceptionRouteNotFoundException => response()->json([
                    'success' => false,
                    'message' => 'Route not found.',
                ], Response::HTTP_NOT_FOUND),

                default => response()->json([
                    'success' => false,
                    'message' => 'An unexpected error occurred.',
                    'details' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR),
            };
        }

        return parent::render($request, $e);

    }

    
}
