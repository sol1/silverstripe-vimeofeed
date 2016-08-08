<?php

/**
 * Class VimeoVideo
 *
 * Represents a video on Vimeo
 */
class VimeoVideo extends DataObject
{

    /**
     * @var array
     */
    private static $db = array(
        'Title' => 'Varchar(255)',
        'VideoID' => 'Varchar(255)',
        'VideoURL' => 'Varchar(255)',
        'Description' => 'Text',
        'Published' => 'SS_Datetime',
        'ChannelTitle' => 'Varchar(255)',
        'ChannelID' => 'Varchar(255)',
        'PlaylistID' => 'Varchar(255)',
        'ThumbnailURL' => 'Varchar(255)',
        'PlaylistPosition' => 'Int',
        'EmbedHTML' => 'HTMLText'
    );

    /**
     * @var array
     */
    private static $indexes = array(
        'VideoID' => true
    );

    /**
     * @var array
     */
    private static $summary_fields = array(
        'Title' => 'Title',
        'Description' => 'Description',
        'Published.Nice' => 'Published'
    );

    /**
     * @var string
     */
    private static $default_sort = "Published DESC";

    /**
     * Returns the URL where the video resides on Vimeo
     *
     * @return string
     */
    public function getLink()
    {
        return $this->VideoURL;
    }

    /**
     * Looks up VimeoVideo objects by VideoID, returns the first result
     *
     * @param $videoID
     * @return bool|DataList
     */
    public static function getExisting($videoID)
    {
        $video = VimeoVideo::get()
            ->filter('VideoID', $videoID)
            ->first();

        return $video ? $video : false;
    }
}
