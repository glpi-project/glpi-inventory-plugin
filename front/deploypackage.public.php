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

/** @var DBmysql $DB */
global $DB;

Session::checkLoginUser();

if (Session::getCurrentInterface() !== 'helpdesk') {
    Session::checkRight('plugin_glpiinventory_selfpackage', READ);
}

Html::helpHeader(
    __('GLPI Inventory'),
    $_SERVER["PHP_SELF"],
    "plugins",
    "pluginglpiinventorymenu",
    "deploypackage"
);
$pfDeployPackage = new PluginGlpiinventoryDeployPackage();

if (isset($_POST['prepareinstall'])) {
    //Only the (computer, package) pairs offered to the current user may be deployed
    $deployments = $pfDeployPackage->filterAllowedDeployments($_POST, (int) $_SESSION['glpiID']);

    foreach ($deployments as $target_computers_id => $packages_ids) {
        foreach ($packages_ids as $packages_id) {
            $pfDeployPackage->deployToComputer($target_computers_id, $packages_id, $_SESSION['glpiID']);
        }
    }

    //Every computer having received a package must have its agent woken up
    $deployed_computers_ids = array_keys($deployments);

    $agent_rows = [];
    if ($deployed_computers_ids !== []) {
        foreach ($DB->request([
            'FROM'   => Agent::getTable(),
            'WHERE'  => ['itemtype' => Computer:class, 'items_id' => $deployed_computers_ids],
        ]) as $agent_row) {
            $agent_rows[] = $agent_row;
        }
    }

    //Try to wakeup the agent to perform the deployment task
    //If it's a local wakeup, local call to the agent RPC service
    switch ($_POST['wakeup_type']) {
        case 'local':
            $ports = [];
            foreach ($agent_rows as $agent_row) {
                $port = (int) $agent_row['port'];
                $ports[$port > 0 ? $port : Agent::DEFAULT_PORT] = true;
            }
            if ($ports === []) {
                $ports[Agent::DEFAULT_PORT] = true;
            }
            $wakeup_calls = '';
            foreach (array_keys($ports) as $port) {
                $wakeup_calls .= "$.get('http://127.0.0.1:{$port}/now');\n";
            }
            echo Html::scriptBlock("
                {$wakeup_calls}
                setTimeout(function(){
                    window.location='{$_SERVER['HTTP_REFERER']}';
                }, 500);
            ");
            exit;
            break;
        case 'remote':
            foreach ($agent_rows as $agent_row) {
                //Remote call to wakeup the agent, from the server
                $agent = new Agent();
                $agent->getFromResultSet($agent_row);
                PluginGlpiinventoryAgentWakeup::wakeUp($agent);
            }
            break;
        default:
            break;
    }

    Html::back();
} else {
    Html::header(
        __('GLPI Inventory'),
        $_SERVER["PHP_SELF"],
        "plugins",
        "pluginglpiinventorymenu",
        "deploypackage"
    );

    $pfDeployPackage->showPackageForMe($_SESSION['glpiID']);
    Html::footer();
}
