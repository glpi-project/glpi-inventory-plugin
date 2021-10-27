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

