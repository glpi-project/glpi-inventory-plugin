<?php

/**
 * ---------------------------------------------------------------------
 * GLPI Inventory Plugin
 * Copyright (C) 2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on FusionInventory for GLPI
 * Copyright (C) 2010-2021 by the FusionInventory Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI Inventory Plugin.
 *
 * GLPI Inventory Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * GLPI Inventory Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use Glpi\Cache\CacheManager;
use Glpi\Cache\SimpleCache;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

ini_set('display_errors', 'On');
error_reporting(E_ALL);

define('GLPI_ROOT', __DIR__ . '/../../../');
define('GLPI_CONFIG_DIR', __DIR__ . '/../../../tests/config');
define('GLPI_VAR_DIR', __DIR__ . '/files');
define('GLPI_URI', (getenv('GLPI_URI') ?: 'http://localhost:8088'));
define('GLPI_LOG_DIR', GLPI_VAR_DIR . '/_log');
define(
    'PLUGINS_DIRECTORIES',
    [
      GLPI_ROOT . '/plugins',
      GLPI_ROOT . '/tests/fixtures/plugins',
    ]
);

define('TU_USER', '_test_user');
define('TU_PASS', 'PhpUnit_4');

global $CFG_GLPI, $GLPI_CACHE;

include(GLPI_ROOT . "/inc/based_config.php");

if (!file_exists(GLPI_CONFIG_DIR . '/config_db.php')) {
    die("\nConfiguration file for tests not found\n\nrun: bin/console glpi:database:install --config-dir=tests/config ...\n\n");
}

// Create subdirectories of GLPI_VAR_DIR based on defined constants
foreach (get_defined_constants() as $constant_name => $constant_value) {
    if (
        preg_match('/^GLPI_[\w]+_DIR$/', $constant_name)
        && preg_match('/^' . preg_quote(GLPI_VAR_DIR, '/') . '\//', $constant_value)
    ) {
        is_dir($constant_value) or mkdir($constant_value, 0755, true);
    }
}

//init cache
if (file_exists(GLPI_CONFIG_DIR . DIRECTORY_SEPARATOR . CacheManager::CONFIG_FILENAME)) {
   // Use configured cache for cache tests
    $cache_manager = new CacheManager();
    $GLPI_CACHE = $cache_manager->getCoreCacheInstance();
} else {
   // Use "in-memory" cache for other tests
    $GLPI_CACHE = new SimpleCache(new ArrayAdapter());
}

global $PLUGIN_HOOKS;

include_once GLPI_ROOT . 'inc/includes.php';
include_once GLPI_ROOT . '/plugins/glpiinventory/vendor/autoload.php';
include_once __DIR__ . '/LogTest.php';

// $_SESSION['glpiprofiles'][4]['entities'] = [0 => ['id' => 0, 'is_recursive' => true]];
// $_SESSION['glpidefault_entity'] = 0;
$auth = new Auth();
$user = new User();
$auth->auth_succeded = true;
$user->getFromDB(2);
$auth->user = $user;
Session::init($auth);
Session::initEntityProfiles(2);
Session::changeProfile(4);
plugin_init_glpiinventory();

if (!file_exists(GLPI_LOG_DIR . '/php-errors.log')) {
    file_put_contents(GLPI_LOG_DIR . '/php-errors.log', '');
}

if (!file_exists(GLPI_LOG_DIR . '/sql-errors.log')) {
    file_put_contents(GLPI_LOG_DIR . '/sql-errors.log', '');
}

// Creation of folders if not created in tests
if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory')) {
    mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory');
}
if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/tmp')) {
    mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/tmp');
}
if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/upload')) {
    mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/upload');
}
if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files')) {
    mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files');
}
if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/repository')) {
    mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/repository');
}
if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/manifests')) {
    mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/manifests');
}
if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/import')) {
    mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/import');
}
if (!is_dir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/export')) {
    mkdir(GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/export');
}

// @codingStandardsIgnoreStart
class GlpitestPHPerror extends \Exception
{
}
class GlpitestPHPwarning extends \Exception
{
}
class GlpitestPHPnotice extends \Exception
{
}
class GlpitestSQLError extends \Exception
{
}
// @codingStandardsIgnoreEnd