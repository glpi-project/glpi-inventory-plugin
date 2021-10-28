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

include ("../../../inc/includes.php");

Html::header(__('GLPI Inventory', 'glpiinventory'),
             $_SERVER["PHP_SELF"],
             "admin",
             "pluginglpiinventorymenu",
             "inventorycomputerimportxml");

Session::checkRight('plugin_glpiinventory_importxml', CREATE);

PluginGlpiinventoryMenu::displayMenu("mini");

$pfCommunication = new PluginGlpiinventoryCommunication();

if (isset($_FILES['importfile']) && $_FILES['importfile']['tmp_name'] != '') {

   error_log($_FILES['importfile']['name']);
   ini_set("memory_limit", "-1");
   ini_set("max_execution_time", "0");

   if (preg_match('/\.zip/i', $_FILES['importfile']['name'])) {
      $zip = new ZipArchive;
      $zip->open($_FILES['importfile']['tmp_name']);

      if (!$zip) {
         error_log("Zip failure");
         Session::addMessageAfterRedirect(
            __("Can't read zip file!", 'glpiinventory'),
            ERROR
         );
      } else {
         for ($n = 0; $n < $zip->numFiles; $n++) {
            $filename = $zip->getNameIndex($n);
            $xml = $zip->getFromName($zip->getNameIndex($n));
            if (!empty($xml)) {
               $_SESSION['glpi_glpiinventory_nolock'] = true;
               $pfCommunication->handleOCSCommunication('', $xml);
               unset($_SESSION['glpi_glpiinventory_nolock']);
            }
         }
         $zip->close();
      }
   } else if (preg_match('/\.(ocs|xml)/i', $_FILES['importfile']['name'])) {

      $xml = file_get_contents($_FILES['importfile']['tmp_name']);
      $_SESSION['glpi_glpiinventory_nolock'] = true;
      $pfCommunication->handleOCSCommunication('', $xml, 'glpi');
      unset($_SESSION['glpi_glpiinventory_nolock']);
   } else {
      Session::addMessageAfterRedirect(
         __('No file to import!', 'glpiinventory'),
         ERROR
      );
   }
   Html::back();
}

$pfInventoryComputerImportXML = new PluginGlpiinventoryInventoryComputerImportXML();
$pfInventoryComputerImportXML->showImportForm();

Html::footer();
