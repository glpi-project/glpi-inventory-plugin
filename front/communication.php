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

if (!defined('GLPI_ROOT')) {
    include_once("../../../inc/includes.php");
}

if (!class_exists("PluginGlpiinventoryConfig")) {
    header("Content-Type: application/xml");
    echo "<?xml version='1.0' encoding='UTF-8'?>
<REPLY>
   <ERROR>Plugin GLPI Inventory not installed!</ERROR>
</REPLY>";
    session_destroy();
    exit();
}

if (isset($_GET['action']) && isset($_GET['machineid'])) {
    ini_set("memory_limit", "-1");
    ini_set("max_execution_time", "0");
    ini_set('display_errors', 1);

    if (session_id() == "") {
        session_start();
    }

    $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;
    if (!isset($_SESSION['glpilanguage'])) {
        $_SESSION['glpilanguage'] = 'fr_FR';
    }
    $_SESSION['glpi_glpiinventory_nolock'] = true;
    ini_set('display_errors', 'On');
    error_reporting(E_ALL | E_STRICT);
    $_SESSION['glpi_use_mode'] = 0;
    $_SESSION['glpiparententities'] = '';
    $_SESSION['glpishowallentities'] = true;

    header("server-type: glpi/glpiinventory " . PLUGIN_GLPIINVENTORY_VERSION);

    PluginGlpiinventoryCommunicationRest::handleFusionCommunication();
} else {

    if (!isset($rawdata)) {
        $rawdata = file_get_contents("php://input");
     }

    $compressmode = '';
    $content_type = filter_input(INPUT_SERVER, "CONTENT_TYPE");
    if (!empty($xml)) {
            $compressmode = 'none';
    } else if ($content_type == "application/x-compress-zlib") {
            $xml = gzuncompress($rawdata);
            $compressmode = "zlib";
    } else if ($content_type == "application/x-compress-gzip") {
            $xml = $pfToolbox->gzdecode($rawdata);
            $compressmode = "gzip";
    } else if ($content_type == "application/xml") {
            $xml = $rawdata;
            $compressmode = 'none';
    } else {
        // try each algorithm successively
        if (($xml = gzuncompress($rawdata))) {
            $compressmode = "zlib";
        } else if (($xml = $pfToolbox->gzdecode($rawdata))) {
            $compressmode = "gzip";
        } else if (($xml = gzinflate (substr($rawdata, 2)))) {
            // accept deflate for OCS agent 2.0 compatibility,
            // but use zlib for answer
            if (strstr($xml, "<QUERY>PROLOG</QUERY>") AND !strstr($xml, "<TOKEN>")) {
                $compressmode = "zlib";
            } else {
                $compressmode = "deflate";
            }
        } else {
            $xml = $rawdata;
            $compressmode = 'none';
        }
    }

    // Check XML integrity
    $pxml = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    if (!$pxml) {
        $pxml = @simplexml_load_string(mb_convert_encoding($xml, 'UTF-8', mb_list_encodings()), 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($pxml) {
            $xml = mb_convert_encoding($xml, 'UTF-8', mb_list_encodings());
        }
    }

    $communication  = new PluginGlpiinventoryCommunication();
    if (!$pxml) {

            $communication->setMessage("<?xml version='1.0' encoding='UTF-8'?>
<REPLY>
<ERROR>XML not well formed!</ERROR>
</REPLY>");
            $communication->sendMessage($compressmode);
        return;
    }

    $array = json_decode(json_encode($pxml),TRUE);

    if(!isset($array['CONTENT']['DEVICE']['ERROR'])){
        //no error redirect inventory to GLPI
        include_once  GLPI_ROOT . '/front/inventory.php';
    } else {
        $communication->setMessage("<?xml version='1.0' encoding='UTF-8'?>
<REPLY>
<RESPONSE>SEND</RESPONSE>
</REPLY>");
        $communication->sendMessage($compressmode);
        return;
    }

}

session_destroy();
