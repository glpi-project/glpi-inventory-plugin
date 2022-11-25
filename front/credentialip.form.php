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

include('../../../inc/includes.php');
$dropdown = new PluginGlpiinventoryCredentialIP();

if (!($dropdown instanceof CommonDropdown)) {
    Html::displayErrorAndDie('');
}
if (!$dropdown->canView()) {
   // Gestion timeout session
    Session::redirectIfNotLoggedIn();
    Html::displayRightError();
}

if (isset($_POST["id"])) {
    $_GET["id"] = $_POST["id"];
} else if (!isset($_GET["id"])) {
    $_GET["id"] = -1;
}

if (isset($_POST["add"])) {
    $dropdown->check(-1, CREATE, $_POST);

    if ($newID = $dropdown->add($_POST)) {
        if ($_SESSION['glpibackcreated']) {
            $url = $dropdown->getLinkURL();
            if (isset($_REQUEST['_in_modal'])) {
                $url .= "&_in_modal=1";
            }
            Html::redirect($url);
        }
    }
    Html::back();
} else if (isset($_POST["purge"])) {
    $dropdown->check($_POST["id"], PURGE);
    if (
        $dropdown->isUsed()
        && empty($_POST["forcepurge"])
    ) {
        Html::header(
            $dropdown->getTypeName(1),
            $_SERVER['PHP_SELF'],
            "config",
            $dropdown->second_level_menu,
            str_replace('glpi_', '', $dropdown->getTable())
        );
        $dropdown->showDeleteConfirmForm($_SERVER['PHP_SELF']);
        Html::footer();
    } else {
        $dropdown->delete($_POST, 1);
        $dropdown->redirectToList();
    }
} else if (isset($_POST["update"])) {
    $dropdown->check($_POST["id"], UPDATE);
    $dropdown->update($_POST);
    Html::back();
} else if (isset($_GET['_in_modal'])) {
    Html::popHeader(
        $dropdown->getTypeName(1),
        $_SERVER['PHP_SELF'],
        true,
        $dropdown->first_level_menu,
        $dropdown->second_level_menu,
        $dropdown->getType()
    );
    $dropdown->showForm($_GET["id"]);
    Html::popFooter();
} else {
    Html::header(
        __('GLPI Inventory', 'glpiinventory'),
        $_SERVER["PHP_SELF"],
        "admin",
        "pluginglpiinventorymenu",
        "credential"
    );
    PluginGlpiinventoryMenu::displayMenu("mini");
    //If there is no form to submit, display the form
    $dropdown->display($_GET);
    Html::footer();
}
