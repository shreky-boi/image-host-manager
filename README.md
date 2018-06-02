# Image Host Manager

I like to host my own images on S3. I built this simple web app to front my S3 bucket and help manage the image contents.

The app is built in PHP with the [Slim 3 Framework](https://www.slimframework.com/). It uses a handful of php libraries that need to be installed with [composer](https://getcomposer.org/doc/00-intro.md).

[![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy)

## Features

- Preview all (up to 1000) images in your bucket, sorted by most recently modified first.
- Filter the images by keyword (matches on file name).
- Add a new image by url and provide an optional filename.
- Click an image preview to copy the url to your clipboard.
- Update the description and tags meta data of your images.

## Setup

### Install

```bash
$ git clone git@github.com:stevenmaguire/image-host-manager.git
$ cd image-host-manager
$ composer install
```

### Configure

Copy the `.env.example` file to a new `.env` file.

```bash
$ cp .env.example .env
```

Open up the `.env` file in your favorite editor and provide values for the keys.

```
APP_NAME="My App"
IMAGES_BASE_URL="https://my.images.are.served.from.here.com"
USERS='{"user1":"password1","user2":"password2"}'
AWS_ACCESS_KEY="YOUR-AWS-ACCESS-KEY"
AWS_SECRET_KEY="YOUR-AWS-SECRET-KEY"
AWS_S3_BUCKET="YOUR-S3-BUCKET-WHERE-YOUR-IMAGES-LIVE"
AWS_S3_REGION="REGION-WHERE-YOUR-S3-BUCKET-LIVES"
```

The app uses Basic Authentication to protect your content from prying eyes. You can add your own user and password combinations to the JSON string associated with the `USERS` key in the `.env` file.

### Run

```bash
$ composer start
```

Browse to [http://localhost:8080/](http://localhost:8080/) and enter your user and password to begin using the app.


## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Steven Maguire](https://github.com/stevenmaguire)
- [All Contributors](https://github.com/stevenmaguire/image-host-manager/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
