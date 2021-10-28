<?php
/**
 *  * ---------------------------------------------------------------------
 *  * GLPI Inventory Plugin
 *  * Copyright (C) 2021 Teclib' and contributors.
 *  *
 *  * http://glpi-project.org
 *  *
 *  * based on FusionInventory for GLPI
 *  * Copyright (C) 2010-2021 by the FusionInventory Development Team.
 *  *
 *  * ---------------------------------------------------------------------
 *  *
 *  * LICENSE
 *  *
 *  * This file is part of GLPI Inventory Plugin.
 *  *
 *  * GLPI Inventory Plugin is free software: you can redistribute it and/or modify
 *  * it under the terms of the GNU Affero General Public License as published by
 *  * the Free Software Foundation, either version 3 of the License, or
 *  * (at your option) any later version.
 *  *
 *  * GLPI Inventoruy Plugin is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  * GNU Affero General Public License for more details.
 *  *
 *  * You should have received a copy of the GNU Affero General Public License
 *  * along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
 *  * ---------------------------------------------------------------------
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
} else {
   Html::header(__('GLPI Inventory', 'glpiinventory'), filter_input(INPUT_SERVER, "PHP_SELF"), "plugins",
                "glpiinventory");

   Html::redirect(Plugin::getWebDir('glpiinventory')."/front/menu.php");
   Html::footer();
}
