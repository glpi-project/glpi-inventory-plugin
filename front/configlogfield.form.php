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

Html::header(__('Features', 'glpiinventory'), $_SERVER["PHP_SELF"], "admin",
        "pluginglpiinventorymenu", "configlogfield");

if (isset($_POST['update'])) {

   if (empty($_POST['cleaning_days'])) {
      $_POST['cleaning_days'] = 0;
   }

   $_POST['id']=1;
   switch ($_POST['tabs']) {

      case 'config' :
         break;

      case 'history' :
         $pfConfigLogField = new PluginGlpiinventoryConfigLogField();
         foreach ($_POST as $key=>$val) {
            $split = explode("-", $key);
            if (isset($split[1]) AND is_numeric($split[1])) {
               $pfConfigLogField->getFromDB($split[1]);
               $input = [];
               $input['id'] = $pfConfigLogField->fields['id'];
               $input['days'] = $val;
               $pfConfigLogField->update($input);
            }
         }
         break;

   }
   if (isset($pfConfig)) {
      $pfConfig->update($_POST);
   }
   Html::back();
} else if ((isset($_POST['Clean_history']))) {
   $pfNetworkPortLog = new PluginGlpiinventoryNetworkPortLog();
   $pfNetworkPortLog->cronCleannetworkportlogs();
   Html::back();
}

Html::footer();

