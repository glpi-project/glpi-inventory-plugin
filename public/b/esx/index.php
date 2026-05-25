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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Inventory\Conf;
use Glpi\Inventory\Request;

$conf = new Conf();
if ($conf->enabled_inventory != 1) {
    throw new AccessDeniedHttpException("Inventory is disabled");
}

$inventory_request = new Request();
$inventory_request->handleHeaders();
$inventory_request->handleContentType('application/json');

$request_ok = false;
if (PluginGlpiinventoryToolbox::authInventory($inventory_request)) {
    $request_ok = true;
}

//This call is to check that the ESX inventory service is up and running
$fi_status = filter_input(INPUT_GET, "status");
if ($request_ok && !empty($fi_status)) {
    return 'ok';
}

$response = false;
//Agent communication using REST protocol
$fi_machineid = filter_input(INPUT_GET, "machineid");
if ($request_ok && !empty($fi_machineid)) {
    switch (filter_input(INPUT_GET, "action")) {
        case 'getJobs':
            $agent        = new Agent();
            $pfTask         = new PluginGlpiinventoryTask();
            $pfTaskjob      = new PluginGlpiinventoryTaskjob();
            $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();

            if ($agent->getFromDBByCrit(['deviceid' => filter_input(INPUT_GET, "machineid")])) {
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

                if (count($order->jobs) > 0) {
                    $response = $order;
                }
            }

            break;

        case 'setLog':
            //Generic method to update logs
            PluginGlpiinventoryCommunicationRest::updateLog($_GET);
            break;
    }
}

http_response_code($inventory_request->getHttpResponseCode());
$headers = $inventory_request->getHeaders(true);
foreach ($headers as $key => $value) {
    header(sprintf('%s: %s', $key, $value));
}

if ($response !== false) {
    $inventory_request->addToResponse((array) $response);
    echo $inventory_request->getResponse();
} elseif ($request_ok) {
    // Authenticated, but no job to send: return a valid (empty) JSON object so
    // the agent does not complain about a non-hash answer ("[]" is an array).
    echo json_encode((object) []);
} else {
    // Not authenticated: output the authentication challenge / error response.
    echo $inventory_request->getResponse();
}
