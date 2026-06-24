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

if (plugin_glpiinventory_script_endswith("taskjob_logs.php")) {
    Session::checkRight('plugin_glpiinventory_task', READ);
}

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

$task_ids = $_POST['task_id'] ?? null;
if ($task_ids !== null) {
    $task_ids = is_array($task_ids) ? $task_ids : [$task_ids];
    $pfTask = new PluginGlpiinventoryTask();
    foreach ($task_ids as $task_id) {
        $task_id = (int) $task_id;
        if ($task_id > 0 && !$pfTask->can($task_id, READ)) {
            throw new AccessDeniedHttpException();
        }
    }
}

$pfTask = new PluginGlpiinventoryTask();
$pfTask->ajaxGetJobLogs($_POST);
