<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Manage the network discovery import.
 */
class PluginFusioninventorySnmpmodelImportExport extends CommonGLPI {


   /**
    * Import discovery devices
    *
    * @param array $arrayinventory
    * @param string $device_id
    */
   function import_netdiscovery($arrayinventory, $device_id) {

      PluginFusioninventoryCommunication::addLog(
         'Function PluginFusioninventorySnmpmodelImportExport->import_netdiscovery().');

      $ptap = new PluginFusioninventoryStateDiscovery();
      $pta  = new PluginFusioninventoryAgent();

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
      $_SESSION['glpi_plugin_fusioninventory_agentid'] = $agent['id'];
      $count_discovery_devices = 0;
      if (isset($arrayinventory['DEVICE'])) {
         if (is_int(key($arrayinventory['DEVICE']))) {
            $count_discovery_devices = count($arrayinventory['DEVICE']);
         } else {
            $count_discovery_devices = 1;
         }
      }
      if ($count_discovery_devices != "0") {
         $ptap->updateState($_SESSION['glpi_plugin_fusioninventory_processnumber'],
                            ['nb_found' => $count_discovery_devices], $agent['id']);
         if (is_int(key($arrayinventory['DEVICE']))) {
            foreach ($arrayinventory['DEVICE'] as $discovery) {
               if (count($discovery) > 0) {
                  $pfCommunicationNetworkDiscovery =
                                    new PluginFusioninventoryCommunicationNetworkDiscovery();
                  $pfCommunicationNetworkDiscovery->sendCriteria($discovery);
               }
            }
         } else {
            $pfCommunicationNetworkDiscovery =
                                    new PluginFusioninventoryCommunicationNetworkDiscovery();
            $pfCommunicationNetworkDiscovery->sendCriteria($arrayinventory['DEVICE']);
         }
      }
   }
}
