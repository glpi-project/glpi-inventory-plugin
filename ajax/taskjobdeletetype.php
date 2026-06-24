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
use Glpi\Exception\Http\BadRequestHttpException;

if (plugin_glpiinventory_script_endswith("taskjobdeletetype.php")) {
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkRight('plugin_glpiinventory_task', UPDATE);

$taskjobs_id = (int) filter_input(INPUT_POST, "taskjobs_id");
if ($taskjobs_id <= 0) {
    throw new BadRequestHttpException();
}

$pfTaskjob = new PluginGlpiinventoryTaskjob();
if (!$pfTaskjob->can($taskjobs_id, UPDATE)) {
    throw new AccessDeniedHttpException();
}

$pfTaskjob->deleteitemtodefatc(
    filter_input(INPUT_POST, "type"),
    filter_input(INPUT_POST, filter_input(INPUT_POST, "type") . 'item'),
    $taskjobs_id
);
