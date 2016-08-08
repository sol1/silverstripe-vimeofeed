<?php

/**
 * Class VimeoFeed
 *
 * Provides Vimeo user profile access
 */
class VimeoFeed extends Controller
{
    /**
     * @var Vimeo
     */
    private $lib;

    /**
     * @var string
     */
    private $stateSessionIdentifier = 'VimeoFeed_State';

    /**
     * Instantiate the Vimeo API and feed provided config values
     * We require a long-lived access token
     */
    public function __construct()
    {
        parent::__construct();

        $siteConfig = SiteConfig::current_site_config();
        $clientID = $siteConfig->VimeoFeed_ClientID;
        $clientSecret = $siteConfig->VimeoFeed_ClientSecret;

        $this->lib;

        $this->lib = new \Vimeo\Vimeo($this->appID, $this->appSecret);
        $this->lib->setToken($siteConfig->VimeoFeed_AppToken);

        if ($clientID && $clientSecret) {
            if ($accessToken = $this->getConfigToken()) {
                $this->lib->setToken($accessToken);
            }
        }
    }

    /**
     * Returns true if the user has a valid access token
     *
     * @return string
     */
    public function getIsAuthenticated()
    {
        return $this->lib->getToken();
    }

    /**
     * Checks the connected Vimeo account for new uploads, and calls processVideo() on each one.
     * Returns an array containing up to $limit VimeoVideo objects
     *
     * @param $limit Int number of results to retrieve
     * @return array
     */
    public function getRecentUploads($limit = 50)
    {
        if ($this->getIsAuthenticated()) {
            //get latest videos
            $response = $this->lib->request('/users/' . $siteConfig->VimeoFeed_Username . '/videos', array('per_page' => $limit, 'sort' => 'date', 'direction' => 'desc'), 'GET');
            $uploads = array();

            //loop through checking if results already exist in DB
            foreach ($response['body']['data'] as $video) {
                $videoObject = $this->processVideo($video);
                array_push($uploads, $videoObject);
            }

            return isset($uploads) ? $uploads : false;
        }

        return false;
    }

    /**
     * Checks the connected Vimeo account for video uploads, and calls processVideo() on each one.
     *
     * @return boolean False on failure, true otherwise
     */
    public function getAllUploads($limit_per_page = 50)
    {
        $count = 0;
        //if user authenticated get all videos from channel
        if ($this->getIsAuthenticated()) {
            $response = $this->lib->request('/users/' . $siteConfig->VimeoFeed_Username . '/videos', array('per_page' => $limit_per_page, 'sort' => 'date', 'direction' => 'desc'), 'GET');
            //loop through all pages
            for ($i = 1; ($i - 1) * $limit_per_page < $response['body']['total']; $i++){
                //loop through each video returned from the page
                foreach ($response['body']['data'] as $video) {
                    $videoObject = $this->processVideo($video);
                    echo $count++ . " " . $video['name'] . "<br />";
                }
                $response = $this->lib->request('/users/' . $siteConfig->VimeoFeed_Username . '/videos', array('page'=> $i + 1, 'per_page' => $limit_per_page, 'sort' => 'date', 'direction' => 'desc'), 'GET');
            }
        }else {
            echo "Not authenticated";
        }

        return false;
    }

    /**
     * Saves data from Vimeo API request into VimeoVideo
     * Overwrites an existing object, or creates a new one.
     * Returns the VimeoVideo DataObject.
     *
     * @param $video
     * @return VimeoVideo
     */
    protected function processVideo($video)
    {
        $videoFields = array();

        // Map response data to columns in our VimeoVideo table
        $videoFields['VideoID'] = str_replace('/videos/', "", $video['uri']);
        $videoFields['Description'] = $video['description'];
        $videoFields['Published'] = strtotime($video['release_time']);
        $videoFields['Title'] = $video['name'];
        $videoFields['ThumbnailURL'] = $video['pictures']['sizes'][3]['link'];
        $videoFields['EmbedHTML'] = $video['embed']['html'];

        // Try retrieve existing VimeoVideo by Youtube Video ID, create if it doesn't exist
        $videoObject = VimeoVideo::getExisting($videoFields['VideoID']);

        if (!$videoObject) {
            $videoObject = new VimeoVideo();
            $newVimeoVideo = true;
        }

        $videoObject->update($videoFields);
        $videoObject->write();

        if (isset($newVimeoVideo)) {
            // Allow decoration of VimeoVideo with onAfterCreate(VimeoVideo $videoObject) method
            $this->extend('onAfterCreate', $videoObject);
        }

        return $videoObject;
    }

    /**
     * Returns the access token from SiteConfig
     *
     * @return mixed
     */
    protected function getConfigToken()
    {
        return SiteConfig::current_site_config()->VimeoFeed_Token;
    }

    /**
     * Saves the access token into SiteConfig
     *
     * @return void
     */
    protected function setConfigToken($token)
    {
        $siteConfig = SiteConfig::current_site_config();
        $siteConfig->VimeoFeed_Token = $token;
        $siteConfig->write();
    }

    /**
     * Returns the SS_Datetime a VimeoVideo was last retrieved from the external service
     *
     * @return SS_Datetime
     */
    protected function getTimeLastSaved()
    {
        return SiteConfig::current_site_config()->VimeoFeed_LastSaved;
    }

    /**
     * Checks if it's time to do a video update, or performs one anyway if $force is true
     *
     * @param bool $force Force auto update, disregards 'VimeoFeed_AutoUpdate' property
     * @throws ValidationException
     * @throws null
     */
    public function doAutoUpdate($force = false)
    {
        $siteConfig = SiteConfig::current_site_config();

        if ($force || $siteConfig->VimeoFeed_AutoUpdate) {
            $lastUpdated = $siteConfig->VimeoFeed_LastSaved;
            $nextUpdateInterval = $siteConfig->VimeoFeed_UpdateInterval;
            $nextUpdateIntervalUnit = $siteConfig->VimeoFeed_UpdateIntervalUnit;

            if ($lastUpdated) {
                // Assemble the time another update became required as per SiteConfig options
                // VimeoFeed_NextUpdateInterval & ..Unit
                $minimumUpdateTime = strtotime($lastUpdated . ' +' . $nextUpdateInterval . ' ' . $nextUpdateIntervalUnit);
            }

            // If we haven't auto-updated before (fresh install), or an update is due, do update
            if ($force || !isset($minimumUpdateTime) || $minimumUpdateTime < time()) {
                $this->getRecentUploads();

                // Save the time the update was performed
                $siteConfig->VimeoFeed_LastSaved = SS_Datetime::now()->value;
                $siteConfig->write();
            }
        }
    }

    /**
     * Import all available Vimeo videos, using API pagination. Use this as a once-off if you have more than 50 videos.
     *
     * @throws ValidationException
     * @throws null
     */
    public function importAll($force = false)
    {
        $siteConfig = SiteConfig::current_site_config();

        $this->getAllUploads();

        // Save the time the update was performed
        $siteConfig->VimeoFeed_LastSaved = SS_Datetime::now()->value;
        $siteConfig->write();
    }
}
