<?php /*

[Cache]
CacheItems[]=openapi

[Cache_openapi]
name=OpenApi cache
id=openapi
tags[]=content
path=openapi
isClustered=true
class=\Opencontent\OpenApi\Loader

[RoleSettings]
PolicyOmitList[]=openapi.json
PolicyOmitList[]=openapi.yml
PolicyOmitList[]=openapi/doc
PolicyOmitList[]=openapi/terms
PolicyOmitList[]=schemas
*/ ?>
