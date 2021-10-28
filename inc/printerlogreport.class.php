<?php
/**
 *  * ---------------------------------------------------------------------
 *  * GLPI Inventory Plugin
 *  * Copyright (C) 2021 Teclib' and contributors.
 *  *
 *  * http://glpi-project.org
 *  *
 *  * based on FusionInventory for GLPI
 *  * Copyright (C) 2010-2021 by the FusionInventory Development Team.
 *  *
 *  * ---------------------------------------------------------------------
 *  *
 *  * LICENSE
 *  *
 *  * This file is part of GLPI Inventory Plugin.
 *  *
 *  * GLPI Inventory Plugin is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Affero General Public License as published by
 *  * the Free Software Foundation, either version 3 of the License, or
 *  * (at your option) any later version.
 *  *
 *  * GLPI Inventoruy Plugin is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  * GNU Affero General Public License for more details.
 *  *
 *  * You should have received a copy of the GNU Affero General Public License
 *  * along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
 *  * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Manage the log reports of printers.
 */
class PluginGlpiinventoryPrinterLogReport extends CommonDBTM {


   /**
    * __contruct function where initialize some variables
    *
    * @global array $CFG_GLPI
    */
   function __construct() {
      global $CFG_GLPI;
      $this->table = "glpi_plugin_glpiinventory_printers";
      $CFG_GLPI['glpitablesitemtype']["PluginGlpiinventoryPrinterLogReport"] = $this->table;
   }


   /**
    * Get search function for the class
    *
    * @return array
    */
   function rawSearchOptions() {

      $pfPrinterLog = new PluginGlpiinventoryPrinterLog();
      $tab = $pfPrinterLog->rawSearchOptions();

      foreach ($tab as $searchOptions) {
         if ($searchOptions['table'] == PluginGlpiinventoryPrinterLog::getTable()) {
            $tab[$searchOptions['id']]['forcegroupby']='1';
         }
      }
      return $tab;
   }
}
