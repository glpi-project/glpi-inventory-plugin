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

include("../../../inc/includes.php");

Html::header(
    __('GLPI Inventory', 'glpiinventory'),
    $_SERVER["PHP_SELF"],
    "admin",
    "glpiinventory",
    "agentmodules"
);

Session::checkRight('plugin_glpiinventory_agent', READ);

$agentmodule = new PluginGlpiinventoryAgentmodule();

if (isset($_POST["agent_add"])) {
    $agentmodule->getFromDB($_POST['id']);
    $a_agentList         = importArrayFromDB($agentmodule->fields['exceptions']);
    $a_agentList[]       = $_POST['agent_to_add'][0];
    $input               = [];
    $input['exceptions'] = exportArrayToDB($a_agentList);
    $input['id']         = $_POST['id'];
    $agentmodule->update($input);
    Html::back();
} elseif (isset($_POST["agent_delete"])) {
    $agentmodule->getFromDB($_POST['id']);
    $a_agentList         = importArrayFromDB($agentmodule->fields['exceptions']);
    foreach ($a_agentList as $key => $value) {
        if ($value == $_POST['agent_to_delete'][0]) {
            unset($a_agentList[$key]);
        }
    }
    $input = [];
    $input['exceptions'] = exportArrayToDB($a_agentList);
    $input['id'] = $_POST['id'];
    $agentmodule->update($input);
    Html::back();
} elseif (isset($_POST["updateexceptions"])) {
    $a_modules = $agentmodule->find();
    foreach ($a_modules as $data) {
        $a_agentList        = importArrayFromDB($data['exceptions']);
        $agentModule        = 0;
        if (
            isset($_POST['activation-' . $data['modulename']])
            && $_POST['activation-' . $data['modulename']] != 0
        ) {
            $agentModule     = 1;
        }
        $agentModuleBase    = 0;
        if (in_array($_POST['id'], $a_agentList)) {
            $agentModuleBase = 1;
        }
        if ($data['is_active'] == 0) {
            if (($agentModule == 1) and ($agentModuleBase == 0)) {
                $a_agentList[] = $_POST['id'];
            } elseif (($agentModule == 0) and ($agentModuleBase == 1)) {
                foreach ($a_agentList as $key => $value) {
                    if ($value == $_POST['id']) {
                        unset($a_agentList[$key]);
                    }
                }
            }
        } elseif ($data['is_active'] == 1) {
            if (($agentModule == 1) and ($agentModuleBase == 1)) {
                foreach ($a_agentList as $key => $value) {
                    if ($value == $_POST['id']) {
                        unset($a_agentList[$key]);
                    }
                }
            } elseif (($agentModule == 0) and ($agentModuleBase == 0)) {
                $a_agentList[]  = $_POST['id'];
            }
        }
        $data['exceptions'] = exportArrayToDB($a_agentList);
        $agentmodule->update($data);
    }

    Html::back();
} elseif (isset($_POST["update"])) {
    $agentmodule->getFromDB($_POST['id']);
    $input = [];
    if (
        isset($_POST['activation'])
        && $_POST['activation']
    ) {
        $input['is_active'] = 1;
    } else {
        $input['is_active'] = 0;
    }
    if ($agentmodule->fields['is_active'] != $input['is_active']) {
        $a_agentList         = [];
        $input['exceptions'] = exportArrayToDB($a_agentList);
    }
    $input['id']  = $_POST['id'];

    $agentmodule->update($input);
    Html::back();
}

Html::footer();
