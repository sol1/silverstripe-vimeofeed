# silverstripe-vimeofeed
Ingest Vimeo videos into your SilverStripe instance, based on [silverstripe-youtubefeed](https://github.com/Little-Giant/silverstripe-youtubefeed).

### Installation
- `$ composer require sol1/silverstripe-vimeofeed`

### Configure
- run `dev/build?flush=1`
- Add Vimeo usename, Vimeo API ClientID and Client secret to /admin/settings -> Vimeo tag.

### Usage
Call `VimeoFeed::importAll()` to get all videos associated with a vimeo account. `VimeoFeed::getRecentUploads()` will import the 50 most recent videos.
