<?php

namespace Opencontent\OpenApi\SchemaFactory;

use Opencontent\Opendata\Api\Values\Content;
use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;

class ContentClassAttributePropertyFactory
{
    protected $class;

    protected $attribute;

    public function __construct(\eZContentClass $class, \eZContentClassAttribute $attribute)
    {
        $this->class = $class;
        $this->attribute = $attribute;
    }

    public function providePropertyIdentifier()
    {
        return $this->attribute->attribute('identifier');
    }

    /**
     * @return array
     */
    public function provideProperties()
    {
        return [
            "type" => "string",
            "description" => $this->getPropertyDescription()
        ];
    }

    protected function getPropertyDescription()
    {
        $description = $this->attribute->attribute('name');
        $extra = trim($this->attribute->attribute('description'));
        if (!empty($extra)){
            $description = trim($description, ' :') . ': ' . lcfirst($extra);
        }

        return $description;
    }

    public function isRequired()
    {
        return (bool)$this->attribute->attribute('is_required');
    }

    /**
     * @return \eZContentClass
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return \eZContentClassAttribute
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    public function serializeValue(Content $content, $locale)
    {
        return $this->getContent($content, $locale);
    }

    protected function getContent(Content $content, $locale)
    {
        $identifier = $this->attribute->attribute('identifier');
        if (isset($content->data[$locale][$identifier])) {
            return $content->data[$locale][$identifier]['content'];
        }

        return null;
    }

    public function serializePayload(PayloadBuilder $payloadBuilder, array $payload, $locale)
    {
        if (isset($payload[$this->providePropertyIdentifier()])){
            $value = $payload[$this->providePropertyIdentifier()];
            $payloadBuilder->setData(
                $locale,
                $this->attribute->attribute('identifier'),
                is_scalar($value) ? (string)$value : $value
            );
        }
    }
}