<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponse;
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
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // not found types response error sending
        if($exception instanceof HttpException){
            $code = $exception->getStatusCode();
            $message = Response::$statusTexts[$code];
            return $this->errorResponse($message, $code);
        }

        //model not found error message , data is not found on database
        if($exception instanceof ModelNotFoundException){
            //removing the namespace of the model class using php class_baseName()
            $removeNameSpace = class_basename($exception->getModel());

            //covert to lower case
            $lowerCaseModel = strtolower($removeNameSpace);
            return  $this->errorResponse("Does not Exist any instance of {$lowerCaseModel} with the given id", Response::HTTP_NOT_FOUND);

        }

        //authorization exception
        if($exception instanceof AuthorizationException){
            return $this->errorResponse($exception->getMessage(), Response::HTTP_FORBIDDEN);
        }

        //authentication Exception
        if($exception instanceof AuthenticationException){
            return $this->errorResponse($exception->getMessage(), Response::HTTP_UNAUTHORIZED);
        }

        //validation exception
        if($exception instanceof ValidationException){
            $errors = $exception->validator->errors()->getMessages();
            return  $this->errorResponse($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        //we have exception that we can not handle but app-debug is true
        if(env('APP_DEBUG', false)){
            return parent::render($request, $exception);
        }
        //we have exception that we can not handle
        return $this->errorResponse("Unexpected error. Try Later", Response::HTTP_INTERNAL_SERVER_ERROR);

    }
}
