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

Session::checkLoginUser();

if (isset($_GET["popup"])) {
    $_SESSION["glpipopup"]["name"] = $_GET["popup"];
}

if (isset($_SESSION["glpipopup"]["name"])) {
    switch ($_SESSION["glpipopup"]["name"]) {
        case "test_rule":
            Html::popHeader(__('Test'), $_SERVER['PHP_SELF']);
            include "../../../front/rule.test.php";
            break;

        case "test_all_rules":
            Html::popHeader(__('Test rules engine'), $_SERVER['PHP_SELF']);
            include "../../../front/rulesengine.test.php";
            break;

        case "show_cache":
            Html::popHeader(__('Cache informations', 'glpiinventory'), $_SERVER['PHP_SELF']);
            include "../../../front/rule.cache.php";
            break;
    }
    echo "<div class='center'><br><a href='javascript:window.close()'>" . __('Back') . "</a>";
    echo "</div>";
    Html::popFooter();
}
