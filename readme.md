# how do install?
the whole app uses 3 (sub)domains to run, 1 for the api, 1 for the static image files, and last for the front-end app.

This api will only cover 2 of the domains `api.boring.host` and `i.boring.host`

# installation
installation goes like every other laravel app.
- clone this repo
- run `composer install`
- fill in your info in the .env file.
- run `php artisan migrate`

# configure the domains.
Make sure the `api.domain.com` is pointing to the `/public` folder of the Laravel installation.
The `i.domain.com` should be pointed to `/storage/images` folder of the Laravel install. This is where all the images and thumbnails will be stored.

# Api usage
coming soon...

but for now send post requests to `api.domain.com/api/upload` with a file param containing the image

upgrade procedure:
1. php artisan down
2. git pull
3. php artisan migrate
4. php artisan:migrate_files
5. set up the new cdn
6. php artisan up