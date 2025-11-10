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


use Glpi\Event;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Safe\Exceptions\FilesystemException;

use function Safe\filesize;
use function Safe\fopen;
use function Safe\fread;
use function Safe\realpath;

Session::checkLoginUser();

$docDir = GLPI_PLUGIN_DOC_DIR . '/glpiinventory';

if (isset($_GET['file'])) {
    $filename = $_GET['file'];

    // Security test : document in $docDir
    if (
        !str_starts_with(realpath($filename), realpath(GLPI_PLUGIN_DOC_DIR))
    ) {
        Event::log(
            $filename,
            "sendFile",
            1,
            "security",
            $_SESSION["glpiname"] . " tries to get a non standard file."
        );
        throw new AccessDeniedHttpException();
    }

    $file = $docDir . '/' . $filename;
    if (!file_exists($file)) {
        throw new NotFoundHttpException(
            sprintf(
                'File %1$s does not exist',
                $filename
            )
        );
    } else {
        // Now send the file with header() magic
        header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
        header('Pragma: private'); /// IE BUG + SSL
        header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
        header("Content-disposition: filename=\"$filename\"");

        try {
            $f = fopen($file, "r");
            // for \x00 not to become \0
            $fsize = filesize($file);
            if ($fsize) {
                echo fread($f, filesize($file));
            }
        } catch (FilesystemException $e) {
            throw new NotFoundHttpException(
                sprintf(
                    'Error opening %1$s',
                    $filename
                ),
                $e
            );
        }
    }
}
