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

$group = new PluginGlpiinventoryDeployGroup();

if (isset($_GET['plugin_glpiinventory_deploygroups_id'])) {
    $_SESSION['glpisearch']['PluginGlpiinventoryComputer'] = $_GET;
}

if (isset($_GET['save'])) {
   $group_item = new PluginGlpiinventoryDeployGroup_Dynamicdata();
   if (!countElementsInTable($group_item->getTable(),
                             ['plugin_glpiinventory_deploygroups_id' => $_GET['id']])) {
      $criteria  = ['criteria'     => $_GET['criteria'],
                         'metacriteria' => $_GET['metacriteria']];
      $values['fields_array'] = serialize($criteria);
      $values['plugin_glpiinventory_deploygroups_id'] = $_GET['id'];
      $group_item->add($values);
   } else {
      $item = getAllDataFromTable($group_item->getTable(),
                                   ['plugin_glpiinventory_deploygroups_id' => $_GET['id']]);
      $values                 = array_pop($item);

      $criteria = ['criteria'     => $_GET['criteria'],
                        'metacriteria' => $_GET['metacriteria']];
      $values['fields_array'] = serialize($criteria);
      $group_item->update($values);
   }

   Html::redirect(Toolbox::getItemTypeFormURL("PluginGlpiinventoryDeployGroup")."?id=".$_GET['id']);
} else if (isset($_FILES['importcsvfile'])) {
   PluginGlpiinventoryDeployGroup_Staticdata::csvImport($_POST, $_FILES);
   Html::back();
} else if (isset($_POST["add"])) {
   $group->check(-1, UPDATE, $_POST);
   $newID = $group->add($_POST);
   Html::redirect(Toolbox::getItemTypeFormURL("PluginGlpiinventoryDeployGroup")."?id=".$newID);

} else if (isset($_POST["delete"])) {
   //   $group->check($_POST['id'], DELETE);
   $ok = $group->delete($_POST);

   $group->redirectToList();

} else if (isset($_POST["purge"])) {
   //   $group->check($_POST['id'], DELETE);
   $ok = $group->delete($_REQUEST, 1);

   $group->redirectToList();

} else if (isset($_POST["update"])) {
   $group->check($_POST['id'], UPDATE);
   $group->update($_POST);

   Html::back();
} else {
   Html::header(__('GLPI Inventory DEPLOY'), $_SERVER["PHP_SELF"], "admin",
                "pluginglpiinventorymenu", "deploygroup");

   PluginGlpiinventoryMenu::displayMenu("mini");
   $values       = $_POST;
   if (!isset($_GET['id'])) {
      $id = '';
   } else {
      $id = $_GET['id'];
      if (isset($_GET['sort']) AND isset($_GET['order'])) {
         $group->getFromDB($id);
         PluginGlpiinventoryDeployGroup::getSearchParamsAsAnArray($group, true);
      }
   }
   $values['id'] = $id;
   if (isset($_GET['preview'])) {
      $values['preview'] = $_GET['preview'];
   }
   $group->display($values);
   Html::footer();
}
