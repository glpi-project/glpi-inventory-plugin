#!/usr/bin/php
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

$doc = <<<DOC
get_job_logs.php

Usage:
   get_job_logs.php [-h | -q | -d ] [-m methods] [-t task_ids]

-h, --help     Show this help
-q, --quiet    Run quietly
-d, --debug    Show informations.

-m, --methods=methods   Show only tasks defined with a list of methods (separated by commas).
-t, --tasks=task_ids    Filter logs by tasks (separated by commas)
DOC;

chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

include ("../../../inc/includes.php");

include ("./docopt.php");

require ("./logging.php");

/**
 * Process arguments passed to the script
 */

$docopt = new \Docopt\Handler();
$args = $docopt->handle($doc);

$logger = new Logging();
$logger->setLevelFromArgs($args['--quiet'], $args['--debug']);

$logger->debug($args);

/*
 * Get Running Tasks
 */

$pfTask = new PluginGlpiinventoryTask();
$logs = $pfTask->getJoblogs();
$logger->info($logs);
