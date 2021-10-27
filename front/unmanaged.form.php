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

$pfUnmanaged = new PluginGlpiinventoryUnmanaged();
$ptt  = new PluginGlpiinventoryTask();

Html::header(__('GLPI Inventory', 'glpiinventory'), $_SERVER["PHP_SELF"],
        "assets", "pluginglpiinventoryunmanaged");


Session::checkRight('plugin_glpiinventory_unmanaged', READ);

PluginGlpiinventoryMenu::displayMenu("mini");

$id = "";
if (isset($_GET["id"])) {
   $id = $_GET["id"];
}
if (isset ($_POST["add"])) {
   Session::checkRight('plugin_glpiinventory_unmanaged', CREATE);
   if (isset($_POST['items_id'])
          && ($_POST['items_id'] != "0") AND ($_POST['items_id'] != "")) {
      $_POST['itemtype'] = '1';
   }
   $pfUnmanaged->add($_POST);
   Html::back();
} else if (isset($_POST["delete"])) {
   Session::checkRight('plugin_glpiinventory_unmanaged', PURGE);

   $pfUnmanaged->check($_POST['id'], DELETE);

   $pfUnmanaged->delete($_POST);

   $pfUnmanaged->redirectToList();
} else if (isset($_POST["restore"])) {

   $pfUnmanaged->check($_POST['id'], DELETE);

   if ($pfUnmanaged->restore($_POST)) {
      Event::log($_POST["id"], "PluginGlpiinventoryUnmanaged", 4, "inventory",
               $_SESSION["glpiname"]." ".__('restoration of the item', 'glpiinventory')." ".
               $pfUnmanaged->getField('name'));
   }
   $pfUnmanaged->redirectToList();

} else if (isset($_POST["purge"]) || isset($_GET["purge"])) {
   Session::checkRight('plugin_glpiinventory_unmanaged', PURGE);

   $pfUnmanaged->check($_POST['id'], PURGE);

   $pfUnmanaged->delete($_POST, 1);
   $pfUnmanaged->redirectToList();
} else if (isset($_POST["update"])) {
   $pfUnmanaged->check($_POST['id'], UPDATE);
   $pfUnmanaged->update($_POST);
   Html::back();
} else if (isset($_POST["import"])) {
   $Import = 0;
   $NoImport = 0;
   list($Import, $NoImport) = $pfUnmanaged->import($_POST['id'], $Import, $NoImport);
    Session::addMessageAfterRedirect(
            __('Number of imported devices', 'glpiinventory')." : ".$Import);
    Session::addMessageAfterRedirect(
            __('Number of devices not imported because type not defined', 'glpiinventory').
            " : ".$NoImport);
   if ($Import == "0") {
      Html::back();
   } else {
      Html::redirect(Plugin::getWebDir('glpiinventory')."/front/unmanaged.php");
   }
}

$pfUnmanaged->display($_GET);

Html::footer();
