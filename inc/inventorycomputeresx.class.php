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

/**
 * Manage the taskjob for VMWARE ESX / VCENTER remote inventory.
 */
class PluginGlpiinventoryInventoryComputerESX extends PluginGlpiinventoryCommunication
{
    /**
     * Get ESX jobs for this agent
     *
     * @param object $taskjobstate
     * @return array<string,string>
     */
    public function run($taskjobstate)
    {
        $credential     = new PluginGlpiinventoryCredential();
        $credentialip   = new PluginGlpiinventoryCredentialIp();

        $credentialip->getFromDB($taskjobstate->fields['items_id']);
        $credential->getFromDB($credentialip->fields['plugin_glpiinventory_credentials_id']);

        $order['uuid'] = $taskjobstate->fields['uniqid'];
        $order['host'] = $credentialip->fields['ip'];
        $order['user'] = $credential->fields['username'];
        $order['password'] = $credential->fields['password'];
        return $order;
    }
}
