# Fairest of Them All

Mirror Mirror is a self-hosted image uploading platform that complies with the [Tweetbot Custom Media Upload specification](http://tapbots.net/tweetbot/custom_media/). Mirror Mirror passes the actual media itself to [Amazon S3](http://aws.amazon.com/s3/). Mirror Mirror also retains a local database of uploaded images along with respective view counts.

To use, after setting up Mirror Mirror on your server, simply point your Tweetbot Custom Media Upload specification–compliant image-uploading client to `[wherever you uploaded Mirror Mirror]/?key=[the random alphanumeric key you set in creds.php]` (e.g. `http://m2.z17.me/?key=5thhg7dk90`).

## Yellow Brick Roadmap

1. Track/log errors better.
2. Add an install script to reduce effort required to install Mirror Mirror onto a server.
3. Look into displaying videos on a custom page as well instead of linking directly to the file.

### Completed Roadmap Items

1. Keep a local database of uploaded media and their respective view counts.
2. Improve handling of view requests from applications (e.g Tweetbot) vs browsers (e.g. Chrome).
3. Track views from applications and browsers separately.
4. Style the image viewing page.

## Fade to: Black. Roll Credits.

Mirror Mirror’s code was originally based on [this](http://net.tutsplus.com/tutorials/php/how-to-use-amazon-s3-php-to-dynamically-store-and-manage-files-with-ease/) and [this](http://www.macstories.net/news/tweetbot-for-mac-review/#customuploads) and uses Donovan Schönknecht’s [amazon-s3-php-class](https://github.com/tpyo/amazon-s3-php-class).

________

### Mirror Mirror: Legacy

For a version of Mirror Mirror before it used a database, visit the [`no_db` branch](https://github.com/Zyber17/MirrorMirror/tree/no_db).