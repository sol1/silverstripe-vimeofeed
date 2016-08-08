<?php

/**
 * Class ContentControllerExtension
 *
 * Updates VimeoVideo objects if the time period between updates has elapsed
 */
class VimeoFeedContentControllerExtension extends DataExtension
{

    /**
     * Perform the auto update
     */
    public function onAfterInit()
    {
        $service = new VimeoFeed();
        $service->doAutoUpdate();
    }
}
