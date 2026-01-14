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

use Safe\Exceptions\FilesystemException;

use function Safe\fclose;
use function Safe\fopen;
use function Safe\json_decode;

/**
 * Manage the prepare task job and give the data to the agent when request what
 * to deploy.
 */
class PluginGlpiinventoryDeployCommon extends PluginGlpiinventoryCommunication
{
    /**
     * run function, so return data to send to the agent for deploy
     *
     * @param PluginGlpiinventoryTaskjobstate $taskjobstate PluginGlpiinventoryTaskjobstate instance
     * @return false|array<string,mixed>
     */
    public function run($taskjobstate)
    {

        //Check if the job has been postponed
        if (
            !is_null($taskjobstate->fields['date_start'])
            && $taskjobstate->fields['date_start'] > $_SESSION['glpi_currenttime']
        ) {
            //If the job is postponed and the execution date is in the future,
            //skip the job for now
            return false;
        }

        //get order by type and package id
        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        if (!$pfDeployPackage->getFromDB($taskjobstate->fields['items_id'])) {
            //entry no longer exists
            trigger_error(
                sprintf(
                    'Item "%1$s" #%2$s does not exists in %3$s table.',
                    $taskjobstate->fields['itemtype'],
                    $taskjobstate->fields['items_id'],
                    $pfDeployPackage->getTable()
                ),
                E_USER_WARNING
            );
            return false;
        }
        //decode order data
        $order_data = json_decode($pfDeployPackage->fields['json'], true);

        /* TODO:
        * This has to be done properly in each corresponding classes.
        * Meanwhile, I just split the data to rebuild a proper and compliant JSON
        */
        $order_job = $order_data['jobs'];
        //add uniqid to response data
        $order_job['uuid'] = $taskjobstate->fields['uniqid'];

        /* TODO:
        * Orders should only contain job data and associatedFiles should be retrieved from the
        * list inside Orders data like the following :
        *
        * $order_files = []
        * foreach ($order_job["associatedFiles"] as $hash) {
        *    if (!isset($order_files[$hash]) {
        *       $order_files[$hash] = PluginGlpiinventoryDeployFile::getByHash($hash);
        *       $order_files[$hash]['mirrors'] = $mirrors
        *    }
        * }
        */
        $order_files = $order_data['associatedFiles'];

        //Add mirrors to associatedFiles
        $mirrors = PluginGlpiinventoryDeployMirror::getList(
            $taskjobstate->fields['agents_id']
        );
        foreach ($order_files as $hash => $params) {
            $order_files[$hash]['mirrors'] = $mirrors;
            $manifest = PLUGIN_GLPI_INVENTORY_MANIFESTS_DIR . $hash;
            $order_files[$hash]['multiparts'] = [];
            if (file_exists($manifest)) {
                try {
                    $handle = fopen($manifest, "r");
                    while (($buffer = fgets($handle)) !== false) {
                        $order_files[$hash]['multiparts'][] = trim($buffer);
                    }
                    fclose($handle);
                } catch (FilesystemException $e) {
                    //empty catch
                }
            }
        }
        //Send an empty json dict instead of empty json list
        if (count($order_files) == 0) {
            $order_files = (object) [];
        }

        // Fix some command like : echo "write in file" >> c:\TEMP\HELLO.txt
        if (isset($order_job['actions'])) {
            foreach ($order_job['actions'] as $key => $value) {
                if (isset($value['cmd']) && isset($value['cmd']['exec'])) {
                    $order_job['actions'][$key]['cmd']['exec'] = $value['cmd']['exec'];
                }
            }
        }

        $order = [
            "job"             => $order_job,
            "associatedFiles" => $order_files,
        ];
        return $order;
    }
}
