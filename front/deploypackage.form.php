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

$package = new PluginGlpiinventoryDeployPackage();
if (isset($_POST['update_json'])) {
   $json_clean = stripcslashes($_POST['json']);

   $json = json_decode($json_clean, true);

   $ret = PluginGlpiinventoryDeployPackage::updateOrderJson($_POST['packages_id'], $json);
   Html::back();
   exit;
} else if (isset($_POST['add_item'])) {
   $data = array_map(['Toolbox', 'stripslashes_deep'],
                     $package->escapeText($_POST));
   PluginGlpiinventoryDeployPackage::alterJSON('add_item', $data);
   Html::back();
} else if (isset($_POST['save_item'])) {
   $data = array_map(['Toolbox', 'stripslashes_deep'],
                     $package->escapeText($_POST));
   PluginGlpiinventoryDeployPackage::alterJSON('save_item', $data);
   Html::back();
} else if (isset($_POST['remove_item'])) {
   $data = array_map(['Toolbox', 'stripslashes_deep'],
                     $package->escapeText($_POST));
   PluginGlpiinventoryDeployPackage::alterJSON('remove_item', $data);
   Html::back();
}

//$data = Toolbox::stripslashes_deep($_POST);
$data = $_POST;

//general form
if (isset ($data["add"])) {
   Session::checkRight('plugin_glpiinventory_package', CREATE);
   $newID = $package->add($data);
   Html::redirect(Toolbox::getItemTypeFormURL('PluginGlpiinventoryDeployPackage')."?id=".$newID);
} else if (isset ($data["update"])) {
   Session::checkRight('plugin_glpiinventory_package', UPDATE);
   $package->update($data);
   Html::back();
} else if (isset ($data["purge"])) {
   Session::checkRight('plugin_glpiinventory_package', PURGE);
   $package->delete($data, 1);
   $package->redirectToList();
} else if (isset($_POST["addvisibility"])) {
   if (isset($_POST["_type"]) && !empty($_POST["_type"])
           && isset($_POST["plugin_glpiinventory_deploypackages_id"])
           && $_POST["plugin_glpiinventory_deploypackages_id"]) {
      $item = null;
      switch ($_POST["_type"]) {
         case 'User' :
            if (isset($_POST['users_id']) && $_POST['users_id']) {
               $item = new PluginGlpiinventoryDeployPackage_User();
            }
            break;

         case 'Group' :
            if (isset($_POST['groups_id']) && $_POST['groups_id']) {
               $item = new PluginGlpiinventoryDeployPackage_Group();
            }
            break;

         case 'Profile' :
            if (isset($_POST['profiles_id']) && $_POST['profiles_id']) {
               $item = new PluginGlpiinventoryDeployPackage_Profile();
            }
            break;

         case 'Entity' :
            $item = new PluginGlpiinventoryDeployPackage_Entity();
            break;
      }
      if (!is_null($item)) {
         $item->add($_POST);
         //         Event::log($_POST["plugin_glpiinventory_deploypackages_id"], "sla", 4, "tools",
         //                    //TRANS: %s is the user login
         //                    sprintf(__('%s adds a target'), $_SESSION["glpiname"]));
      }
   }
   Html::back();
}

Html::header(__('GLPI Inventory DEPLOY'), $_SERVER["PHP_SELF"], "admin",
   "pluginglpiinventorymenu", "deploypackage");
PluginGlpiinventoryMenu::displayMenu("mini");
$id = "";
if (isset($_GET["id"])) {
   $id = $_GET["id"];
}
$package->display($_GET);
Html::footer();

