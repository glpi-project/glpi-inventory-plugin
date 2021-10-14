# How to run tests


## Install GLPI tests

```
cd glpi/
php bin/console glpi:database:install --config-dir=tests/config --force
php bin/console glpi:plugin:install --config-dir=tests/config --username=glpi fusioninventory
php bin/console glpi:plugin:activate --config-dir=tests/config fusioninventory
```

## Run FusionInventory tests

```
cd plugins/glpiinventory/
php vendor/bin/phpunit --testdox tests/
```
