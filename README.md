## Participant

- Salimou

## Installation

```bash
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
symfony serve -d
```

La connexion à la base se configure dans `.env.local` :

```
DATABASE_URL="mysql://root@127.0.0.1:3306/ecommerce?serverVersion=8.4.7"
```
