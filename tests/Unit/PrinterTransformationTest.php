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

use PHPUnit\Framework\TestCase;

class PrinterTransformationTest extends TestCase {


   /**
    * @test
    */
   public function PrinterGeneral() {
      global $DB;

      $DB->connect();

      $_SESSION["plugin_glpiinventory_entity"] = 0;
      $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

      $a_printer = [];
      $a_printer['INFO'] = [
                'COMMENTS'       => 'HP ETHERNET MULTI-ENVIRONMENT',
                'ID'             => '54',
                'LOCATION'       => 'Room 102',
                'MANUFACTURER'   => 'Hewlett Packard',
                'MODEL'          => 'HP LaserJet P1505n',
                'NAME'           => 'ARC12-B09-N',
                'SERIAL'         => 'VRG5XUT4',
                'TYPE'           => 'PRINTER',
                'MEMORY'         => 64
            ];

      $pfFormatconvert = new PluginGlpiinventoryFormatconvert();

      $a_return = $pfFormatconvert->printerInventoryTransformation($a_printer);
      $date = date('Y-m-d H:i:s');
      if (isset($a_return['PluginGlpiinventoryPrinter'])
              && isset($a_return['PluginGlpiinventoryPrinter']['last_inventory_update'])) {
         $date = $a_return['PluginGlpiinventoryPrinter']['last_inventory_update'];
      }
      $a_reference = [
          'PluginGlpiinventoryPrinter' => [
                  'sysdescr'                    => 'HP ETHERNET MULTI-ENVIRONMENT',
                  'last_inventory_update' => $date
                ],
          'networkport'    => [],
          'cartridge'      => [],
          'pagecounters'   => [],
          'itemtype'       => 'Printer'
          ];
      $a_reference['Printer'] = [
               'name'               => 'ARC12-B09-N',
               'serial'             => 'VRG5XUT4',
               'id'                 => 54,
               'manufacturers_id'   => 'Hewlett Packard',
               'locations_id'       => 'Room 102',
               'printermodels_id'   => 'HP LaserJet P1505n',
               'memory_size'        => 64,
               'is_dynamic'         => 1,
               'have_ethernet'      => 1
      ];
      $this->assertEquals($a_reference, $a_return);

   }


   /**
    * @test
    */
   public function PrinterPageCounter() {
      global $DB;

      $DB->connect();

      $_SESSION["plugin_glpiinventory_entity"] = 0;
      $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

      $a_printer = [];
      $a_printer['INFO'] = [
                'ID'             => '54',
                'NAME'           => 'ARC12-B09-N',
                'TYPE'           => 'PRINTER'
            ];
      $a_printer['PAGECOUNTERS'] = [
                'BLACK'       => 10007,
                'COLOR'       => 5127,
                'RECTOVERSO'  => 0,
                'TOTAL'       => 15134,
                'COPYTOTAL'   => ''
            ];

      $pfFormatconvert = new PluginGlpiinventoryFormatconvert();

      $a_return = $pfFormatconvert->printerInventoryTransformation($a_printer);
      $date = date('Y-m-d H:i:s');
      if (isset($a_return['PluginGlpiinventoryPrinter'])
              && isset($a_return['PluginGlpiinventoryPrinter']['last_inventory_update'])) {
         $date = $a_return['PluginGlpiinventoryPrinter']['last_inventory_update'];
      }
      $a_reference = [
          'PluginGlpiinventoryPrinter' => [
                  'sysdescr'                    => '',
                  'last_inventory_update' => $date
                ],
          'networkport' => [],
          'cartridge'   => [],
          'itemtype'    => 'Printer'
          ];
      $a_reference['Printer'] = [
               'name'               => 'ARC12-B09-N',
               'id'                 => 54,
               'serial'             => '',
               'manufacturers_id'   => '',
               'locations_id'       => '',
               'printermodels_id'   => '',
               'memory_size'        => 0,
               'is_dynamic'         => 1,
               'have_ethernet'      => 1
      ];
      $a_reference['pagecounters'] = [
               'pages_total'        => 15134,
               'pages_n_b'          => 10007,
               'pages_color'        => 5127,
               'pages_recto_verso'  => 0,
               'pages_total_copy'   => 0,
               'scanned'            => 0,
               'pages_total_print'  => 0,
               'pages_n_b_print'    => 0,
               'pages_color_print'  => 0,
               'pages_n_b_copy'     => 0,
               'pages_color_copy'   => 0,
               'pages_total_fax'    => 0

          ];
      $this->assertEquals($a_reference, $a_return);
   }


   /**
    * @test
    */
   public function PrinterCartridge() {
      global $DB;

      $DB->connect();

      $_SESSION["plugin_glpiinventory_entity"] = 0;
      $_SESSION["glpiname"] = 'Plugin_GLPI_Inventory';

      $a_printer = [];
      $a_printer['INFO'] = [
                'ID'             => '54',
                'NAME'           => 'ARC12-B09-N',
                'TYPE'           => 'PRINTER'
            ];
      $a_printer['CARTRIDGES'] = [
                'CARTRIDGEBLACK'   => 90,       // percentage
                'CARTRIDGECYAN'    => '',       // unknown value
                'CARTRIDGEMAGENTA' => 'OK',     // only known if ok or not
                'CARTRIDGEYELLOW'  => '30pages' //define number pages remaining
            ];

      $pfFormatconvert = new PluginGlpiinventoryFormatconvert();
      $pfMapping       = new PluginGlpiinventoryMapping();

      $a_return = $pfFormatconvert->printerInventoryTransformation($a_printer);

      $a_reference = [];
      $a_id = $pfMapping->get("Printer", strtolower('CARTRIDGEBLACK'));
      $a_reference[$a_id['id']] = 90;

      $a_id = $pfMapping->get("Printer", strtolower('CARTRIDGEMAGENTA'));
      $a_reference[$a_id['id']] = 100000;

      $a_id = $pfMapping->get("Printer", strtolower('CARTRIDGEYELLOW'));
      $a_reference[$a_id['id']] = -30;

      $this->assertEquals($a_reference, $a_return['cartridge']);
   }
}
