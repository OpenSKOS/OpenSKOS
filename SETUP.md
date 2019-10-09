Build repository

```sh
composer install [--ignore-platform-reqs]
cp application/configs/application.ini.dist application/configs/application.ini
```

TODO: modifications here

Start machines

```sh
docker-compose up
```

Create dataset

- Go to localhost:9001
- Log in with admin:admin (or what you entered when modified)
- Create a dataset ( manage datasets -> add new dataset ) with the name you chose in your application.ini

Initialize first tenant

```sh
docker exec -it openskos-php-fpm php tools/tenant.php --code CODE --name NAME --email EMAIL --password PASSWORD create
```

TODO:

```sh
docker exec -it openskos-php-fpm ./vendor/bin/phing install.dev
```

Login

- Go to localhost:9000/editor/login
- Log in with the email and password just created
