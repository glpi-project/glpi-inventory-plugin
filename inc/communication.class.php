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

use function Safe\simplexml_load_string;

/**
 * Manage communication with agents using XML
 */
class PluginGlpiinventoryCommunication
{
    /**
     * Define message variable
     *
     * @var SimpleXMLElement
     */
    protected $message;


    /**
     * __construct function used to initialize protected message variable
     */
    public function __construct()
    {
        $this->message = new SimpleXMLElement(
            "<?xml version='1.0' encoding='UTF-8'?><REPLY></REPLY>"
        );
        PluginGlpiinventoryToolbox::logIfExtradebug(
            'pluginGlpiinventory-communication',
            'New PluginGlpiinventoryCommunication object.'
        );
    }


    /**
     * Get readable XML message (add carriage returns)
     *
     * @return object SimpleXMLElement
     */
    public function getMessage()
    {
        return $this->message;
    }


    /**
     * Set XML message
     *
     * @param string $message XML in string format
     */
    public function setMessage($message): void
    {
        // avoid xml warnings
        $this->message = @simplexml_load_string(
            $message,
            'SimpleXMLElement',
            LIBXML_NOCDATA
        );
    }

    /**
     * If extra-debug is active, write log
     *
     * @param string $p_logs log message to write
     */
    public static function addLog($p_logs): void
    {
        PluginGlpiinventoryToolbox::logIfExtradebug(
            GLPI_LOG_DIR . '/pluginGlpiinventory-communication.log',
            sprintf("\n%s: %s", time(), $p_logs)
        );
    }



    /**
     * Get all tasks prepared for the agent
     *
     * @param int $agent_id id of the agent
     *
     * @return array<int,array<string,mixed>>
     */
    public function getTaskAgent($agent_id): array
    {
        $response = [];
        $pfTask = new PluginGlpiinventoryTask();

        /**
         * TODO: the following must be definitely done differently !
         * (... but i'm kind in a hurry right now ;-) )
         */
        $methods = [];
        $classnames = [];
        foreach (PluginGlpiinventoryStaticmisc::getmethods() as $method) {
            if (isset($method['classname'])) {
                $methods[] = $method['method'];
                $classnames[$method['method']] = $method['classname'];
            }
        }

        $jobstates = $pfTask->getTaskjobstatesForAgent($agent_id, $methods);
        foreach ($jobstates as $jobstate) {
            $className = $classnames[$jobstate->method];
            if (class_exists($className)) {
                /*
                 * TODO: check if use_rest is enabled in Staticmisc::get_methods.
                 * Also, this get_methods function need to be reviewed
                 */
                if (
                    !is_a($className, PluginGlpiinventoryInventoryComputerESX::class, true)
                    && !is_a($className, PluginGlpiinventoryDeployCommon::class, true)
                    && !is_a($className, PluginGlpiinventoryCollect::class, true)
                ) {
                    $class = new $className(); // @phpstan-ignore glpi.forbidDynamicInstantiation (not a GLPI framework object, see no way to check properly what is expected)
                    $run_response = $class->run($jobstate);
                    $response[] = $run_response;
                }
            }
        }

        return $response;
    }
}
