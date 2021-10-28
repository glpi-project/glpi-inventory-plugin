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
 * Manage the hooks for computer inventory. It's some other things we can do
 * with inventory information.
 */
class PluginGlpiinventoryInventoryComputerLibhook {


   /**
    * __contruct function where initilize sessions variables
    */
   function __construct() {
      if (!isset($_SESSION["plugin_glpiinventory_history_add"])) {
         $_SESSION["plugin_glpiinventory_history_add"] = true;
      }
      if (!isset($_SESSION["plugin_glpiinventory_no_history_add"])) {
         $_SESSION["plugin_glpiinventory_no_history_add"] = false;
      }
      $_SESSION["plugin_glpiinventory_userdefined"] = 0;
   }


    /**
     * Define Mapping for unlock fields
     *
     * @return array of the mapping
     */
   static function getMapping() {
      $opt = [];

      $i = 0;

      // ** HARDWARE
      $opt[$i]['xmlSection']       = 'HARDWARE';
      $opt[$i]['xmlSectionChild']  = 'NAME';
      $opt[$i]['glpiItemtype']     = 'Computer';
      $opt[$i]['glpiField']        = 'name';

      $i++;
      $opt[$i]['xmlSection']       = 'HARDWARE';
      $opt[$i]['xmlSectionChild']  = 'OSNAME';
      $opt[$i]['glpiItemtype']     = 'Computer';
      $opt[$i]['glpiField']        = 'operatingsystems_id';

      $i++;
      $opt[$i]['xmlSection']       = 'HARDWARE';
      $opt[$i]['xmlSectionChild']  = 'OSVERSION';
      $opt[$i]['glpiItemtype']     = 'Computer';
      $opt[$i]['glpiField']        = 'operatingsystemversions_id';

      $i++;
      $opt[$i]['xmlSection']       = 'HARDWARE';
      $opt[$i]['xmlSectionChild']  = 'WINPRODID';
      $opt[$i]['glpiItemtype']     = 'Computer';
      $opt[$i]['glpiField']        = 'os_licenseid';

      $i++;
      $opt[$i]['xmlSection']       = 'HARDWARE';
      $opt[$i]['xmlSectionChild']  = 'WINPRODKEY';
      $opt[$i]['glpiItemtype']     = 'Computer';
      $opt[$i]['glpiField']        = 'os_license_number';

      $i++;
      $opt[$i]['xmlSection']       = 'HARDWARE';
      $opt[$i]['xmlSectionChild']  = 'WORKGROUP';
      $opt[$i]['glpiItemtype']     = 'Computer';
      $opt[$i]['glpiField']        = 'domains_id';

      $i++;
      $opt[$i]['xmlSection']       = 'HARDWARE';
      $opt[$i]['xmlSectionChild']  = 'OSCOMMENTS';
      $opt[$i]['glpiItemtype']     = 'Computer';
      $opt[$i]['glpiField']        = 'operatingsystemservicepacks_id';

      $i++;
      $opt[$i]['xmlSection']       = 'HARDWARE';
      $opt[$i]['xmlSectionChild']  = 'UUID';
      $opt[$i]['glpiItemtype']     = 'Computer';
      $opt[$i]['glpiField']        = 'uuid';

      $i++;
      $opt[$i]['xmlSection']       = 'HARDWARE';
      $opt[$i]['xmlSectionChild']  = 'DESCRIPTION';
      $opt[$i]['glpiItemtype']     = 'Computer';
      $opt[$i]['glpiField']        = 'comment';

      // ** USERS
      $i++;
      $opt[$i]['xmlSection']       = 'USERS';
      $opt[$i]['xmlSectionChild']  = 'LOGIN';
      $opt[$i]['glpiItemtype']     = 'Computer';
      $opt[$i]['glpiField']        = 'users_id';

      $i++;
      $opt[$i]['xmlSection']       = 'USERS';
      $opt[$i]['xmlSectionChild']  = 'LOGIN';
      $opt[$i]['glpiItemtype']     = 'Computer';
      $opt[$i]['glpiField']        = 'contact';

      // ** BIOS
      $i++;
      $opt[$i]['xmlSection']       = 'BIOS';
      $opt[$i]['xmlSectionChild']  = 'SMANUFACTURER';
      $opt[$i]['glpiItemtype']     = 'Computer';
      $opt[$i]['glpiField']        = 'manufacturers_id';

      $i++;
      $opt[$i]['xmlSection']       = 'BIOS';
      $opt[$i]['xmlSectionChild']  = 'SMODEL';
      $opt[$i]['glpiItemtype']     = 'Computer';
      $opt[$i]['glpiField']        = 'computermodels_id';

      $i++;
      $opt[$i]['xmlSection']       = 'BIOS';
      $opt[$i]['xmlSectionChild']  = 'SSN';
      $opt[$i]['glpiItemtype']     = 'Computer';
      $opt[$i]['glpiField']        = 'serial';

      $i++;
      $opt[$i]['xmlSection']       = 'BIOS';
      $opt[$i]['xmlSectionChild']  = 'TYPE';
      $opt[$i]['glpiItemtype']     = 'Computer';
      $opt[$i]['glpiField']        = 'computertypes_id';

      return $opt;
   }


    /**
     * Update model for HP for suppliertag plugin
     *
     * @param integer $items_id id of the computer
     * @param string $partnumber HP partnumber
     */
   function Suppliertag($items_id, $partnumber) {
      if ($partnumber != 'Not Specified') {
         $a_partnumber = explode("#", $partnumber);
         $Plugin = new Plugin();
         if ($Plugin->isActivated('manufacturersimports')) {
            if (class_exists("PluginManufacturersimportsModel")) {
               $PluginManufacturersimportsModel = new PluginManufacturersimportsModel();
               $PluginManufacturersimportsModel->addModel(
                       ['items_id'   => $items_id,
                             'itemtype'   => 'Computer',
                             'model_name' => $a_partnumber[0]]);
            }
         }
      }
   }
}
