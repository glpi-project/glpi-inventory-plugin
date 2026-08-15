<?php

/**
 * ---------------------------------------------------------------------
 * GLPI Inventory Plugin
 * @basedon   FusionInventory for GLPI
 * @copyright 2021-2026 Teclib' and contributors.
 * @copyright 2010-2021 by the FusionInventory Development Team.
 *
 * http://glpi-project.org
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

Session::checkLoginUser();

if (Session::getCurrentInterface() !== 'helpdesk') {
    Session::checkRight('plugin_glpiinventory_selfpackage', READ);
}

Html::helpHeader(
    __('GLPI Inventory'),
    '',
    "plugins",
    "deploypackage"
);
$pfDeployPackage = new PluginGlpiinventoryDeployPackage();

if (isset($_POST['prepareinstall'])) {
    $target = null;

    $selections = $pfDeployPackage->filterOfferedSelections(
        PluginGlpiinventoryDeployPackage::getPackageSelections($_POST),
        $_SESSION['glpiID']
    );

    foreach ($selections as $selection) {
        $target = $selection;
        foreach ($selection['packages_ids'] as $packages_id) {
            $pfDeployPackage->deployToItem(
                $selection['itemtype'],
                $selection['items_id'],
                $packages_id,
                $_SESSION['glpiID']
            );
        }
    }

    $agent = $target === null
        ? null
        : PluginGlpiinventoryToolbox::getAgentForItem($target['itemtype'], $target['items_id']);

    //Try to wakeup the agent to perform the deployment task
    //If it's a local wakeup, local call to the agent RPC service
    switch ($_POST['wakeup_type']) {
        case 'local':
            $port = $agent === null ? 0 : (int) $agent->fields['port'];
            if ($port == 0) {
                $port = Agent::DEFAULT_PORT;
            }
            echo Html::scriptBlock("
                $.get('http://127.0.0.1:{$port}/now');
                setTimeout(function(){
                    window.location='{$_SERVER['HTTP_REFERER']}';
                }, 500);
            ");
            return;
        case 'remote':
            if ($agent !== null) {
                //Remote call to wakeup the agent, from the server
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
        '',
        "plugins",
        "pluginglpiinventorymenu",
        "deploypackage"
    );

    $pfDeployPackage->showPackageForMe($_SESSION['glpiID']);
    Html::footer();
}
