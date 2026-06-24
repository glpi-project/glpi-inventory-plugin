# How to run tests

## Install GLPI and plugin

```
cd glpi/
GLPI_ENVIRONMENT_TYPE="testing" php bin/console database:install --force
GLPI_ENVIRONMENT_TYPE="testing" php bin/console plugin:install --username=glpi glpiinventory
GLPI_ENVIRONMENT_TYPE="testing" php bin/console plugin:activate glpiinventory
```

## Run plugin tests

```
cd plugins/glpiinventory/
php vendor/bin/phpunit --testdox tests/
```
