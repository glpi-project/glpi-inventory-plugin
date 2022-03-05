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

//This call is to check that the ESX inventory service is up and running
$fi_status = filter_input(INPUT_GET, "status");
if (!empty($fi_status)) {
    return 'ok';
}
ob_start();
include("../../../../inc/includes.php");
ob_end_clean();

$response = false;
//Agent communication using REST protocol
$fi_machineid = filter_input(INPUT_GET, "machineid");
if (!empty($fi_machineid)) {
    switch (filter_input(INPUT_GET, "action")) {
        case 'getJobs':
            $agent        = new Agent();
            $pfTask         = new PluginGlpiinventoryTask();
            $pfTaskjob      = new PluginGlpiinventoryTaskjob();
            $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();

            if ($agent->getFromDBByCrit(['deviceid' => Toolbox::addslashes_deep(filter_input(INPUT_GET, "machineid"))])) {
                $taskjobstates = $pfTask->getTaskjobstatesForAgent(
                    $agent->fields['id'],
                    ['InventoryComputerESX']
                );

                ////start of json response
                $order = new stdClass();
                $order->jobs = [];

                $module = new PluginGlpiinventoryInventoryComputerESX();
                foreach ($taskjobstates as $taskjobstate) {
                     $order->jobs[] = $module->run($taskjobstate);

                     $taskjobstate->changeStatus(
                         $taskjobstate->fields['id'],
                         $taskjobstate::SERVER_HAS_SENT_DATA
                     );
                }

                // return an empty dictionnary if there are no jobs.
                if (count($order->jobs) == 0) {
                    $response = "{}";
                } else {
                    $response = json_encode($order);
                }
            }

            break;

        case 'setLog':
           //Generic method to update logs
            PluginGlpiinventoryCommunicationRest::updateLog($_GET);
            break;
    }

    if ($response !== false) {
        echo $response;
    } else {
        echo json_encode((object)[]);
    }
}
