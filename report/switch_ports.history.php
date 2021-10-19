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

//Options for GLPI 0.71 and newer : need slave db to access the report
$USEDBREPLICATE=1;
$DBCONNECTION_REQUIRED=0;

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

Html::header(__('FusionInventory', 'glpiinventory'), filter_input(INPUT_SERVER, "PHP_SELF"), "utils", "report");

Session::checkRight('plugin_glpiinventory_reportnetworkequipment', READ);

$FK_port = filter_input(INPUT_GET, "networkports_id");

echo "<form action='".filter_input(INPUT_SERVER, "PHP_SELF")."' method='get'>";
echo "<table class='tab_cadre' cellpadding='5'>";
echo "<tr class='tab_bg_1' align='center'>";

echo "<td>";
echo _n('Network port', 'Network ports', 1)." :&nbsp;";

$query = "SELECT `glpi_networkequipments`.`name` as `name`, `glpi_networkports`.`name` as `pname`,
                 `glpi_networkports`.`id` as `id`
          FROM `glpi_networkequipments`
               LEFT JOIN `glpi_networkports` ON `items_id` = `glpi_networkequipments`.`id`
          WHERE `itemtype`='NetworkEquipment'
          ORDER BY `glpi_networkequipments`.`name`, `glpi_networkports`.`logical_number`;";

$result=$DB->query($query);
      $selected = '';
while ($data=$DB->fetchArray($result)) {

   if (($data['id'] == $FK_port)) {
      $selected = $data['id'];
   }
   $ports[$data['id']] = $data['name']." - ".$data['pname'];
}

Dropdown::showFromArray("networkports_id", $ports,
                        ['value'=>$selected]);
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td align='center'>";
echo "<input type='submit' value='" . __('Validate')  . "' class='submit' />";
echo "</td>";
echo "</tr>";

echo "</table>";
Html::closeForm();

$networkports_id = filter_input(INPUT_GET, "networkports_id");
if ($networkports_id != '') {
   echo PluginGlpiinventoryNetworkPortLog::showHistory($networkports_id);
}

Html::closeForm();

Html::footer();

