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
 * Manage the update of information into printer in GLPI.
 */
class PluginGlpiinventoryInventoryPrinterLib extends PluginGlpiinventoryInventoryCommon {


   /**
    * Function to update Printer
    *
    * @global object $DB
    * @param array $a_inventory data fron agent inventory
    * @param integer $printers_id id of the printer
    * @param boolean $no_history notice if changes must be logged or not
    */
   function updatePrinter($a_inventory, $printers_id, $no_history = false) {
      global $DB;

      $printer   = new Printer();
      $pfPrinter = new PluginGlpiinventoryPrinter();

      $printer->getFromDB($printers_id);

      if (!isset($_SESSION['glpiactiveentities_string'])) {
         $_SESSION['glpiactiveentities_string'] = $printer->fields['entities_id'];
      }
      if (!isset($_SESSION['glpiactiveentities'])) {
         $_SESSION['glpiactiveentities'] = [$printer->fields['entities_id']];
      }
      if (!isset($_SESSION['glpiactive_entity'])) {
         $_SESSION['glpiactive_entity'] = $printer->fields['entities_id'];
      }

      // * Printer
      $db_printer =  $printer->fields;
      $a_lockable = PluginGlpiinventoryLock::getLockFields('glpi_printers', $printers_id);
      $a_ret      = PluginGlpiinventoryToolbox::checkLock($a_inventory['Printer'],
                                                            $db_printer,
                                                            $a_lockable);

      $a_inventory['Printer'] = $a_ret[0];
      $input                  = $a_inventory['Printer'];
      $input['id']            = $printers_id;
      $input['itemtype']      = 'Printer';
      if (isset($a_inventory['networkport'])) {
         foreach ($a_inventory['networkport'] as $a_port) {
            if (isset($a_port['ip'])) {
               $input['ip'][] = $a_port['ip'];
            }
         }
      }
      //Add the location if needed (play rule locations engine)
      $input = PluginGlpiinventoryToolbox::addLocation($input);

      // manage auto inventory number
      if ($printer->fields['otherserial'] == ''
         && (!isset($input['otherserial'])
            || $input['otherserial'] == '')) {

         $input['otherserial'] = PluginGlpiinventoryToolbox::setInventoryNumber(
            'Printer', '', $printer->fields['entities_id']);
      }

      $printer->update($input, !$no_history);

      $db_printer = [];

      // * Printer fusion (ext)
      $params = [
         'FROM'  => getTableForItemType("PluginGlpiinventoryPrinter"),
         'WHERE' => ['printers_id' => $printers_id]
      ];
      $iterator = $DB->request($params);
      foreach ($iterator as $data) {
         foreach ($data as $key=>$value) {
            $db_printer[$key] = Toolbox::addslashes_deep($value);
         }
      }

      if (count($db_printer) == '0') { // Add
         $a_inventory['PluginGlpiinventoryPrinter']['printers_id'] =
            $printers_id;
         $pfPrinter->add($a_inventory['PluginGlpiinventoryPrinter']);
      } else { // Update
         $idtmp      = $db_printer['id'];
         unset($db_printer['id']);
         unset($db_printer['printers_id']);
         unset($db_printer['plugin_glpiinventory_configsecurities_id']);

         $a_ret = PluginGlpiinventoryToolbox::checkLock(
                     $a_inventory['PluginGlpiinventoryPrinter'],
                     $db_printer);
         $a_inventory['PluginGlpiinventoryPrinter'] = $a_ret[0];
         $input = $a_inventory['PluginGlpiinventoryPrinter'];
         $input['id'] = $idtmp;
         $pfPrinter->update($input);
      }

      // * Ports
      $this->importPorts('Printer', $a_inventory, $printers_id, $no_history);

      //Import firmwares
      $this->importFirmwares('Printer', $a_inventory, $printers_id, $no_history);

      //Import simcards
      $this->importSimcards('Printer', $a_inventory, $printers_id, $no_history);

      // Page counters
      $this->importPageCounters($a_inventory['pagecounters'], $printers_id);

      //Update printer page counter
      $this->updateGlpiPageCounter($a_inventory['pagecounters'], $printers_id);

      // Cartridges
      $this->importCartridges($a_inventory['cartridge'], $printers_id);

      Plugin::doHook("glpiinventory_inventory",
      ['inventory_data' => $a_inventory,
       'printers_id'   => $printers_id,
       'no_history'     => $no_history
      ]);

   }


   /**
    * Import page counters
    *
    * @param array $a_pagecounters
    * @param integer $printers_id
    */
   function importPageCounters($a_pagecounters, $printers_id) {

      $pfPrinterLog = new PluginGlpiinventoryPrinterLog();
      //See if have an entry today
      $a_entires = $pfPrinterLog->find(
            ['printers_id' => $printers_id,
             'date'        => ['LIKE', date("Y-m-d").' %']],
            [], 1);
      if (count($a_entires) > 0) {
         return;
      }

      $a_pagecounters['printers_id'] = $printers_id;
      $a_pagecounters['date'] = date("Y-m-d H:i:s");

      $pfPrinterLog->add($a_pagecounters);
   }


   /**
    * Fill the current counter or page of the printer in GLPI
    * using FI counter value
    *
    * @param array $a_pagecounters
    * @param integer $printers_id
    */
   function updateGlpiPageCounter($a_pagecounters, $printers_id) {
      if (is_array($a_pagecounters) && isset($a_pagecounters['pages_total'])) {
         $printer = new Printer();
         if ($printer->getFromDB($printers_id)) {
            $printer->update(['id'                 => $printers_id,
                              'last_pages_counter' => $a_pagecounters['pages_total']
                             ], 0);
         }
      }
   }


   /**
    * Import cartridges
    *
    * @param array $a_cartridges
    * @param integer $printers_id
    */
   function importCartridges($a_cartridges, $printers_id) {

      $pfPrinterCartridge = new PluginGlpiinventoryPrinterCartridge();

      $a_db = $pfPrinterCartridge->find(['printers_id' => $printers_id]);
      $a_dbcartridges = [];
      foreach ($a_db as $data) {
         $a_dbcartridges[$data['plugin_glpiinventory_mappings_id']] = $data;
      }

      foreach ($a_cartridges as $mappings_id=>$value) {
         if (isset($a_dbcartridges[$mappings_id])) {
            $a_dbcartridges[$mappings_id]['state'] = $value;
            $pfPrinterCartridge->update($a_dbcartridges[$mappings_id]);
         } else {
            $input = [];
            $input['printers_id'] = $printers_id;
            $input['plugin_glpiinventory_mappings_id'] = $mappings_id;
            $input['state'] = $value;
            $pfPrinterCartridge->add($input);
         }
      }
   }
}
