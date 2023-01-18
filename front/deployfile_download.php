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

use org\bovigo\vfs\FilenameTestCase;

include("../../../inc/includes.php");

Session::checkRight('plugin_glpiinventory_package', READ);
global $CFG_GLPI;

$filename = null;
if (isset($_GET['filename'])) {
    $filename = $_GET['filename'];
}

$mimetype = null;
if (isset($_GET['mimetype'])) {
    $mimetype = urldecode($_GET['mimetype']);
}

if (isset($_GET['sha512'])) {
   $deploy = new PluginGlpiinventoryDeployFile();
   if($deploy->checkPresenceFile($_GET['sha512'])) {
      $path = $deploy->getFilePath($_GET['sha512']);
      $deploy->constructFileToTmp($path, $filename);
   } else {
      Html::displayErrorAndDie(__('File not found'), true); // Not found
   }
}

if ($mimetype != null && $filename != null && $path != null) {
   Toolbox::sendFile(GLPI_TMP_DIR . "/" . $filename, $filename, $mimetype);
} else {
   Html::displayErrorAndDie(__('Unauthorized access to this file'), true);
}
