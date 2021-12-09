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

/**
 * Add search options for GLPI objects
 *
 * @param string $itemtype
 * @return array
 */
function plugin_glpiinventory_getAddSearchOptions($itemtype)
{

    $sopt = [];
    if ($itemtype == 'Computer') {
        $sopt[5164]['table']         = "glpi_plugin_glpiinventory_agentmodules";
        $sopt[5164]['field']         = "DEPLOY";
        $sopt[5164]['linkfield']     = "DEPLOY";
        $sopt[5164]['name']          = __('Module', 'glpiinventory') . "-" . __('Deploy', 'glpiinventory');
        $sopt[5164]['datatype']      = 'bool';
        $sopt[5164]['massiveaction'] = false;

        $sopt[5165]['table']         = "glpi_plugin_glpiinventory_agentmodules";
        $sopt[5165]['field']         = "WAKEONLAN";
        $sopt[5165]['linkfield']     = "WAKEONLAN";
        $sopt[5165]['name']          = __('Module', 'glpiinventory') . "-" . __('WakeOnLan', 'glpiinventory');
        $sopt[5165]['datatype']      = 'bool';
        $sopt[5165]['massiveaction'] = false;

        $sopt[5166]['table']         = "glpi_plugin_glpiinventory_agentmodules";
        $sopt[5166]['field']         = "INVENTORY";
        $sopt[5166]['linkfield']     = "INVENTORY";
        $sopt[5166]['name']          = __('Module', 'glpiinventory') . "-" . __('Local inventory', 'glpiinventory');
        $sopt[5166]['datatype']      = 'bool';
        $sopt[5166]['massiveaction'] = false;

        $sopt[5167]['table']         = "glpi_plugin_glpiinventory_agentmodules";
        $sopt[5167]['field']         = "InventoryComputerESX";
        $sopt[5167]['linkfield']     = "InventoryComputerESX";
        $sopt[5167]['name']          = __('Module', 'glpiinventory') . "-" . __('ESX/VMWare', 'glpiinventory');
        $sopt[5167]['datatype']      = 'bool';
        $sopt[5167]['massiveaction'] = false;

        $sopt[5168]['table']         = "glpi_plugin_glpiinventory_agentmodules";
        $sopt[5168]['field']         = "NETWORKINVENTORY";
        $sopt[5168]['linkfield']     = "NETWORKINVENTORY";
        $sopt[5168]['name']          = __('Module', 'glpiinventory') . "-" . __('Network inventory', 'glpiinventory');
        $sopt[5168]['datatype']      = 'bool';
        $sopt[5168]['massiveaction'] = false;

        $sopt[5169]['table']         = "glpi_plugin_glpiinventory_agentmodules";
        $sopt[5169]['field']         = "NETWORKDISCOVERY";
        $sopt[5169]['linkfield']     = "NETWORKDISCOVERY";
        $sopt[5169]['name']          = __('Module', 'glpiinventory') . "-" . __('Network discovery', 'glpiinventory');
        $sopt[5169]['datatype']      = 'bool';
        $sopt[5169]['massiveaction'] = false;

        $sopt[5170]['table']         = "glpi_plugin_glpiinventory_agentmodules";
        $sopt[5170]['field']         = "Collect";
        $sopt[5170]['linkfield']     = "Collect";
        $sopt[5170]['name']          = __('Module', 'glpiinventory') . "-" . __('Collect', 'glpiinventory');
        $sopt[5170]['datatype']      = 'bool';
        $sopt[5170]['massiveaction'] = false;

        $sopt[5171]['name']          = __('Static group', 'glpiinventory');
        $sopt[5171]['table']         = getTableForItemType('PluginGlpiinventoryDeployGroup');
        $sopt[5171]['massiveaction'] = false;
        $sopt[5171]['field']         = 'name';
        $sopt[5171]['forcegroupby']  = true;
        $sopt[5171]['usehaving']     = true;
        $sopt[5171]['datatype']      = 'dropdown';
        $sopt[5171]['joinparams']    = ['beforejoin'
                                       => ['table'      => 'glpi_plugin_glpiinventory_deploygroups_staticdatas',
                                                'joinparams' => ['jointype'          => 'itemtype_item',
                                                                        'specific_itemtype' => 'Computer']]];
    }

    if ($itemtype == 'Computer') {
        $sopt += PluginGlpiinventoryCollect::getSearchOptionsToAdd();
    }

    return $sopt;
}


/**
 * Manage search give items (display information in the search page)
 *
 * @global array $CFG_GLPI
 * @param string $type
 * @param integer $id
 * @param array $data
 * @param integer $num
 * @return string
 */
function plugin_glpiinventory_giveItem($type, $id, $data, $num)
{
    global $CFG_GLPI, $DB;

    $searchopt = &Search::getOptions($type);
    $table = $searchopt[$id]["table"];
    $field = $searchopt[$id]["field"];

    switch ($table . '.' . $field) {
        case "glpi_plugin_glpiinventory_taskjobs.status":
            $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
            return $pfTaskjobstate->stateTaskjob($data['raw']['id'], '200', 'htmlvar', 'simple');

        case "glpi_plugin_glpiinventory_credentials.itemtype":
            if ($label = PluginGlpiinventoryCredential::getLabelByItemtype($data['raw']['ITEM_' . $num])) {
                return $label;
            } else {
                return '';
            }
            break;

        case 'glpi_plugin_glpiinventory_taskjoblogs.state':
            $pfTaskjoblog = new PluginGlpiinventoryTaskjoblog();
            return $pfTaskjoblog->getDivState($data['raw']['ITEM_' . $num]);

        case 'glpi_plugin_glpiinventory_taskjoblogs.comment':
            $comment = $data['raw']['ITEM_' . $num];
            return PluginGlpiinventoryTaskjoblog::convertComment($comment);

        case 'glpi_plugin_glpiinventory_taskjobstates.plugin_glpiinventory_agents_id':
            $agent = new Agent();
            $agent->getFromDB($data['raw']['ITEM_' . $num]);
            if (!isset($agent->fields['name'])) {
                return NOT_AVAILABLE;
            }
            $itemtype = PluginGlpiinventoryTaskjoblog::getStateItemtype($data['raw']['ITEM_0']);
            if ($itemtype == 'PluginGlpiinventoryDeployPackage') {
                $computer = new Computer();
                $computer->getFromDB($agent->fields['items_id']);
                return $computer->getLink(1);
            }
            return $agent->getLink(1);
    }

    if ($table == "glpi_plugin_glpiinventory_agentmodules") {
        if ($type == 'Computer') {
            $pfAgentmodule = new PluginGlpiinventoryAgentmodule();
            $a_modules = $pfAgentmodule->find(['modulename' => $field]);
            $data2 = current($a_modules);
            if ($table . "." . $field == "glpi_plugin_glpiinventory_agentmodules." . $data2['modulename']) {
                if (strstr($data['raw']["ITEM_" . $num . "_0"], '"' . $data['raw']["ITEM_" . $num . "_1"] . '"')) {
                    if ($data['raw']['ITEM_' . $num] == '0') {
                        return Dropdown::getYesNo(true);
                    } else {
                        return Dropdown::getYesNo(false);
                    }
                }
                return Dropdown::getYesNo($data['raw']['ITEM_' . $num]);
            }
        } else {
            $pfAgentmodule = new PluginGlpiinventoryAgentmodule();
            $a_modules = $pfAgentmodule->find(['modulename' => $field]);
            foreach ($a_modules as $data2) {
                if ($table . "." . $field == "glpi_plugin_glpiinventory_agentmodules." . $data2['modulename']) {
                    if (strstr($data['raw']["ITEM_" . $num . "_0"], '"' . $data['raw']['id'] . '"')) {
                        if ($data['raw']['ITEM_' . $num] == 0) {
                             return Dropdown::getYesNo('1');
                        } else {
                            return Dropdown::getYesNo('0');
                        }
                    }
                    return Dropdown::getYesNo($data['raw']['ITEM_' . $num]);
                }
            }
        }
    }

    switch ($type) {
       // * range IP list (plugins/fusinvsnmp/front/iprange.php)
        case 'PluginGlpiinventoryIPRange':
            switch ($table . '.' . $field) {
                // ** Display entity name
                case "glpi_entities.name":
                    if ($data['raw']["ITEM_$num"] == '') {
                        $out = Dropdown::getDropdownName("glpi_entities", $data['raw']["ITEM_$num"]);
                        return "<center>" . $out . "</center>";
                    }
                    break;
            }
            break;
    }

    return "";
}


/**
 * Manage search options values
 *
 * @global object $DB
 * @param object $item
 * @return boolean
 */
function plugin_glpiinventory_searchOptionsValues($item)
{
    global $DB;

    if (
        $item['searchoption']['table'] == 'glpi_plugin_glpiinventory_taskjoblogs'
           and $item['searchoption']['field'] == 'state'
    ) {
        $pfTaskjoblog = new PluginGlpiinventoryTaskjoblog();
        $elements = $pfTaskjoblog->dropdownStateValues();
        Dropdown::showFromArray($item['name'], $elements, ['value' => $item['value']]);
        return true;
    } elseif (
        $item['searchoption']['table'] == 'glpi_plugin_glpiinventory_taskjobstates'
           and $item['searchoption']['field'] == 'uniqid'
    ) {
        $elements = [];
        $iterator = $DB->request([
         'FROM'      => $item['searchoption']['table'],
         'GROUPBY'   => 'uniqid',
         'ORDER'     => 'uniqid'
        ]);
        foreach ($iterator as $data) {
            $elements[$data['uniqid']] = $data['uniqid'];
        }
        Dropdown::showFromArray($item['name'], $elements, ['value' => $item['value']]);
        return true;
    }
}


/**
 * Define Dropdown tables to be manage in GLPI
 *
 * @return array
 */
function plugin_glpiinventory_getDropdown()
{
    return [];
}


/**
 * Manage GLPI cron
 *
 * @return integer
 */
function cron_plugin_glpiinventory()
{
   //   TODO :Disable for the moment (may be check if functions is good or not
   //   $ptud = new PluginGlpiinventoryUnmanaged;
   //   $ptud->cleanOrphelinsConnections();
   //   $ptud->FusionUnknownKnownDevice();
   //   TODO : regarder les 2 lignes juste en dessous !!!!!
   //   #Clean server script processes history
   //   $pfisnmph = new PluginGlpiinventoryNetworkPortLog;
   //   $pfisnmph->cronCleanHistory();

    return 1;
}


/**
 * Manage the installation process
 *
 * @return boolean
 */
function plugin_glpiinventory_install()
{
    ini_set("max_execution_time", "0");

    if (basename(filter_input(INPUT_SERVER, "SCRIPT_NAME")) != "cli_install.php") {
        if (!isCommandLine()) {
            Html::header(__('Setup', 'glpiinventory'), filter_input(INPUT_SERVER, "PHP_SELF"), "config", "plugins");
        }
        $migrationname = 'Migration';
    } else {
        $migrationname = 'CliMigration';
    }

    require_once(PLUGIN_GLPI_INVENTORY_DIR . "/install/update.php");
    $version_detected = pluginGlpiinventoryGetCurrentVersion();

    if (
        !defined('FORCE_INSTALL')
        &&
        isset($version_detected)
        && (
         defined('FORCE_UPGRADE')
         || (
            $version_detected != '0'
         )
        )
    ) {
       // note: if version detected = version found can have problem, so need
       //       pass in upgrade to be sure all OK
        pluginGlpiinventoryUpdate($version_detected, $migrationname);
        require_once PLUGIN_GLPI_INVENTORY_DIR . '/install/update.native.php';
        $version_detected = pluginGlpiinventoryGetCurrentVersion();
        pluginGlpiinventoryUpdateNative($version_detected, $migrationname);
    } else {
        require_once(PLUGIN_GLPI_INVENTORY_DIR . "/install/install.php");
        pluginGlpiinventoryInstall(PLUGIN_GLPI_INVENTORY_VERSION, $migrationname);
    }
    return true;
}


/**
 * Manage the uninstallation of the plugin
 *
 * @return boolean
 */
function plugin_glpiinventory_uninstall()
{
    require_once(PLUGIN_GLPI_INVENTORY_DIR . "/inc/setup.class.php");
    require_once(PLUGIN_GLPI_INVENTORY_DIR . "/inc/profile.class.php");
    return PluginGlpiinventorySetup::uninstall();
}


/**
 * Add massive actions to GLPI itemtypes
 *
 * @param string $type
 * @return array
 */
function plugin_glpiinventory_MassiveActions($type)
{

    $sep = MassiveAction::CLASS_ACTION_SEPARATOR;
    $ma = [];

    switch ($type) {
        case "Computer":
            if (Session::haveRight('plugin_glpiinventory_task', UPDATE)) {
                $ma["PluginGlpiinventoryTask" . $sep . "target_task"]
                 = __('Target a task', 'glpiinventory');
            }
            if (Session::haveRight('plugin_glpiinventory_group', UPDATE)) {
                $ma["PluginGlpiinventoryDeployGroup" . $sep . "add_to_static_group"]
                = __('Add to static group', 'glpiinventory');
            }
            break;
    }
    return $ma;
}


/**
 * Manage massice actions fields display
 *
 * @param array $options
 * @return boolean
 */
function plugin_glpiinventory_MassiveActionsFieldsDisplay($options = [])
{

    $table = $options['options']['table'];
    $field = $options['options']['field'];
    $linkfield = $options['options']['linkfield'];

    switch ($table . "." . $field) {
        case 'glpi_entities.name':
            if (Session::isMultiEntitiesMode()) {
                Dropdown::show(
                    "Entities",
                    ['name' => "entities_id",
                    'value' => $_SESSION["glpiactive_entity"]]
                );
            }
            return true;
    }
    return false;
}


/**
 * Manage Add select to search query
 *
 * @param string $type
 * @param integer $id
 * @param integer $num
 * @return string
 */
function plugin_glpiinventory_addSelect($type, $id, $num)
{

    $searchopt = &Search::getOptions($type);
    $table = $searchopt[$id]["table"];
    $field = $searchopt[$id]["field"];

    switch ($type) {
        case 'Computer':
            $a_agent_modules = PluginGlpiinventoryAgentmodule::getModules();
            foreach ($a_agent_modules as $module) {
                if ($table . "." . $field == 'glpi_plugin_glpiinventory_agentmodules.' . $module) {
                    return " `FUSION_" . $module . "`.`is_active` AS ITEM_$num, " .
                          "`FUSION_" . $module . "`.`exceptions`  AS ITEM_" . $num . "_0, " .
                          "`agent" . strtolower($module) . "`.`id`  AS ITEM_" . $num . "_1, ";
                }
            }
            break;
    }
    return "";
}


/**
 * Manage group by in search query
 *
 * @param string $type
 * @return boolean
 */
function plugin_glpiinventory_forceGroupBy($type)
{
    return false;
}


/**
 * Manage left join in search query
 *
 * @param string $itemtype
 * @param string $ref_table
 * @param string $new_table
 * @param string $linkfield
 * @param string $already_link_tables
 * @return string
 */
function plugin_glpiinventory_addLeftJoin(
    $itemtype,
    $ref_table,
    $new_table,
    $linkfield,
    &$already_link_tables
) {

    switch ($itemtype) {
        case 'PluginGlpiinventoryTaskjoblog':
           //         echo $new_table.".".$linkfield."<br/>";
            $taskjob = 0;
            $already_link_tables_tmp = $already_link_tables;
            array_pop($already_link_tables_tmp);
            foreach ($already_link_tables_tmp as $tmp_table) {
                if (
                    $tmp_table == "glpi_plugin_glpiinventory_tasks"
                    or $tmp_table == "glpi_plugin_glpiinventory_taskjobs"
                    or $tmp_table == "glpi_plugin_glpiinventory_taskjobstates"
                ) {
                    $taskjob = 1;
                }
            }

            switch ($new_table . "." . $linkfield) {
                case 'glpi_plugin_glpiinventory_tasks.plugin_glpiinventory_tasks_id':
                    $ret = '';
                    if ($taskjob == '0') {
                        $ret = ' LEFT JOIN `glpi_plugin_glpiinventory_taskjobstates` ON
                     (`plugin_glpiinventory_taskjobstates_id` = ' .
                          '`glpi_plugin_glpiinventory_taskjobstates`.`id` )
                  LEFT JOIN `glpi_plugin_glpiinventory_taskjobs` ON
                     (`plugin_glpiinventory_taskjobs_id` = ' .
                          '`glpi_plugin_glpiinventory_taskjobs`.`id` ) ';
                    }
                    $ret .= ' LEFT JOIN `glpi_plugin_glpiinventory_tasks` ON
                  (`plugin_glpiinventory_tasks_id` = `glpi_plugin_glpiinventory_tasks`.`id`) ';
                    return $ret;

                case 'glpi_plugin_glpiinventory_taskjobs.plugin_glpiinventory_taskjobs_id':
                case 'glpi_plugin_glpiinventory_taskjobstates.' .
                    'plugin_glpiinventory_taskjobstates_id':
                    if ($taskjob == '0') {
                        return ' LEFT JOIN `glpi_plugin_glpiinventory_taskjobstates` ON
                     (`plugin_glpiinventory_taskjobstates_id` = ' .
                          '`glpi_plugin_glpiinventory_taskjobstates`.`id` )
                  LEFT JOIN `glpi_plugin_glpiinventory_taskjobs` ON
                     (`plugin_glpiinventory_taskjobs_id` = ' .
                          '`glpi_plugin_glpiinventory_taskjobs`.`id` ) ';
                    }
                    return ' ';
            }
            break;

        case 'PluginGlpiinventoryTask':
            if (
                $new_table . "." . $linkfield == 'glpi_plugin_glpiinventory_taskjoblogs.' .
                 'plugin_glpiinventory_taskjoblogs_id'
            ) {
                return "LEFT JOIN `glpi_plugin_glpiinventory_taskjobs` AS taskjobs
                     ON `plugin_glpiinventory_tasks_id` = `glpi_plugin_glpiinventory_tasks`.`id`
               LEFT JOIN `glpi_plugin_glpiinventory_taskjobstates` AS taskjobstates
                     ON taskjobstates.`id` =
                  (SELECT MAX(`id`)
                     FROM glpi_plugin_glpiinventory_taskjobstates
                   WHERE plugin_glpiinventory_taskjobs_id = taskjobs.`id`
                   ORDER BY id DESC
                   LIMIT 1
                  )
               LEFT JOIN `glpi_plugin_glpiinventory_taskjoblogs`
                  ON `glpi_plugin_glpiinventory_taskjoblogs`.`id` =
                  (SELECT MAX(`id`)
                     FROM `glpi_plugin_glpiinventory_taskjoblogs`
                   WHERE `plugin_glpiinventory_taskjobstates_id`= taskjobstates.`id`
                   ORDER BY id DESC LIMIT 1
                  ) ";
            }
            break;

        case 'Computer':
            $a_agent_modules = PluginGlpiinventoryAgentmodule::getModules();
            foreach ($a_agent_modules as $module) {
                if ($new_table . "." . $linkfield == 'glpi_plugin_glpiinventory_agentmodules.' . $module) {
                    return " LEFT JOIN `glpi_plugin_glpiinventory_agentmodules` AS FUSION_" . $module . "
                             ON FUSION_" . $module . ".`modulename`='" . $module . "'
                          LEFT JOIN `glpi_agents` as agent" . strtolower($module) . "
                             ON (`glpi_computers`.`id`=`agent" . strtolower($module) . "`.`items_id`
                                 AND `agent" . strtolower($module) . "`.`itemtype`='Computer') ";
                }
            }
            break;
    }
    return "";
}


/**
 * Manage order in search query
 *
 * @param string $type
 * @param integer $id
 * @param string $order
 * @param integer $key
 * @return string
 */
function plugin_glpiinventory_addOrderBy($type, $id, $order, $key = 0)
{
    return "";
}


/**
 * Add where in search query
 *
 * @param string $type
 * @return string
 */
function plugin_glpiinventory_addDefaultWhere($type)
{
    if ($type == 'PluginGlpiinventoryTaskjob' && !isAPI()) {
        return " ( select count(*) FROM `glpi_plugin_glpiinventory_taskjobstates`
         WHERE plugin_glpiinventory_taskjobs_id= `glpi_plugin_glpiinventory_taskjobs`.`id`
         AND `state`!='3' )";
    }
}


/**
 * Manage where in search query
 *
 * @param string $link
 * @param string $nott
 * @param string $type
 * @param integer $id
 * @param string $val
 * @return string
 */
function plugin_glpiinventory_addWhere($link, $nott, $type, $id, $val)
{

    $searchopt = &Search::getOptions($type);
    $table = $searchopt[$id]["table"];
    $field = $searchopt[$id]["field"];

    switch ($type) {
        case 'PluginGlpiinventoryTaskjob':
           /*
            * WARNING: The following is some minor hack in order to select a range of ids.
            *
            * More precisely, when using the ID filter, you can now put IDs separated by commas.
            * This is used by the DeployPackage class when it comes to check running tasks on some
            * packages.
            */
            if ($table == 'glpi_plugin_glpiinventory_tasks') {
                if ($field == 'id') {
                   //check if this range is numeric
                    $ids = explode(',', $val);
                    foreach ($ids as $k => $i) {
                        if (!is_numeric($i)) {
                            unset($ids[$k]);
                        }
                    }

                    if (count($ids) >= 1) {
                        return $link . " `$table`.`id` IN (" . implode(',', $ids) . ")";
                    } else {
                        return "";
                    }
                } elseif ($field == 'name') {
                    $val = stripslashes($val);
                   //decode a json query to match task names in taskjobs list
                    $names = json_decode($val);
                    if ($names !== null && is_array($names)) {
                        $names = array_map(
                            function ($a) {
                                return "\"" . $a . "\"";
                            },
                            $names
                        );
                        return $link . " `$table`.`name` IN (" . implode(',', $names) . ")";
                    } else {
                        return "";
                    }
                }
            }
            break;

        case 'PluginGlpiinventoryTaskjoblog':
            if ($field == 'uniqid') {
                return $link . " (`" . $table . "`.`uniqid`='" . $val . "') ";
            }
            break;

       // * Computer List (front/computer.php)
        case 'Computer':
            $a_agent_modules = PluginGlpiinventoryAgentmodule::getModules();
            foreach ($a_agent_modules as $module) {
                if ($table . "." . $field == 'glpi_plugin_glpiinventory_agentmodules.' . $module) {
                    $pfAgentmodule = new PluginGlpiinventoryAgentmodule();
                    $a_modules = $pfAgentmodule->find(['modulename' => $module]);
                    $data = current($a_modules);
                    if (($data['exceptions'] != "[]") and ($data['exceptions'] != "")) {
                        $a_exceptions = importArrayFromDB($data['exceptions']);
                        $current_id = current($a_exceptions);
                        $in = "(";
                        foreach ($a_exceptions as $agent_id) {
                             $in .= $agent_id . ", ";
                        }
                        $in .= ")";
                        $in = str_replace(", )", ")", $in);

                        if ($val != $data['is_active']) {
                            return $link . " (FUSION_" . $module . ".`exceptions` LIKE '%\"" .
                             $current_id . "\"%' ) AND `agent" . strtolower($module) . "`.`id` IN " .
                             $in . " ";
                        } else {
                            return $link . " `agent" . strtolower($module) . "`.`id` NOT IN " . $in . " ";
                        }
                    } else {
                        if ($val != $data['is_active']) {
                            return $link . " (FUSION_" . $module . ".`is_active`!='" .
                              $data['is_active'] . "') ";
                        } else {
                            return $link . " (FUSION_" . $module . ".`is_active`='" .
                             $data['is_active'] . "') ";
                        }
                    }
                }
            }
            break;

       // * range IP list (plugins/fusinvsnmp/front/iprange.php)
        case 'PluginGlpiinventoryIPRange':
            switch ($table . "." . $field) {
               // ** Name of range IP and link to form
                case "glpi_plugin_glpiinventory_ipranges.name":
                    break;

               // ** Agent name associed to IP range and link to agent form
                case "glpi_plugin_fusinvsnmp_agents.id":
                    $ADD = "";
                    if ($nott == "0" && $val == "NULL") {
                        $ADD = " OR $table.name IS NULL";
                    } elseif ($nott == "1" && $val == "NULL") {
                        $ADD = " OR $table.name IS NOT NULL";
                    }
                    return $link . " ($table.name  LIKE '%" . $val . "%' $ADD ) ";
            }
            switch ($table . "." . $field) {
                case "glpi_plugin_fusinvsnmp_agents.plugin_fusinvsnmp_agents_id_query":
                    $ADD = "";
                    if ($nott == "0" && $val == "NULL") {
                        $ADD = " OR $table.name IS NULL";
                    } elseif ($nott == "1" && $val == "NULL") {
                        $ADD = " OR $table.name IS NOT NULL";
                    }
                    return $link . " (gpta.name  LIKE '%" . $val . "%' $ADD ) ";
            }
            break;
    }
    return "";
}


/**
 * Manage pre-item update an item
 *
 * @param object $parm
 */
function plugin_pre_item_update_glpiinventory($parm)
{
    if ($parm->fields['directory'] == 'glpiinventory') {
        $plugin = new Plugin();

        $a_plugins = PluginGlpiinventoryModule::getAll();
        foreach ($a_plugins as $datas) {
            $plugin->unactivate($datas['id']);
        }
    }
}


/**
 * Manage pre-item purge an item
 *
 * @param object $parm
 * @return object
 */
function plugin_pre_item_purge_glpiinventory($parm)
{
    $itemtype = get_class($parm);
    $items_id = $parm->getID();

    switch ($itemtype) {
        case 'Computer':
            $agent        = new Agent();
            $pfTaskjobstate = new PluginGlpiinventoryTaskjobstate();
            if ($agent->getFromDBByCrit(['itemtype' => 'Computer', 'items_id' => $items_id])) {
                $agent_id = $agent->fields['id'];
                // count associated tasks to the agent
                $states = $pfTaskjobstate->find(['agents_id' => $agent_id], [], 1);
                if (count($states) > 0) {
                   // Delete link between computer and agent fusion
                    $agent->update(
                        [
                        'id'           => $agent_id,
                        'items_id' => 0],
                        true
                    );
                } else {
                   // no task associated, purge also agent
                    $agent->delete(['id' => $agent_id], true);
                }
            }

            $clean = [
            'PluginGlpiinventoryCollect_File_Content',
            'PluginGlpiinventoryCollect_Registry_Content',
            'PluginGlpiinventoryCollect_Wmi_Content'
            ];
            foreach ($clean as $obj) {
                $obj::cleanComputer($items_id);
            }
            break;
    }

    $rule = new RuleMatchedLog();
    $rule->deleteByCriteria(['itemtype' => $itemtype, 'items_id' => $items_id]);

    return $parm;
}


/**
 * Manage when purge an item
 *
 * @param object $parm
 * @return object
 */
function plugin_item_purge_glpiinventory($parm)
{
    global $DB;

    switch (get_class($parm)) {
        case 'NetworkPort_NetworkPort':
           // If remove connection of a hub port (unknown device), we must delete this port too
            $NetworkPort = new NetworkPort();
            $NetworkPort_Vlan = new NetworkPort_Vlan();
            $unmanaged = new Unmanaged();
            $networkPort_NetworkPort = new NetworkPort_NetworkPort();

            $a_hubs = [];

            $port_id = $NetworkPort->getContact($parm->getField('networkports_id_1'));
            $NetworkPort->getFromDB($parm->getField('networkports_id_1'));
            if ($NetworkPort->fields['itemtype'] == 'Unmanaged') {
                $unmanaged->getFromDB($NetworkPort->fields['items_id']);
                if ($unmanaged->fields['hub'] == '1') {
                    $a_hubs[$NetworkPort->fields['items_id']] = 1;
                    $NetworkPort->delete($NetworkPort->fields);
                }
            }
            $NetworkPort->getFromDB($port_id);
            if ($port_id) {
                if ($NetworkPort->fields['itemtype'] == 'Unmanaged') {
                    $unmanaged->getFromDB($NetworkPort->fields['items_id']);
                    if ($unmanaged->fields['hub'] == '1') {
                        $a_hubs[$NetworkPort->fields['items_id']] = 1;
                    }
                }
            }
            $port_id = $NetworkPort->getContact($parm->getField('networkports_id_2'));
            $NetworkPort->getFromDB($parm->getField('networkports_id_2'));
            if ($NetworkPort->fields['itemtype'] == 'Unmanaged') {
                if ($unmanaged->getFromDB($NetworkPort->fields['items_id'])) {
                    if ($unmanaged->fields['hub'] == '1') {
                        $a_vlans = $NetworkPort_Vlan->getVlansForNetworkPort($NetworkPort->fields['id']);
                        foreach ($a_vlans as $vlan_id) {
                            $NetworkPort_Vlan->unassignVlan($NetworkPort->fields['id'], $vlan_id);
                        }
                        $a_hubs[$NetworkPort->fields['items_id']] = 1;
                        $NetworkPort->delete($NetworkPort->fields);
                    }
                }
            }
            if ($port_id) {
                $NetworkPort->getFromDB($port_id);
                if ($NetworkPort->fields['itemtype'] == 'Unmanaged') {
                    $unmanaged->getFromDB($NetworkPort->fields['items_id']);
                    if ($unmanaged->fields['hub'] == '1') {
                        $a_hubs[$NetworkPort->fields['items_id']] = 1;
                    }
                }
            }

           // If hub have no port, delete it
            foreach (array_keys($a_hubs) as $unknowndevice_id) {
                $a_networkports = $NetworkPort->find(
                    ['itemtype' => 'Unmanaged',
                    'items_id' => $unknowndevice_id]
                );
                if (count($a_networkports) < 2) {
                     $unmanaged->delete(['id' => $unknowndevice_id], 1);
                } elseif (count($a_networkports) == '2') {
                    $switchPorts_id = 0;
                    $otherPorts_id  = 0;
                    foreach ($a_networkports as $data) {
                        if ($data['name'] == 'Link') {
                            $switchPorts_id = $NetworkPort->getContact($data['id']);
                        } elseif ($otherPorts_id == '0') {
                            $otherPorts_id = $NetworkPort->getContact($data['id']);
                        } else {
                            $switchPorts_id = $NetworkPort->getContact($data['id']);
                        }
                    }

                    $unmanaged->disconnectFrom($switchPorts_id); // disconnect this port
                    $unmanaged->disconnectFrom($otherPorts_id); // disconnect destination port

                    $networkPort_NetworkPort->add(['networkports_id_1' => $switchPorts_id,
                                                   'networkports_id_2' => $otherPorts_id]);
                }
            }
            break;

        case 'PluginGlpiinventoryTimeslot';
            $pfTimeslotEntry = new PluginGlpiinventoryTimeslotEntry();
            $dbentries = getAllDataFromTable(
                'glpi_plugin_glpiinventory_timeslotentries',
                [
                'WHERE'  => [
                  'plugin_glpiinventory_timeslots_id' => $parm->fields['id']
                ]
                ]
            );
            foreach ($dbentries as $data) {
                $pfTimeslotEntry->delete(['id' => $data['id']], true);
            }
          break;

        case 'PluginGlpiinventoryDeployPackage':
           // Delete all linked items
            $DB->delete(
                'glpi_plugin_glpiinventory_deploypackages_entities',
                [
                'plugin_glpiinventory_deploypackages_id' => $parm->fields['id']
                ]
            );
            $DB->delete(
                'glpi_plugin_glpiinventory_deploypackages_groups',
                [
                'plugin_glpiinventory_deploypackages_id' => $parm->fields['id']
                ]
            );
            $DB->delete(
                'glpi_plugin_glpiinventory_deploypackages_profiles',
                [
                'plugin_glpiinventory_deploypackages_id' => $parm->fields['id']
                ]
            );
            $DB->delete(
                'glpi_plugin_glpiinventory_deploypackages_users',
                [
                'plugin_glpiinventory_deploypackages_id' => $parm->fields['id']
                ]
            );
            break;
    }
    return $parm;
}


/**
 * Define dropdown relations
 *
 * @return array
 */
function plugin_glpiinventory_getDatabaseRelations()
{

    $plugin = new Plugin();

    if ($plugin->isActivated("glpiinventory")) {
        return ["glpi_locations"
                        => ['glpi_plugin_glpiinventory_deploymirrors' => 'locations_id'],
                   "glpi_entities"
                        => [
                                 "glpi_plugin_glpiinventory_collects"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_credentialips"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_credentials"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_deployfiles"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_deploymirrors"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_deploypackages"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_ipranges"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_tasks"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_timeslotentries"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_timeslots"
                                    => "entities_id",
                                 "glpi_plugin_glpiinventory_deployuserinteractiontemplates"
                                    => "entities_id",
                                 ]];
    }
    return [];
}

function plugin_glpiinventory_prolog_response($params)
{
    $agent = new Agent();
    if ($agent->getFromDBByCrit(['deviceid' => $params['deviceid']])) {
        $communication = new PluginGlpiinventoryCommunication();
        $tasks_response = $communication->getTaskAgent($agent->fields['id']);
        $params['response'] += $tasks_response;
    }

    return $params;
}

function plugin_glpiinventory_network_discovery($params)
{
    $agent = new Agent();
    if ($agent->getFromDBByCrit(['deviceid' => $params['deviceid']])) {
        $communication = new PluginGlpiinventoryCommunicationNetworkDiscovery();

        $response = $communication->import(
            $params['deviceid'],
            $params['inventory']->getRawData(),
            $params['inventory']
        );

        $params['response'] = $response['response'];
    }

    return $params;
}

function plugin_glpiinventory_network_inventory($params)
{
    $agent = new Agent();
    if ($agent->getFromDBByCrit(['deviceid' => $params['deviceid']])) {
        $communication = new PluginGlpiinventoryCommunicationNetworkInventory();

        $response = $communication->import(
            $params['deviceid'],
            $params['inventory']->getRawData(),
            $params['inventory']
        );

        $params['response'] = $response['response'];
    }

    return $params;
}
