# How to run tests

## Install GLPI and plugin

```
cd glpi/
php bin/console glpi:database:install --config-dir=tests/config --force
php bin/console glpi:plugin:install --config-dir=tests/config --username=glpi glpiinventory
php bin/console glpi:plugin:activate --config-dir=tests/config glpiinventory
```

## Run plugin tests

```
cd plugins/glpiinventory/
php vendor/bin/phpunit --testdox tests/
```
