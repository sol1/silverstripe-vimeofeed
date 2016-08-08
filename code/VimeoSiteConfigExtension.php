<?php

/**
 * Class VimeoSiteConfigExtension
 *
 * Provides SiteConfig with properties to facilitate Vimeo feed retrieval
 */
class VimeoSiteConfigExtension extends DataExtension
{

    /**
     * @var array
     */
    private static $db = array(
        'VimeoFeed_Username' => 'Varchar(255)',
        'VimeoFeed_ClientID' => 'Varchar(255)',
        'VimeoFeed_ClientSecret' => 'Varchar(255)',
        'VimeoFeed_AppToken' => 'Varchar(255)',
        'VimeoFeed_LastSaved' => 'SS_Datetime',
        'VimeoFeed_AutoUpdate' => 'Boolean',
        'VimeoFeed_UpdateIntervalUnit' => "Enum('Minutes,Hours,Days', 'Minutes')",
        'VimeoFeed_UpdateInterval' => 'Int'
    );

    /**
     * @var array
     */
    private static $defaults = array(
        'VimeoFeed_AutoUpdate' => false,
        'VimeoFeed_UpdateIntervalUnit' => 'Hours',
        'VimeoFeed_UpdateInterval' => '6'
    );

    /**
     * If the ClientID or ClientSecret has changed, remove the access token because it's no longer valid
     */
    public function onBeforeWrite()
    {
        if (isset($this->owner->getChangedFields()['VimeoFeed_ClientID']) || isset($this->owner->getChangedFields()['VimeoFeed_ClientSecret'])) {
            $this->owner->VimeoFeed_Token = null;
        }
    }

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Vimeo',
            new TextField(
                'VimeoFeed_Username',
                'Vimeo username'
            )
        );
        $fields->addFieldToTab('Root.Vimeo',
            new TextField(
                'VimeoFeed_ClientID',
                'Application ID'
            )
        );
        $fields->addFieldToTab('Root.Vimeo',
            new TextField(
                'VimeoFeed_ClientSecret',
                'Application Secret'
            )
        );

        if ($this->owner->VimeoFeed_ClientID && $this->owner->VimeoFeed_ClientSecret) {
            if(!$this->owner->VimeoFeed_AppToken){
                //set the application token
                $lib = new \Vimeo\Vimeo($this->owner->VimeoFeed_ClientID,
                                        $this->owner->VimeoFeed_ClientSecret);
                $token = $lib->clientCredentials('public')['body']['access_token'];
                $this->owner->VimeoFeed_AppToken = $token;

                //also store in database, so it doesn't update everytime this page is renewed
                $configRow = DataObject::get_by_id('SiteConfig',1);
                if($configRow) {
                    $configRow->VimeoFeed_AppToken = $token;
                    $configRow->write();
                }
            }
            $fields->addFieldToTab('Root.Vimeo',
                new ReadonlyField(
                    'VimeoFeed_AppToken',
                    'Application Token'
                )
            );
            $service = new VimeoFeed();
            if ($service->getIsAuthenticated()) {
                //  We have a valid access token
                $fields->addFieldToTab('Root.Vimeo',
                    new LiteralField(
                        'VimeoTabHeading',
                        "<h2>Vimeo has an active connection.</h2>"
                    )
                );
                $fields->addFieldToTab('Root.Vimeo',
                    new CheckboxField(
                        'VimeoFeed_AutoUpdate',
                        'Automatically fetch Vimeo video information'
                    )
                );

                if ($this->owner->VimeoFeed_AutoUpdate) {
                    $fields->addFieldToTab('Root.Vimeo',
                        new NumericField(
                            'VimeoFeed_UpdateInterval',
                            'Update interval'
                        )
                    );
                    $fields->addFieldToTab('Root.Vimeo',
                        $updateIntervalUnitsField = new DropdownField(
                            'VimeoFeed_UpdateIntervalUnit',
                            '&nbsp;',
                            singleton('SiteConfig')->dbObject('VimeoFeed_UpdateIntervalUnit')->enumValues()
                        )
                    );
                    $updateIntervalUnitsField->setRightTitle('This time period defines the minimum length of time between each request to Vimeo to check for new or updated videos.');
                }
            } else {
                // Vimeo isn't connected -- provide auth link
                $serviceURL = $service->getAuthURL();
                $fields->addFieldToTab('Root.Vimeo',
                    new LiteralField('area', '<a href="' . $serviceURL . '" name="action_AuthenticateVimeo" value="Authenticate Vimeo Application" class="action ss-ui-button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" id="Form_EditForm_action_AuthenticateVimeo" role="button" aria-disabled="false"><span class="ui-button-text">
		Authenticate Vimeo Application
	</span></a>')
                );
            }
        }
    }
}
