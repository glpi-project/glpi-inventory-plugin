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
 * GLPI Inventoruy Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Manage the general configuration tabs (display) in plugin.
 */
class PluginGlpiinventoryConfiguration extends CommonDBTM {

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = "plugin_glpiinventory_configuration";


   /**
    * Define tabs to display on form page
    *
    * @param array $options
    * @return array containing the tabs name
    */
   function defineTabs($options = []) {

      $tabs = [];
      $moduleTabs = [];
      $tabs[1]=__('General setup');
      $tabs[2]=__('Agents modules', 'glpiinventory');

      if (isset($_SESSION['glpi_plugin_glpiinventory']['configuration']['moduletabforms'])) {
         $plugin_tabs = $tabs;
         $moduleTabForms =
               $_SESSION['glpi_plugin_glpiinventory']['configuration']['moduletabforms'];
         if (count($moduleTabForms)) {
            foreach ($moduleTabForms as $module=>$form) {
               $plugin = new Plugin;
               if ($plugin->isActivated($module)) {
                  $tabs[] = key($form);
               }
            }
            $moduleTabs = array_diff($tabs, $plugin_tabs);
         }
         $_SESSION['glpi_plugin_glpiinventory']['configuration']['moduletabs'] = $moduleTabs;
      }
      return $tabs;
   }


   /**
    * Display configuration form
    *
    * @param array $options
    * @return true
    */
   function showConfigForm($options = []) {

      $this->initForm($options);

      return true;
   }
}
