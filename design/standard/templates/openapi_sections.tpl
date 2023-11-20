<div class="container my-5">
    <div class="row">
        <div class="col-12 mb-3">
            <h1>{ezini('SiteSettings', 'SiteName')} Rest API</h1>
            {include uri='design:openapi_intro.tpl'}
        </div>
        {foreach $sections as $identifier => $section}
            <div class="col-12 col-lg-6">
                <div class="card-wrapper card-space">
                    <div class="card card-bg">
                        <div class="card-body">
                            <h3 class="card-title h5">{$section.title|wash()}</h3>
                            <p class="card-text font-serif">{$section.description|wash()}</p>
                            <a class="read-more" href="{concat('/openapi/doc/', $identifier)|ezurl(no)}">
                                <span class="text">{$section.link_label|wash()}</span>
                                {display_icon('it-arrow-right', 'svg', 'icon')}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        {/foreach}
    </div>
</div>