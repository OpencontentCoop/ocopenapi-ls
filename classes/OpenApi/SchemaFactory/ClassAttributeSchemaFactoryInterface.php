<?php

namespace Opencontent\OpenApi\SchemaFactory;

interface ClassAttributeSchemaFactoryInterface
{
    /**
     * @return integer
     */
    public function getClassAttributeId();

    /**
     * @return \eZContentClassAttribute
     */
    public function getClassAttribute();

    /**
     * @return \eZContentClass
     */
    public function getClass();
}