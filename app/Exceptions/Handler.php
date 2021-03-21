<?php

namespace App\Exceptions;

use App\Libs\Helpers;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,

        // Custome Exception ...
        ValidateException::class,
        UserNotFoundException::class,
        UserNotRegisteredException::class,
        IncorrectPasswordException::class,
        UnauthorizedException::class,
        FailedAddEditGlobalException::class,
        RoleNotFoundException::class,
        GroupNotFoundException::class,
        GroupExistException::class,
        RoleExistException::class,
        UserExistException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        if (in_array(get_class($exception), $this->dontReport) === false) {
            Helpers::SendErrorReportToTelegram($exception);
        }
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        self::RenderException($exception);
        return parent::render($request, $exception);
    }

    private static function RenderException(Throwable $exception)
    {
        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return self::Response('Requested Data Not Found.', 'ModelNotFoundException', 404);
        }
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return self::Response('Route Not Found.', 'NotFoundHttpException', 404);
        }
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
            return self::Response('Method Not Allowed.', 'MethodNotAllowedHttpException', 405);
        }
    }

    private static function Response(string $message = "", string $reason = "", int $code = 0)
    {
        return Response([
            'api_version' => '1.0',
            'error' => [
                'code' => $code,
                'message' => $message,
                'errors' => [
                    [
                        'reason' => $reason,
                        'message' => $message,
                    ],
                ],
            ],
        ], $code);
    }
}
