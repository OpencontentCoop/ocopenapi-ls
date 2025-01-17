{if $load_page}
<html>
<head>
    <title>API Doc</title>
</head>
<body>
{/if}

{if and($show_section_index)}
    {include uri='design:openapi_sections.tpl'}
{else}
    <style type="text/css">
        @import url({"stylesheets/swagger-ui-5.11.0.css"|ezdesign});
    </style>
<div class="container my-5">
    <div id="swagger-ui" style="min-height: 100px"></div>
    <script src={'javascript/swagger-ui-bundle-5.11.0.js'|ezdesign}></script>
    <script>
        window.onload = function () {ldelim}
            window.ui = SwaggerUIBundle({ldelim}
                url: "{$endpoint_url}",
                dom_id: '#swagger-ui',
                docExpansion: "none",
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.SwaggerUIStandalonePreset
                ]
                {rdelim});
            {rdelim}
    </script>
</div>
{/if}

{if $load_page}
</body>
</html>
{/if}