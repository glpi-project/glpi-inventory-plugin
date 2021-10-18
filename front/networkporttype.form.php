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

Session::checkRight('config', UPDATE);

$pfNetworkporttype = new PluginFusioninventoryNetworkporttype();

if (isset($_POST['type_to_add'])) {
   foreach ($_POST['type_to_add'] as $id) {
      $input = [];
      $input['id'] = $id;
      $input['import'] = 1;
      $pfNetworkporttype->update($input);
   }
   Html::back();
} else if (isset($_POST['type_to_delete'])) {
   foreach ($_POST['type_to_delete'] as $id) {
      $input = [];
      $input['id'] = $id;
      $input['import'] = 0;
      $pfNetworkporttype->update($input);
   }
   Html::back();
}

