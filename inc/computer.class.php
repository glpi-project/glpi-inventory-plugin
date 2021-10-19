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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Manage the search in groups (static and dynamic).
 */
class PluginGlpiinventoryComputer extends Computer {

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = "plugin_glpiinventory_group";

   function rawSearchOptions() {
      $computer = new Computer();
      $options  = $computer->rawSearchOptions();

      $plugin = new Plugin();
      if ($plugin->isInstalled('fields')) {
         if ($plugin->isActivated('fields')) {
            // Include Fields hook from correct installation folder (marketplace or plugins)
            include_once(Plugin::GetPhpDir("fields") . "/hook.php");
            $options['fields_plugin'] = [
               'id'   => 'fields_plugin',
               'name' => __('Plugin fields')
            ];
            $fieldsoptions =  plugin_fields_getAddSearchOptions('Computer');
            foreach ($fieldsoptions as $id=>$data) {
               $data['id'] = $id;
               $options[$id] = $data;
            }
         }
      }

      return $options;
   }

   /**
    * Get the massive actions for this object
    *
    * @param object|null $checkitem
    * @return array list of actions
    */
   function getSpecificMassiveActions($checkitem = null) {

      $actions = [];
      if (strstr(filter_input(INPUT_SERVER, "PHP_SELF"), '/report.dynamic.php')) {
         // In case we export list (CSV, PDF...) we do not have massive actions.
         return $actions;
      }

      if (isset($_GET['id'])) {
         $id = $_GET['id'];
      } else {
         $id = $_POST['id'];
      }
      $group = new PluginGlpiinventoryDeployGroup();
      $group->getFromDB($id);

      //There's no massive action associated with a dynamic group !
      if ($group->isDynamicGroup() || !$group->canEdit($group->getID())) {
         return [];
      }

      if (!isset($_POST['custom_action'])) {
            $actions['PluginGlpiinventoryComputer'.MassiveAction::CLASS_ACTION_SEPARATOR.'add']
               = _x('button', 'Add to associated items of the group');
            $actions['PluginGlpiinventoryComputer'.MassiveAction::CLASS_ACTION_SEPARATOR.'deleteitem']
               = _x('button', 'Remove from associated items of the group');
      } else {
         if ($_POST['custom_action'] == 'add_to_group') {
            $actions['PluginGlpiinventoryComputer'.MassiveAction::CLASS_ACTION_SEPARATOR.'add']
               = _x('button', 'Add to associated items of the group');
         } else if ($_POST['custom_action'] == 'delete_from_group') {
            $actions['PluginGlpiinventoryComputer'.MassiveAction::CLASS_ACTION_SEPARATOR.'deleteitem']
               = _x('button', 'Remove from associated items of the group');
         }
      }
      return $actions;
   }


   /**
    * Define the standard massive actions to hide for this class
    *
    * @return array list of massive actions to hide
    */
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      $forbidden[] = 'add';
      $forbidden[] = 'delete';
      return $forbidden;
   }


   /**
    * Execution code for massive action
    *
    * @param object $ma MassiveAction instance
    * @param object $item item on which execute the code
    * @param array $ids list of ID on which execute the code
    */
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids) {

      $group_item = new PluginGlpiinventoryDeployGroup_Staticdata();
      switch ($ma->getAction()) {

         case 'add' :
            foreach ($ids as $key) {
               if ($item->can($key, UPDATE)) {
                  if (!countElementsInTable($group_item->getTable(),
                     [
                        'plugin_glpiinventory_deploygroups_id' => $_POST['id'],
                        'itemtype'                               => 'Computer',
                        'items_id'                               => $key,
                     ])) {
                     $group_item->add([
                        'plugin_glpiinventory_deploygroups_id'
                           => $_POST['id'],
                        'itemtype' => 'Computer',
                        'items_id' => $key]);
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                  }
               } else {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               }
            }
            return;

         case 'deleteitem':
            foreach ($ids as $key) {
               if ($group_item->deleteByCriteria(['items_id' => $key,
                                                       'itemtype' => 'Computer',
                                                       'plugin_glpiinventory_deploygroups_id'
                                                          => $_POST['id']])) {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
               } else {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
               }
            }

      }
   }


   /**
    * Display form related to the massive action selected
    *
    * @param object $ma MassiveAction instance
    * @return boolean
    */
   static function showMassiveActionsSubForm(MassiveAction $ma) {
      if ($ma->getAction() == 'add') {
         echo "<br><br>".Html::submit(_x('button', 'Add'),
                                      ['name' => 'massiveaction']);
         return true;
      }
      return parent::showMassiveActionsSubForm($ma);
   }

}
