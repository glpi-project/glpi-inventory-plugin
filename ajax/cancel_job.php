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
use Glpi\Exception\Http\NotFoundHttpException;

Session::checkRight('plugin_glpiinventory_task', PURGE);

header("Content-Type: text/json; charset=UTF-8");
Html::header_nocache();

$jobstate_id = (int)($_POST['jobstate_id'] ?? 0);
if ($jobstate_id <= 0) {
    throw new BadRequestHttpException();
}

$jobstate = new PluginGlpiinventoryTaskjobstate();
if (!$jobstate->getFromDB($jobstate_id)) {
    throw new NotFoundHttpException();
}

$jobs_id = (int)$jobstate->fields['plugin_glpiinventory_taskjobs_id'];
$job = new PluginGlpiinventoryTaskjob();
if (!$job->can($jobs_id, PURGE)) {
    throw new AccessDeniedHttpException();
}

$job->delete(['id' => $jobs_id], true);
