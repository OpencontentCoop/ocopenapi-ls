<?php

namespace Opencontent\OpenApi\OperationFactory\Relations;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactory\ChildEndpointFactoryInterface;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\OpenApi\SchemaFactory\RelationsSchemaFactory;

class ReadOperationFactory extends OperationFactory\ReadOperationFactory
{
    use OperationFactory\ChildOperationFactoryTrait;

    public function getSummary()
    {
        return \ezpI18n::tr('ocopenapi', 'Find an existing relationship in the %name field by related resource id', null, [
            '%name' => $this->getPropertyFactory()->providePropertyIdentifier()
        ]);
    }

    /**
     * @param ChildEndpointFactoryInterface $endpointFactory
     * @return \ezpRestMvcResult
     * @throws Exception
     * @throws NotFoundException
     */
    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $result = new \ezpRestMvcResult();
        $parentResult = $this->getParentOperationResult($endpointFactory);
        $data = $parentResult->variables[$this->getPropertyFactory()->providePropertyIdentifier()];

        $requestId = $this->getCurrentRequestParameter($endpointFactory->getParentOperationFactory()->getItemIdLabel());
        $requestIndex = $this->getCurrentRequestParameter($this->getItemIdLabel());

        foreach ($data as $item) {
            if ($item['id'] == $requestIndex) {
                $result->variables = $item;

                return $result;
            }
        }
        throw new NotFoundException($requestId . '/' . $this->getPropertyFactory()->providePropertyIdentifier() . '#' . $requestIndex);
    }

    /**
     * @return OA\Response[]
     */
    protected function generateResponseList()
    {
        /** @var RelationsSchemaFactory $schemaFactory */
        $schemaFactory = $this->getSchemaFactories()[0];

        $responseList = parent::generateResponseList();
        $responseList['200'] = new OA\Response('Successful response', [
            'application/json' => new OA\MediaType([
                'schema' => $schemaFactory->generateSchema()
            ])
        ], $this->generateResponseHeaders());

        return $responseList;
    }

}