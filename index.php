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

include ("../../inc/includes.php");

//Agent posting an inventory or asking for orders using REST
$rawdata = file_get_contents("php://input");
$action = filter_input(INPUT_GET, "action");
$machineid = filter_input(INPUT_GET, "machineid");
if ((!empty($action)
   && !empty($machineid))
      || !empty($rawdata)) {

   include_once("front/communication.php");

   //Fusioninventory plugin pages
} else {
   Html::header(__('GLPI Inventory', 'glpiinventory'), filter_input(INPUT_SERVER, "PHP_SELF"), "plugins",
                "glpiinventory");

   Html::redirect(Plugin::getWebDir('glpiinventory')."/front/menu.php");
   Html::footer();
}

