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

$pfCollect_File = new PluginGlpiinventoryCollect_File();

if (isset($_POST["add"])) {
   // conversions
    if (
        $_POST['sizetype'] != 'none'
           && $_POST['size'] != ''
    ) {
        $_POST['filter_size' . $_POST['sizetype']] = $_POST['size'];
    }
    if (
        $_POST['filter_nametype'] != 'none'
           && $_POST['filter_name'] != ''
    ) {
        $_POST['filter_' . $_POST['filter_nametype']] = $_POST['filter_name'];

        //set null if needed
        if ($_POST['filter_nametype'] == 'iname') {
            $_POST['filter_name'] = null;
        } else {
            $_POST['filter_iname'] = null;
        }
    } else {
        //if 'none' , name and iname need to be null
        $_POST['filter_iname'] = null;
        $_POST['filter_name'] = null;
    }
    if ($_POST['type'] == 'file') {
        $_POST['filter_is_file'] = 1;
        $_POST['filter_is_dir'] = 0;
    } else {
        $_POST['filter_is_file'] = 0;
        $_POST['filter_is_dir'] = 1;
    }

    $pfCollect_File->add($_POST);
    Html::back();
} elseif (isset($_POST["delete_x"])) {
    $pfCollect_File->delete($_POST);
    Html::back();
}
