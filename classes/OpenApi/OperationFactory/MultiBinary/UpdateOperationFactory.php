<?php

namespace Opencontent\OpenApi\OperationFactory\MultiBinary;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactory\ChildEndpointFactoryInterface;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\Opendata\Api\Values\Content;

class UpdateOperationFactory extends OperationFactory\UpdateOperationFactory
{
    use OperationFactory\ChildOperationFactoryTrait;

    public function getSummary()
    {
        return \ezpI18n::tr('ocopenapi', 'Update an existing binary file in the %name field by file name', null, [
            '%name' => $this->getPropertyFactory()->providePropertyIdentifier()
        ]);
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
        $currentPayload = $this->getCurrentPayload();
        $alreadyFilenames = array_column($payload, 'filename');
        if (!in_array($requestFilename, $alreadyFilenames)) {
            throw new NotFoundException($requestId . '/' . $this->getPropertyFactory()->providePropertyIdentifier() . '#' . $requestFilename);
        }

        foreach ($payload as $index => $fileInfo){
            if ($fileInfo['filename'] == $requestFilename){
                unset($payload[$index]);
            }
        }

        $payload[] = $currentPayload;
        $payloadBuilder = new OperationFactory\ContentObject\PayloadBuilder();
        $payloadBuilder->setRemoteId($parentResult->variables['id']);
        $this->getPropertyFactory()->serializePayload(
            $payloadBuilder,
            [$this->getPropertyFactory()->providePropertyIdentifier() => $payload],
            $this->getCurrentRequestLanguage()
        );

        $publishResult = $this->getContentRepository()->update($payloadBuilder->getArrayCopy());
        $content = new Content($publishResult['content']);
        $serializedContent = $this->getPropertyFactory()->serializeValue($content, $this->getCurrentRequestLanguage());
        foreach ($serializedContent as $item){
            if ($item['filename'] == $currentPayload['filename']){
                $result->variables = $item;
            }
        }

        return $result;
    }
}