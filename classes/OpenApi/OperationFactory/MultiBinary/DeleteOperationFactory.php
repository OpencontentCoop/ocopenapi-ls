<?php

namespace Opencontent\OpenApi\OperationFactory\MultiBinary;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactory\ChildEndpointFactoryInterface;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\OperationFactory;

class DeleteOperationFactory extends OperationFactory\DeleteOperationFactory
{
    use OperationFactory\ChildOperationFactoryTrait;

    public function getSummary()
    {
        return \ezpI18n::tr('ocopenapi', 'Delete an existing binary file in the %name field by file name', null, ['%name' => $this->getPropertyFactory()->providePropertyIdentifier()]);
    }

    /**
     * @param ChildEndpointFactoryInterface $endpointFactory
     * @return \ezpRestMvcResult
     * @throws Exception
     * @throws \Opencontent\OpenApi\Exceptions\InvalidPayloadException
     * @throws \Opencontent\Opendata\Api\Exception\OutOfRangeException
     */
    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $result = new \ezpRestMvcResult();
        $parentResult = $this->getParentOperationResult($endpointFactory);
        $payload = $parentResult->variables[$this->getPropertyFactory()->providePropertyIdentifier()];
        $requestId = $this->getCurrentRequestParameter($endpointFactory->getParentOperationFactory()->getItemIdLabel());
        $requestFilename = $this->getCurrentRequestParameter($this->getItemIdLabel());
        $alreadyFilenames = array_column($payload, 'filename');
        if (!in_array($requestFilename, $alreadyFilenames)) {
            throw new NotFoundException($requestId . '/' . $this->getPropertyFactory()->providePropertyIdentifier() . '#' . $requestFilename);
        }

        foreach ($payload as $index => $fileInfo){
            if ($fileInfo['filename'] == $requestFilename){
                unset($payload[$index]);
            }
        }

        $payloadBuilder = new OperationFactory\ContentObject\PayloadBuilder();
        $payloadBuilder->setRemoteId($parentResult->variables['id']);
        $this->getPropertyFactory()->serializePayload(
            $payloadBuilder,
            [$this->getPropertyFactory()->providePropertyIdentifier() => $payload],
            $this->getCurrentRequestLanguage()
        );

        $this->getContentRepository()->update($payloadBuilder->getArrayCopy());

        return $result;
    }
}