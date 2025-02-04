<?php

use Opencontent\OpenApi\Exceptions\TooManyRequestsException;
use Opencontent\OpenApi\Loader;
use Opencontent\Opendata\Api\Exception\BaseException;

class OpenApiErrorResponse implements ezcMvcResultStatusObject
{
    public $code;

    public $message;

    public $errorType;

    public $exception;

    public function __construct(
        $code = null,
        $message = null,
        $errorType = null,
        Throwable $exception = null
    )
    {
        $this->code = $code;
        $this->message = $message;
        $this->errorType = $errorType;
        $this->exception = $exception;
    }

    public function process(ezcMvcResponseWriter $writer)
    {
        if ($writer instanceof ezcMvcHttpResponseWriter) {
            header("HTTP/1.1 " . trim($this->code) . " " . ezpRestStatusResponse::$statusCodes[$this->code]);
            $writer->headers['X-Api-Error-Type'] = $this->errorType;
            $writer->headers['X-Api-Error-Message'] = $this->message;
        }
        if ($this->errorType == BaseException::cleanErrorCode(TooManyRequestsException::class)) {
            $writer->headers['Retry-After'] = OpenApiRateLimit::instance()->getRelativeNextReset();
        }

        if ($this->message !== null && $writer instanceof ezpRestHttpResponseWriter) {
            $writer->headers['Content-Type'] = 'application/json; charset=UTF-8';
            $body = [
                'error_type' => $this->errorType,
                'error_message' => $this->message
            ];
            if (Loader::instance()->getSettingsProvider()->provideSettings()->debugEnabled && $this->exception instanceof Throwable) {
                $body['error_debug_message'] = $this->exception->getMessage();
                $body['error_debug'] = explode(PHP_EOL, $this->exception->getTraceAsString());
                if ($this->exception->getPrevious() instanceof Exception){
                    $body['error_debug']['previous_message'] = $this->exception->getPrevious()->getMessage() . ' in ' . $this->exception->getPrevious()->getFile() . '#' . $this->exception->getPrevious()->getLine();
                    $body['error_debug']['previous_trace'] = explode(PHP_EOL, $this->exception->getPrevious()->getTraceAsString());
                }
            }
            $writer->response->body = json_encode($body);
        }
    }
}
