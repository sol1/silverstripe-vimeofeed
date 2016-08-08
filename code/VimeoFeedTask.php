<?php

/**
 * Class VimeoFeedTask
 *
 * Provides a method of forcing a VimeoFeed update via CLI (intended for cronjobs)
 *      framework/sake VimeoFeedTask flush=all
 */
class VimeoFeedTask extends CliController
{

    /**
     * Force the auto update when called through CLI
     */
    public function process()
    {
        $service = new VimeoFeed();
        $service->doAutoUpdate(true);
    }
}
