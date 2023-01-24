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

include("../../../inc/includes.php");

Session::checkRight('plugin_glpiinventory_package', READ);

session_write_close(); // unlock session to ensure GLPI is still usable while huge file downloads is done in background

$deployfile_id = (int)($_GET['deployfile_id'] ?? 0);

$deploy = new PluginGlpiinventoryDeployFile();
if ($deployfile_id > 0 && $deploy->getFromDB($deployfile_id)) {
    if ($deploy->checkPresenceFile($deploy->fields['sha512'])) {
        //get all repository file path
        $part_path = $deploy->getFilePath($deploy->fields['sha512']);
        $mimetype = $deploy->fields['mimetype'];
        $filesize = $deploy->fields['filesize'];
        $filename = $deploy->fields['name'];

        if ($filename != '' && $part_path !== false && count($part_path)) {
            // Make sure there is nothing in the output buffer (In case stuff was added by core or misbehaving plugin).
            // If there is any extra data, the sent file will be corrupted.
            // 1. Turn off any extra buffering level. Keep one buffering level if PHP output_buffering directive is not "off".
            $ob_config = ini_get('output_buffering');
            $max_buffering_level = $ob_config !== false && (strtolower($ob_config) === 'on' || (is_numeric($ob_config) && (int)$ob_config > 0))
                ? 1
                : 0;
            while (ob_get_level() > $max_buffering_level) {
                ob_end_clean();
            }
            // 2. Clean any buffered output in remaining level (output_buffering="on" case).
            if (ob_get_level() > 0) {
                ob_clean();
            }

            header('Content-Description: File Transfer');
            header('Content-Type: ' . ($mimetype ?: 'application/octet-stream'));
            header('Content-Disposition: attachment; filename=' . basename($filename));
            header('Content-Transfer-Encoding: binary');
            header_remove('Pragma');
            header('Cache-Control: no-store');
            header('Content-Length: ' . $filesize);

            foreach ($part_path as $key => $path) {
                readgzfile($path);
            }
        } else {
            Html::displayErrorAndDie(__('An error occurs', 'glpiinventory'), true);
        }
    } else {
        Html::displayErrorAndDie(__('File not found', 'glpiinventory'), true); // Not found
    }
} else {
    Html::displayErrorAndDie(__('File not found', 'glpiinventory'), true); // Not found
}
