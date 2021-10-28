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

Html::header(__('GLPI Inventory', 'glpiinventory'), $_SERVER["PHP_SELF"],
        "admin", "pluginglpiinventorymenu", "inventoryruleimport");

Session::checkLoginUser();
PluginGlpiinventoryMenu::displayMenu("mini");

RuleCollection::titleBackup();

$rulecollection = new PluginGlpiinventoryInventoryRuleImportCollection();

if (isset($_GET['resetrules'])) {
   $pfSetup = new PluginGlpiinventorySetup();
   $pfSetup->initRules(1);
   Html::back();
}

echo "<center><a href='". Plugin::getWebDir('glpiinventory') .
         "/front/inventoryruleimport.php?resetrules=1' class='vsubmit'>";
echo __('Reset import rules (define only default rules)', 'glpiinventory');
echo "</a></center><br/>";

include (GLPI_ROOT . "/front/rule.common.php");
