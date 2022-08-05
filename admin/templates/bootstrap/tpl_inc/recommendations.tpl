<div class="col-md-5 pr-0 pr-md-4">
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th colspan="2">{__('weRecommend')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $recommendations->getRecommendations() as $recommendation}
                            <tr>
                                <td>
                                    <img src="{$recommendation->getPreviewImage()}"
                                         style="max-width: 120px;"
                                         alt="{$recommendation->getTitle()}" loading="lazy">
                                </td>
                                <td>
                                    <p class="mb-1">{$recommendation->getTeaser()}</p>
                                    <a href="premiumplugin.php?scope={$recommendations->getScope()}&id={$recommendation->getId()}"
                                       class="btn btn-outline-primary">
                                        {__('getToKnowMore')}
                                        <span class="fal fa-long-arrow-right ml-1"></span>
                                    </a>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
            <div class="row">
                <div class="col-auto mx-auto">
                    <a href="{__('extensionStoreURL')}" class="btn btn-primary my-3" target="_blank">
                        <i class="fas fa-puzzle-piece"></i>
                        {__('btnAdditionalExtensionStore')}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>