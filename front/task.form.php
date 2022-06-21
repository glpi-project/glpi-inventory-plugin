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

include("../../../inc/includes.php");

$pfTask = new PluginGlpiinventoryTask();

//Submit the task form parameters
$pfTask->submitForm($_POST);

Html::header(
    __('GLPI Inventory', 'glpiinventory'),
    $_SERVER["PHP_SELF"],
    "admin",
    "pluginglpiinventorymenu",
    "task"
);

// Manage forcetab : non standard system (file name <> class name)
if (isset($_GET['forcetab'])) {
    Session::setActiveTab('PluginGlpiinventoryTask', $_GET['forcetab']);
    unset($_GET['forcetab']);
}

Session::checkRight('plugin_glpiinventory_task', READ);

PluginGlpiinventoryMenu::displayMenu("mini");

//PluginGlpiinventoryTaskjob::isAllowurlfopen();

//If there is no form to submit, display the form
$pfTask->display($_GET);

Html::footer();
