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
 * GLPI Inventory Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GLPI Inventory Plugin. If not, see <https://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include("../../../inc/includes.php");

if (PluginGlpiinventoryMenu::canView()) {
    Html::header(
        __('GLPI Inventory', 'glpiinventory'),
        $_SERVER["PHP_SELF"],
        "admin",
        "pluginglpiinventorymenu",
        "menu"
    );

    echo Html::manageRefreshPage();

    PluginGlpiinventoryMenu::displayMenu();

    $dashboard = new Glpi\Dashboard\Grid('plugin_glpiinventory_dashboard');
    echo "<div class='dashboard card p-3'>";
    $dashboard->show();
    echo "</div>";
} else {
    Html::displayRightError();
}

Html::footer();
