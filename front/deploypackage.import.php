<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include ("../../../inc/includes.php");

Session::checkLoginUser();

Session::checkRight('plugin_glpiinventory_package', CREATE);

Html::header(__('GLPI Inventory DEPLOY'), $_SERVER["PHP_SELF"], "admin",
   "pluginglpiinventorymenu", "deploypackage");

$package = new PluginGlpiinventoryDeployPackage();
if (isset($_POST['importfile'])) {
   $package->importPackage($_POST['importfile']);
   Html::back();
}

PluginGlpiinventoryMenu::displayMenu("mini");


$package->listPackagesToImport();

Html::footer();

