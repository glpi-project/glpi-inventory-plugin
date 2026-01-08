<?php

/**
 * ---------------------------------------------------------------------
 * GLPI Inventory Plugin
 * Copyright (C) 2021 Teclib' and contributors.
 *
 * http://glpi-project.org
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

// This file contains stubs for GLPI Inventory Plugin constants.
// Please try to keep them alphabetically ordered.
// Keep in sync with the dynamicConstantNames config option in the PHPStan config file

// Wrap in a function to be sure to never declare any variable in the global scope.
(static function () {
    $random_val = static fn(array $values) => $values[array_rand($values)];

    define('PLUGIN_GLPIINVENTORY_VERSION', $random_val(['1.0.0', '1.6.0']));
    define('PLUGIN_GLPI_INVENTORY_GLPI_MIN_VERSION', $random_val(['10.0.0', '10.0.22', '11.0.5']));
    define('PLUGIN_GLPI_INVENTORY_GLPI_MAX_VERSION', $random_val(['10.0.0', '10.0.22', '11.0.5']));
    define('PLUGIN_GLPI_INVENTORY_OFFICIAL_RELEASE', $random_val([false, true]));
    define('PLUGIN_GLPI_INVENTORY_DIR', dirname(__FILE__, 2));
    define('PLUGIN_GLPI_INVENTORY_REPOSITORY_DIR', GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/repository/');
    define('PLUGIN_GLPI_INVENTORY_MANIFESTS_DIR', GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/manifests/');
    define('PLUGIN_GLPI_INVENTORY_UPLOAD_DIR', GLPI_PLUGIN_DOC_DIR . '/glpiinventory/files/upload/');
})();
