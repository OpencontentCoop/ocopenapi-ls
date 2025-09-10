<div class="container my-5">
    <div class="row">
        <div class="col-12 mb-3">
            <h1>{ezini('SiteSettings', 'SiteName')} Rest API</h1>
            {include uri='design:openapi_intro.tpl'}
        </div>
        {foreach $sections as $identifier => $section}
            <div class="col-12 col-lg-6">
                <div class="card-wrapper card-space">
                    <div class="card card-bg card-big no-after">
                        <div class="card-body">
                            <h3 class="card-title h5">{if is_set($section.info_tag)}<span class="badge bg-primary">{$section.info_tag|wash()}</span> {/if}{$section.title|wash()}</h3>
                            <p class="card-text font-serif" style="min-height: 50px;">{$section.description|wash()}</p>
                            <div class="it-card-footer">
                                <a class="btn btn-outline-primary btn-xs"
                                   href="{concat('/openapi/doc/', $identifier)|ezurl(no)}">{$section.link_label|wash()}</a>
                                {if is_set($section.info_tag)}
                                    <a class="btn btn-link" href="#"
                                       data-copy2clipboard="{concat('/openapi/audience/', $identifier)|ezurl(no, full)}"
                                       title="{concat('/openapi/audience/', $identifier)|ezurl(no, full)}" >
                                        <i class="fa fa-info-circle"></i> <code>AUDIENCE</code></span></a>
                                {/if}
                                <a class="btn btn-link"
                                   href="{concat('/openapi.yml/?section=', $identifier)|ezurl(no)}">
                                    <i class="fa fa-download"></i> <code>openapi.yaml</code></span></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        {/foreach}
    </div>
</div>

{literal}
    <script>
      $(document).ready(function () {
        $('[data-copy2clipboard]')
          .hover(
            function () {
              $(this).find('i').removeClass('fa-info-circle').addClass('fa-copy');
            }, function () {
              $(this).find('i').removeClass('fa-copy').addClass('fa-info-circle');
            }
          )
          .on('click', function () {
            try {
              navigator.clipboard.writeText($(this).data('copy2clipboard'));
              $(this).find('i').removeClass('fa-copy').addClass('fa-check');
            } catch (err) {
              console.error('Failed to copy: ', err);
            }
          })
      })
    </script>
{/literal}