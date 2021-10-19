#!/usr/bin/php
<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

$doc = <<<DOC
get_agent_jobs.php

Usage:
   get_agent_jobs.php [-h | -q | -d ] [--methods=methods] [<device_ids>...]

-h, --help     show this help
-q, --quiet    run quietly
-d, --debug    display more execution messages
device_ids     the agent's device_ids registered in GLPI
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

$agent = new PluginGlpiinventoryAgent();
$computer = new Computer();


$task = new PluginGlpiinventoryTask();
$staticmisc_methods = PluginGlpiinventoryStaticmisc::getmethods();

$methods = [];
foreach ($staticmisc_methods as $method) {
   $methods[$method['method']] = $method['method'];
}

$device_ids = [];

if (count($args['<device_ids>']) == 0) {
   $agents = array_values($agent->find());
   $randid = rand(0, count($agents));
   $device_ids = [$agents[$randid]['device_id']];
} else {
   $device_ids = $args['<device_ids>'];
}

//$logger->debug($device_ids);

foreach ($device_ids as $device_id) {
   $logger->info("Get prepared jobs for Agent '$device_id'");
   //   $jobstates = $task->getTaskjobstatesForAgent($device_id, $methods, array('read_only'=>true));
   //   $jobstates = $task->getTaskjobstatesForAgent($device_id, $methods);
   $time = microtime(true);
   file_get_contents("http://glpi.kroy-laptop.sandbox/glpi/plugins/glpiinventory/b/deploy/?action=getJobs&machineid=".$device_id);
   $time = microtime(true) - $time;
   $logger->info("Get prepared jobs for Agent '$device_id' : $time s");
}
