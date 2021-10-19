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

Session::checkRight('plugin_glpiinventory_task', READ);

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

$pfTimeslotEntry = new PluginGlpiinventoryTimeslotEntry();
foreach ($_POST as $key=>$value) {
   if (strstr($key, 'purge-')) {
      $split = explode('-', $key);
      $_POST['id'] = $split[1];
      $pfTimeslotEntry->check($_POST['id'], PURGE);
      $pfTimeslotEntry->delete($_POST, 1);
      Html::back();
   }
}

$pfTimeslotEntry = new PluginGlpiinventoryTimeslotEntry();

$pfTimeslotEntry->addEntry($_POST);

Html::back();
