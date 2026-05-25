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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Inventory\Conf;
use Glpi\Inventory\Request;

$conf = new Conf();
if ($conf->enabled_inventory != 1) {
    throw new AccessDeniedHttpException("Inventory is disabled");
}

$inventory_request = new Request();
$inventory_request->handleHeaders();
$inventory_request->handleContentType('application/json');

if (PluginGlpiinventoryToolbox::authInventory($inventory_request)) {
    $collect = new PluginGlpiinventoryCollect();
    $response = $collect->communication(
        filter_input(INPUT_GET, "action"),
        filter_input(INPUT_GET, "machineid"),
        filter_input(INPUT_GET, "uuid")
    );
    $inventory_request->addToResponse((array) $response);
}

http_response_code($inventory_request->getHttpResponseCode());
$headers = $inventory_request->getHeaders(true);
foreach ($headers as $key => $value) {
    header(sprintf('%s: %s', $key, $value));
}

echo $inventory_request->getResponse();
