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
