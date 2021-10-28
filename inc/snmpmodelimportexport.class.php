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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Manage the network discovery import.
 */
class PluginGlpiinventorySnmpmodelImportExport extends CommonGLPI {


   /**
    * Import discovery devices
    *
    * @param array $arrayinventory
    * @param string $device_id
    */
   function import_netdiscovery($arrayinventory, $device_id) {

      PluginGlpiinventoryCommunication::addLog(
         'Function PluginGlpiinventorySnmpmodelImportExport->import_netdiscovery().');

      $ptap = new PluginGlpiinventoryStateDiscovery();
      $pta  = new PluginGlpiinventoryAgent();

      $agent = $pta->infoByKey($device_id);

      if (isset($arrayinventory['AGENT']['START'])) {
         $ptap->updateState($arrayinventory['PROCESSNUMBER'],
                            ['start_time' => date("Y-m-d H:i:s")], $agent['id']);
      } else if (isset($arrayinventory['AGENT']['END'])) {
         $ptap->updateState($arrayinventory['PROCESSNUMBER'],
                            ['end_time' => date("Y-m-d H:i:s")], $agent['id']);
      } else if (isset($arrayinventory['AGENT']['EXIT'])) {
         $ptap->endState($arrayinventory['PROCESSNUMBER'], date("Y-m-d H:i:s"), $agent['id']);
      } else if (isset($arrayinventory['AGENT']['NBIP'])) {
         $ptap->updateState($arrayinventory['PROCESSNUMBER'],
                            ['nb_ip' => $arrayinventory['AGENT']['NBIP']], $agent['id']);
      }
      if (isset($arrayinventory['AGENT']['AGENTVERSION'])) {
         $agent['last_contact'] = date("Y-m-d H:i:s");
         $pta->update($agent);
      }
      $_SESSION['glpi_plugin_glpiinventory_agentid'] = $agent['id'];
      $count_discovery_devices = 0;
      if (isset($arrayinventory['DEVICE'])) {
         if (is_int(key($arrayinventory['DEVICE']))) {
            $count_discovery_devices = count($arrayinventory['DEVICE']);
         } else {
            $count_discovery_devices = 1;
         }
      }
      if ($count_discovery_devices != "0") {
         $ptap->updateState($_SESSION['glpi_plugin_glpiinventory_processnumber'],
                            ['nb_found' => $count_discovery_devices], $agent['id']);
         if (is_int(key($arrayinventory['DEVICE']))) {
            foreach ($arrayinventory['DEVICE'] as $discovery) {
               if (count($discovery) > 0) {
                  $pfCommunicationNetworkDiscovery =
                                    new PluginGlpiinventoryCommunicationNetworkDiscovery();
                  $pfCommunicationNetworkDiscovery->sendCriteria($discovery);
               }
            }
         } else {
            $pfCommunicationNetworkDiscovery =
                                    new PluginGlpiinventoryCommunicationNetworkDiscovery();
            $pfCommunicationNetworkDiscovery->sendCriteria($arrayinventory['DEVICE']);
         }
      }
   }
}
