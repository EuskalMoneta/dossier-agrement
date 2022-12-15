# Dossier agrément


## installation

## Installation with LAMP stack

1. git clone https://.... ./dossier-agrement
2. `cd dossier-agrement`
3. Install composer https://getcomposer.org/download/
4. run `php composer install` to install vendors/dependencies
5. `cp .env .env.local` copy environment file
6. Change username, password and dbname in `.env.local`
   `DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/db_name`
7. Create the database manually or run `php bin/console doctrine:database:create`

8. Create and give -rw rights to the following folders `public/uploads/assets`

## Usefull commands

Migrate the database to the newest version \
`php bin/console doctrine:migrations:migrate`

Check permissions for  var/cache var/log, public/uploads

Faire une mise à jour des assets \
`php bin/console assets:install --symlink`

## Update frais de dossier

Simply modify javascript in the file `adhesion.html.twig` with the new values.