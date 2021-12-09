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
    * __contruct function used to initialize protected message variable
    */
    function __construct()
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
    function getMessage()
    {
        return $this->message;
    }


   /**
    * Set XML message
    *
    * @param string $message XML in string format
    */
    function setMessage($message)
    {
       // avoid xml warnings
        $this->message = @simplexml_load_string(
            $message,
            'SimpleXMLElement',
            LIBXML_NOCDATA
        );
    }


   /**
    * Send response to agent, using given compression algorithm
    *
    * @param string $compressmode compressed mode: none|zlib|deflate|gzip
    */
    function sendMessage($compressmode = 'none')
    {

        if (!$this->message) {
            return;
        }

        switch ($compressmode) {
            case 'none':
                header("Content-Type: application/xml");
                echo PluginGlpiinventoryToolbox::formatXML($this->message);
                break;

            case 'zlib':
               // rfc 1950
                header("Content-Type: application/x-compress-zlib");
                echo gzcompress(
                    PluginGlpiinventoryToolbox::formatXML($this->message)
                );
                break;

            case 'deflate':
               // rfc 1951
                header("Content-Type: application/x-compress-deflate");
                echo gzdeflate(
                    PluginGlpiinventoryToolbox::formatXML($this->message)
                );
                break;

            case 'gzip':
               // rfc 1952
                header("Content-Type: application/x-compress-gzip");
                echo gzencode(
                    PluginGlpiinventoryToolbox::formatXML($this->message)
                );
                break;
        }
    }


   /**
    * If extra-debug is active, write log
    *
    * @param string $p_logs log message to write
    */
    static function addLog($p_logs)
    {

        if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            if (PluginGlpiinventoryConfig::isExtradebugActive()) {
                file_put_contents(
                    GLPI_LOG_DIR . '/pluginGlpiinventory-communication.log',
                    "\n" . time() . ' : ' . $p_logs,
                    FILE_APPEND
                );
            }
        }
    }



   /**
    * Get all tasks prepared for the agent
    *
    * @param integer $agent_id id of the agent
    */
    function getTaskAgent($agent_id)
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
                    $className != "PluginGlpiinventoryInventoryComputerESX"
                    && $className != "PluginGlpiinventoryDeployCommon"
                    && $className != "PluginGlpiinventoryCollect"
                ) {
                    $class = new $className();
                    $run_response = $class->run($jobstate);
                    $response[] = $run_response;
                }
            }
        }

        return $response;
    }
}
