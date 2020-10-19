<?php

namespace Opencontent\OpenApi\OperationFactory\MultiBinary;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactory\ChildEndpointFactoryInterface;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\Exceptions\InvalidPayloadException;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\Opendata\Api\Values\Content;

class CreateOperationFactory extends OperationFactory\CreateOperationFactory
{
    use OperationFactory\ChildOperationFactoryTrait;

    public function getSummary()
    {
        return \ezpI18n::tr('ocopenapi', 'Add a binary file in the %name field', null, ['%name' => $this->getPropertyFactory()->providePropertyIdentifier()]);
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
        $currentPayload = $this->getCurrentPayload();
        $alreadyFilenames = array_column($payload, 'filename');
        if (in_array($currentPayload['filename'], $alreadyFilenames)){
            throw new InvalidPayloadException($this->getPropertyFactory()->providePropertyIdentifier(), "Filename {$currentPayload['filename']} already exists");
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
        $result->variables = array_pop($serializedContent);

        return $result;
    }
}