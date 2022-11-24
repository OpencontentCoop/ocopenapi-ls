<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;
use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\Opendata\Api\Values\Content;
use eZPriceType;

class PriceFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        $schema = parent::provideProperties();
        $schema['type'] = 'number';
        $schema['format'] = 'float';

        return $schema;
    }

    public function serializeValue(Content $content, $locale)
    {
        $price = null;
        $content = $this->getContent($content, $locale);
        if (isset($content['value'])) {
            $price = (float)$content['value'];
        }

        return $price;
    }

    public function serializePayload(PayloadBuilder $payloadBuilder, array $payload, $locale)
    {
        if (isset($payload[$this->providePropertyIdentifier()])){
            $value = [
                'value' => $payload[$this->providePropertyIdentifier()],
                'vat_id' => $this->attribute->attribute( eZPriceType::INCLUDE_VAT_FIELD ),
                'is_vat_included' => $this->attribute->attribute( eZPriceType::VAT_ID_FIELD )
            ];
            $payloadBuilder->setData(
                $locale,
                $this->attribute->attribute('identifier'),
                $value
            );
        }
    }

}