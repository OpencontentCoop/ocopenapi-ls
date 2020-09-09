{if $load_page}
<html>
<head>
    <title>API Doc</title>
</head>
<body>
{/if}
<style type="text/css">
    @import url({"stylesheets/swagger-ui.css"|ezdesign});
</style>
<div id="swagger-ui" style="min-height: 100px"></div>
<script src={'javascript/swagger-ui-bundle.js'|ezdesign}></script>
<script>
    window.onload = function () {ldelim}
        window.ui = SwaggerUIBundle({ldelim}
            url: {"/openapi.json"|ezurl(yes,full)},
            dom_id: '#swagger-ui',
            docExpansion: "none",
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIBundle.SwaggerUIStandalonePreset
            ]
            {rdelim});
        {rdelim}
</script>
{if $load_page}
</body>
</html>
{/if}