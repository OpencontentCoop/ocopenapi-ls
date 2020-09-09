<?php

namespace Opencontent\OpenApi\OperationFactory\ContentObject;

use Opencontent\Opendata\Api\Structs\PublicationOptions as OriginalPublicationOptions;

class PublicationOptions extends OriginalPublicationOptions implements \JsonSerializable
{
    public function __construct(array $options = array())
    {
        $updateRemoteId = false;
        if (isset($options['update_remote_id'])){
            $updateRemoteId = $options['update_remote_id'];
            unset($options['update_remote_id']);
        }
        parent::__construct($options);
        $this->properties['update_remote_id'] = $updateRemoteId;
    }

    public function jsonSerialize()
    {
        return $this->properties;
    }
}