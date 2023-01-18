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
global $CFG_GLPI;

$filename = null;
if (isset($_GET['filename'])) {
    $filename = $_GET['filename'];
}

$mimetype = null;
if (isset($_GET['mimetype'])) {
    $mimetype = urldecode($_GET['mimetype']);
}

$deploy = new PluginGlpiinventoryDeployFile();
if ($deploy->getFromDBByCrit(['name' => $filename, 'mimetype' => $mimetype ])) {
    if ($deploy->checkPresenceFile($deploy->fields['sha512'])) {
        //get all repository file path
        $path = $deploy->getFilePath($deploy->fields['sha512']);
        if ($mimetype != null && $filename != null && count($path)) {
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

            // don't download picture files, see them inline
            $attachment = "";
            // if not begin 'image/'
            if (
                strncmp($mimetype, 'image/', 6) !== 0
                && $mimetype != 'application/pdf'
                // svg vector of attack, force attachment
                // see https://github.com/glpi-project/glpi/issues/3873
                || $mimetype == 'image/svg+xml'
            ) {
                $attachment = ' attachment;';
            }

            header('Content-Description: File Transfer');
            header('Content-Type: ' . $mimetype);
            header('Content-Disposition: ' . $attachment . ' filename=' . basename($filename));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header_remove('Pragma');
            header('Cache-Control: private');

            $stdout = fopen('php://output', 'w');
            foreach ($path as $key => $value) {
                $fdPart = gzopen($value, 'r');
                fwrite($stdout, gzpassthru($fdPart));
                gzclose($fdPart);
            }

            flush();
            readfile("php://output");
            fclose($stdout);
        } else {
            Html::displayErrorAndDie(__('Unauthorized access to this file'), true);
        }
    } else {
        Html::displayErrorAndDie(__('File not found'), true); // Not found
    }
} else {
    Html::displayErrorAndDie(__('File not found'), true); // Not found
}
