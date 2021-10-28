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
Session::checkCentralAccess();

header("Content-Type: text/json; charset=UTF-8");
Html::header_nocache();

if (isset($_REQUEST['id'])) {
   $agent = new PluginGlpiinventoryAgent;
   $agent->getFromDB((int) $_REQUEST['id']);

   if (isset($_REQUEST['action'])) {

      switch ($_REQUEST['action']) {
         case "get_status":
            $agentStatus = $agent->getStatus();
            $agentStatus['waiting'] = false;

            switch ($agentStatus['message']) {

               case 'executing scheduled tasks':
               case 'running':
                  $agentStatus['message'] = __('Running');
                  break;

               case 'noanswer':
                  $agentStatus['message'] = "<i class='fa fa-exclamation-triangle'></i>".
                                            __('cannot contact the agent', 'glpiinventory');
                  break;

               case 'waiting':
                  $agentStatus['waiting'] = true;
                  $agentStatus['message'] = sprintf(
                     __('Available on %1$s', 'glpiinventory'),
                     '<a target="_blank" href="'. $agentStatus['url_ok'] . '">' . $agentStatus['url_ok'] . '</a>'
                  );
                  break;

               default:
                  if (strstr($agentStatus['message'], 'running')) {
                     $agentStatus['message'] = $agentStatus['message'];
                  } else {
                     $agentStatus['message'] = "SELinux problem, do 'setsebool -P httpd_can_network_connect on'";
                  }
                  break;


            }
            echo json_encode($agentStatus);
            break;

         case "start_agent";
            if ($agent->wakeUp()) {
               Session::addMessageAfterRedirect(__('The agent is running', 'glpiinventory'));
               $response = [
                  'status' => 'ok'
               ];

            } else {
               Session::addMessageAfterRedirect(__('Impossible to communicate with agent!', 'glpiinventory'));
               $response = [
                  'status' => 'ko'
               ];
            }

            echo json_encode($response);
            break;

      }
   }
}
