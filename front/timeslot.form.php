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

$pfTimeslot = new PluginGlpiinventoryTimeslot();
//Add a new timeslot
if (isset($_POST["add"])) {
   $pfTimeslot->check(-1, CREATE, $_POST);
   if ($newID = $pfTimeslot->add($_POST)) {
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($pfTimeslot->getFormURL()."?id=".$newID);
      }
   }
   Html::back();

   // delete a timeslot
} else if (isset($_POST["delete"])) {
   $pfTimeslot->check($_POST['id'], DELETE);
   $ok = $pfTimeslot->delete($_POST);
   $pfTimeslot->redirectToList();

} else if (isset($_POST["purge"])) {
   $pfTimeslot->check($_POST['id'], PURGE);
   $pfTimeslot->delete($_POST, 1);
   $pfTimeslot->redirectToList();

   //update a timeslot
} else if (isset($_POST["update"])) {
   $pfTimeslot->check($_POST['id'], UPDATE);
   $pfTimeslot->update($_POST);
   Html::back();

} else {//print timeslot information
   Html::header(PluginGlpiinventoryTimeslot::getTypeName(2),
                $_SERVER['PHP_SELF'],
                "admin",
                "pluginglpiinventorymenu",
                "timeslot");

   PluginGlpiinventoryMenu::displayMenu("mini");
   $pfTimeslot->display(['id' => $_GET["id"]]);
   Html::footer();
}
