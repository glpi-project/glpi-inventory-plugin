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
 * Manage the location rule collection.
 */
class PluginGlpiinventoryInventoryRuleLocationCollection extends RuleCollection {

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = "plugin_glpiinventory_rulelocation";

   /**
    * Set stop play rules when have the first rule of list match
    *
    * @var boolean
    */
   public $stop_on_first_match=true;

   /**
    * Define the name of menu option
    *
    * @var string
    */
   public $menu_option='test';


   /**
    * Get name of this type by language of the user connected
    *
    * @return string name of this type
    */
   function getTitle() {
      return __('Location rules', 'glpiinventory');
   }
}
