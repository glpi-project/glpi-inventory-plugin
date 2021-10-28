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
   die("Sorry. You can't access directly to this file");
}

/**
 * Manage the webservice methods offered by the plugin
 * It require the GLPI plugin Webservice.
 */
class PluginGlpiinventoryInventoryComputerWebservice {


   /**
   * Method for import XML by webservice
   *
   * @param array $params array ID of the agent
   * @param string $protocol value the communication protocol used
   * @return array
   *
   **/
   static function loadInventory($params, $protocol) {

      if (isset ($params['help'])) {
         return ['base64'  => 'string, mandatory',
                      'help'    => 'bool, optional'];
      }
      if (!isset ($_SESSION['glpiID'])) {
         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      $content = base64_decode($params['base64']);

      $pfCommunication = new PluginGlpiinventoryCommunication();
      $pfCommunication->handleOCSCommunication('', $content);

      $msg = __('Computer injected into GLPI', 'glpiinventory');

      return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_FAILED, '', $msg);
   }


   /**
    * More information on the method
    *
    * @param array $params
    * @param string $protocol
    * @return array
    */
   static function methodExtendedInfo($params, $protocol) {
      $response = [];

      if (!isset($params['computers_id'])
              || !is_numeric($params['computers_id'])) {
         return $response;
      }
      $pfInventoryComputerComputer = new PluginGlpiinventoryInventoryComputerComputer();
      $a_computerextend = current($pfInventoryComputerComputer->find(
                                              ['computers_id' => $params['computers_id']],
                                              [], 1));
      if (empty($a_computerextend)) {
         return $response;
      }
      return $a_computerextend;
   }
}
