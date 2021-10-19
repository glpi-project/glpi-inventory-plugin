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

Session::checkRight('plugin_glpiinventory_printer', READ);

if ((isset($_POST['update'])) && (isset($_POST['id']))) {
      Session::checkRight('plugin_glpiinventory_printer', UPDATE);

   $plugin_glpiinventory_printer = new PluginGlpiinventoryPrinter();

   $_POST['printers_id'] = $_POST['id'];
   unset($_POST['id']);

   $query = "SELECT *
             FROM `glpi_plugin_glpiinventory_printers`
             WHERE `printers_id`='".$_POST['printers_id']."' ";
   $result = $DB->query($query);

   if ($DB->numrows($result) == "0") {
      $DB->insert(
         'glpi_plugin_glpiinventory_printers', [
            'printers_id' => $_POST['printers_id']
         ]
      );
      $query = "SELECT *
                FROM `glpi_plugin_glpiinventory_printers`
                WHERE `printers_id`='".$_POST['printers_id']."' ";
      $result = $DB->query($query);
   }

   $data = $DB->fetchAssoc($result);
   $_POST['id'] = $data['id'];

   $plugin_glpiinventory_printer->update($_POST);

} else if ((isset($_POST["update_cartridge"])) && (isset($_POST['id']))) {
   Session::checkRight('plugin_glpiinventory_printer', UPDATE);
   $cartridge = new PluginGlpiinventoryPrinterCartridge();
   if ($cartridge->getFromDB($_POST['id'])) {
      $cartridge->update($_POST);
   }
}

$arg = "";
for ($i=1; $i <= 5; $i++) {
   $value = '';
   switch ($i) {
      case 1:
         $value = "datetotalpages";
         break;

      case 2:
         $value = "dateblackpages";
         break;

      case 3:
         $value = "datecolorpages";
         break;

      case 4:
         $value = "daterectoversopages";
         break;

      case 5:
         $value = "datescannedpages";
         break;

   }
   if (isset($_POST[$value])) {
      $_SESSION[$value] = $_POST[$value];
   }
}

if (isset($_POST['graph_plugin_glpiinventory_printer_period'])) {
   $fields = ['graph_begin', 'graph_end', 'graph_timeUnit', 'graph_type'];
   foreach ($fields as $field) {
      if (isset($_POST[$field])) {
         $_SESSION['glpi_plugin_glpiinventory_'.$field] = $_POST[$field];
      } else {
         unset($_SESSION['glpi_plugin_glpiinventory_'.$field]);
      }
   }
}

$field = 'graph_printerCompAdd';
if (isset($_POST['graph_plugin_glpiinventory_printer_add'])) {
   if (isset($_POST[$field])) {
      $_SESSION['glpi_plugin_glpiinventory_'.$field] = $_POST[$field];
   }
} else {
   unset($_SESSION['glpi_plugin_glpiinventory_'.$field]);
}

$field = 'graph_printerCompRemove';
if (isset($_POST['graph_plugin_glpiinventory_printer_remove'])) {
   if (isset($_POST[$field])) {
      $_SESSION['glpi_plugin_glpiinventory_'.$field] = $_POST[$field];
   }
} else {
   unset($_SESSION['glpi_plugin_glpiinventory_'.$field]);
}

Html::back();

