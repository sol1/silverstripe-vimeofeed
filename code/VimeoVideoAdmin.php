<?php

/**
 * Class VimeoVideoAdmin
 */
class VimeoVideoAdmin extends ModelAdmin
{
    /**
     * @var array
     */
    private static $managed_models = array(
        'VimeoVideo'
    );

    /**
     * @var string
     */
    private static $url_segment = 'vimeo-videos';

    /**
     * @var string
     */
    private static $menu_title = 'Vimeo Videos';
}
