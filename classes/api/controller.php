<?php

use Opencontent\OpenApi;
use Opencontent\OpenApi\Exceptions\UnauthorizedException;
use Opencontent\OpenApi\SchemaBuilder\SchemaBuilderToolsTrait;
use Opencontent\Opendata\Api\Exception\BaseException;

class OpenApiController extends ezpRestMvcController
{
    /**
     * @var ezpRestRequest
     */
    protected $request;

    /**
     * @var OpenApi\Loader
     */
    private $apiLoader;

    private $baseUri;

    private $rateLimitHandler;

    public function __construct($action, ezcMvcRequest $request)
    {
        parent::__construct($action, $request);

        $hostUri = $this->request->getHostURI();
        $hostUri = str_replace('http:/', 'https:/', $hostUri); //@todo
        $apiName = ezpRestPrefixFilterInterface::getApiProviderName();
        $apiPrefix = eZINI::instance('rest.ini')->variable('System', 'ApiPrefix');
        $this->baseUri = $hostUri . $apiPrefix . '/' . $apiName;
        $this->apiLoader = OpenApi\Loader::instance();
        $this->rateLimitHandler = OpenApiRateLimit::instance();

        \eZModule::setGlobalPathList(
            \eZINI::instance('module.ini')->variable('ModuleSettings', 'ModuleRepositories')
        );
        eZDB::setErrorHandling(eZDB::ERROR_HANDLING_EXCEPTIONS);

        eZINI::instance('ezfind.ini')->removeSetting('SearchFilters', 'RawFilterList');
        eZINI::instance('ezfind.ini')->setVariable('LanguageSearch', 'SearchMainLanguageOnly', 'disabled');
        eZINI::instance()->setVariable('RegionalSettings', 'ShowUntranslatedObjects', 'enabled');
    }

    public function doEndpoint()
    {
        $builder = $this->apiLoader->getSchemaBuilder($this->request->get);
        $schema = $builder->build()->toArray();
        $result = new ezpRestMvcResult();
        $result->variables = $schema;

        return $result;
    }

    public function doUnauthorized()
    {
        return $this->doExceptionResult(
            new UnauthorizedException($this->request->variables['error_text'] ?? null)
        );
    }

    public function doAction()
    {
        try {
            $this->rateLimitHandler->checkAndUpdateRequestCount();
            $provider = $this->apiLoader->getEndpointProvider();
            $operation = $provider->getOperationFactoryById($this->request->variables['operationId']);
            $endpoint = $provider->getEndpointFactoryByOperationId($this->request->variables['operationId'])
                ->setBaseUri($this->baseUri);
            $operation->setCurrentRequest($this->request);

            $result = $operation->handleCurrentRequest($endpoint);
            $this->rateLimitHandler->setHeaders();

            header("X-Api-User: " . eZUser::currentUserID());
            header("X-Api-Operation: " . $operation->getId());
            header("Cache-Control: private, no-cache, no-store, must-revalidate");
            $languages = SchemaBuilderToolsTrait::getLanguageList();
            header("Content-Language: " . $languages[$operation->getCurrentRequestLanguage()]);

            return $result;

        } catch (Exception $e) {
            $result = $this->doExceptionResult($e);
        }

        return $result;
    }

    private function doExceptionResult(Exception $exception)
    {
        $result = new ezcMvcResult;
        $result->variables['message'] = $exception->getMessage();

        //$this->getLogger()->error($exception->getMessage() . PHP_EOL . $exception->getTraceAsString(), ['api_request' => $_SERVER['QUERY_STRING']]);

        $serverErrorCode = ezpHttpResponseCodes::SERVER_ERROR;
        $errorType = OpenApi\Exception::cleanErrorCode(get_class($exception));
        if ($exception instanceof BaseException) {
            $serverErrorCode = $exception->getServerErrorCode();
            $errorType = $exception->getErrorType();
        }

        $this->rateLimitHandler->setHeaders();
        $result->status = new OpenApiErrorResponse(
            $serverErrorCode,
            $exception->getMessage(),
            $errorType,
            $exception
        );

        return $result;
    }

    private function getHostURI()
    {
        $hostUri = $this->request->getHostURI();
        if (eZSys::isSSLNow()) {
            $hostUri = str_replace('http:', 'https:', $hostUri);
        }

        return $hostUri;
    }
}
