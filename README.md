# Trustindex – Cégértékelő alkalmazás

Symfony alapú tesztalkalmazás céges vélemények beküldésére, listázására és összesített statisztikák megjelenítésére.

## Fő funkciók

* új vélemény beküldése validációval
* vélemények listázása és részletező oldal
* cégnév szerinti keresés
* lapozás
* cégenkénti véleményszám és átlagos értékelés
* cégnév automatikus kiegészítése
* magyar nyelvű felület
* unit, integration és functional tesztek

## Technológiák

* PHP 8.2+
* Symfony 7.4
* Doctrine ORM és Migrations
* Twig
* Symfony Forms és Validator
* Symfony UX Autocomplete
* PHPUnit
* SQLite

## Telepítés

```bash
git clone git@github.com:positionrelative/trust-rating.git
cd trust-rating
composer install
php bin/console doctrine:migrations:migrate --no-interaction
```

## Futtatás

Symfony CLI használatával:

```bash
symfony serve
```

Symfony CLI nélkül:

```bash
php -S 127.0.0.1:8000 -t public public/index.php
```

Az alkalmazás a `http://127.0.0.1:8000` címen érhető el.

## Tesztek

```bash
APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction
php bin/phpunit
```

## Kódminőség ellenőrzése

```bash
composer validate --strict
php bin/console lint:container
php bin/console lint:twig templates
php bin/console doctrine:schema:validate
vendor/bin/php-cs-fixer fix --dry-run --diff
```

## Főbb technikai döntések

* A cégek külön `Company` entitásként szerepelnek.
* A cégnév-egyezés kis- és nagybetűtől független.
* A statisztikai lekérdezések típusos DTO-t adnak vissza.
* A listázás eager loadingot használ az N+1 probléma elkerülésére.
* A szerző e-mail-címe nem jelenik meg nyilvánosan.

## Munkaidőnapló

| Időszak     | Feladat                                      |         Időtartam |
| ----------- | -------------------------------------------- | ----------------: |
| 09:00–09:20 | Tervezés és projektbeállítás                 |           20 perc |
| 09:20–10:05 | Entitások, migrációk és repositoryk          |           45 perc |
| 10:05–10:55 | Űrlapok, controllerek és nézetek             |           50 perc |
| 10:55–11:26 | Keresés, lapozás, autocomplete és fordítások |           30 perc |
| 11:26–11:46 | Tesztek, javítások és dokumentáció           |           20 perc |
|             | **Összesen**                                 | **2 óra 45 perc** |
