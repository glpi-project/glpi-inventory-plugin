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

$pfIPRange_ConfigSecurity = new PluginFusioninventoryIPRange_ConfigSecurity();

if (isset ($_POST["add"])) {

   $a_data = current(getAllDataFromTable('glpi_plugin_fusioninventory_ipranges_configsecurities',
                                 ['plugin_fusioninventory_ipranges_id' => $_POST['plugin_fusioninventory_ipranges_id']],
                                 false,
                                 '`rank` DESC'));
   $_POST['rank'] = 1;
   if (isset($a_data['rank'])) {
      $_POST['rank'] = $a_data['rank'] + 1;
   }
   $pfIPRange_ConfigSecurity->add($_POST);
   Html::back();
}

