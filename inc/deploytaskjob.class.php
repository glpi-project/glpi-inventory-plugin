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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Manage the deploy task job.
 *
 * @todo This class should inherit the PluginGlpiinventoryTaskjob
 */
class PluginGlpiinventoryDeployTaskjob extends CommonDBTM
{
   /**
    * Is this use can create a deploy task job
    *
    * @return boolean
    */
    public static function canCreate()
    {
        return true;
    }


   /**
    * Is this use can view a deploy task job
    *
    * @return boolean
    */
    public static function canView()
    {
        return true;
    }


   /**
    * Get all data
    *
    * @global object $DB
    * @param array $params
    * @return string in JSON format
    */
    public function getAllDatas($params)
    {
        global $DB;

        $tasks_id = $params['tasks_id'];

        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [
                'plugin_glpiinventory_deploytasks_id' => $tasks_id,
                'method'                              => 'deployinstall'
            ]
        ]);

        $json  = [];
        $temp_tasks = [];
        foreach ($iterator as $row) {
            $row['packages'] = importArrayFromDB($row['definition']);
            $row['actions'] = importArrayFromDB($row['action']);

            $temp_tasks[] = $row;
        }

        $i = 0;
        foreach ($temp_tasks as $task) {
            foreach ($task['actions'] as $action) {
                foreach ($task['packages'] as $package) {
                    $tmp         = array_keys($action);
                    $action_type = $tmp[0];

                    $json['tasks'][$i]['package_id']       = $package['PluginGlpiinventoryDeployPackage'];
                    $json['tasks'][$i]['method']           = $task['method'];
                    $json['tasks'][$i]['comment']          = $task['comment'];
                    $json['tasks'][$i]['retry_nb']         = $task['retry_nb'];
                    $json['tasks'][$i]['retry_time']       = $task['retry_time'];
                    $json['tasks'][$i]['action_type']      = $action_type;
                    $json['tasks'][$i]['action_selection'] = $action[$action_type];

                    $obj_action = new $action_type();
                    $obj_action->getFromDB($action[$action_type]);
                    $json['tasks'][$i]['action_name'] = $obj_action->getField('name');

                    $i++;
                }
            }
        }
        return json_encode($json);
    }


   /**
    * Save data
    *
    * @global object $DB
    * @param array $params
    */
    public function saveDatas($params)
    {
        global $DB;

        $tasks_id = $params['tasks_id'];
        $tasks = json_decode($params['tasks']);

       //remove old jobs from task
        $this->deleteByCriteria(['plugin_glpiinventory_deploytasks_id' => $tasks_id], true);

       //get plugin id
        $plug = new Plugin();
        $plug->getFromDBbyDir('fusinvdeploy');
        $plugins_id = $plug->getField('id');

       //insert new rows
        $sql_tasks = [];
        $i = 0;

        $qparam = new QueryParam();
        $query = $DB::buildInsert(
            $this->getTable(),
            [
            'plugin_glpiinventory_deploytasks_id'   => $qparam,
            'name'                                    => $qparam,
            'date_creation'                           => $qparam,
            'entities_id'                             => $qparam,
            'plugins_id'                              => $qparam,
            'method'                                  => $qparam,
            'definition'                              => $qparam,
            'action'                                  => $qparam,
            'retry_nb'                                => $qparam,
            'retry_time'                              => $qparam,
            'periodicity_type'                        => $qparam,
            'periodicity_count'                       => $qparam
            ]
        );
        $stmt = $DB->prepare($query);

        foreach ($tasks as $task) {
            $task = get_object_vars($task);

            //encode action and definition
            //$action = exportArrayToDB(array(array(
            //    $task['action_type'] => $task['action_selection'])));
            $action = exportArrayToDB($task['action']);
            $definition = exportArrayToDB([[
              'PluginGlpiinventoryDeployPackage' => $task['package_id']]]);

            $job_name = "job_" . $tasks_id . "_" . $i;
            $now = 'NOW()';
            $entities_id = '0';
            $period_type = 'minutes';
            $period_count = '0';
            $stmt->bind_param(
                'ssssssssssss',
                $tasks_id,
                $job_name,
                $now,
                $entities_id,
                $plugins_id,
                $task['method'],
                $definition,
                $action,
                $task['retry_nb'],
                $task['retry_time'],
                $period_type,
                $period_count
            );
            $DB->executeStatement($stmt);
        }
        mysqli_stmt_close($stmt);
    }


   /**
    * Get the different type of task job actions
    *
    * @return array
    */
    public static function getActionTypes()
    {

        return [
         [
            'name' => __('Computers'),
            'value' => 'Computer',
         ],
         [
            'name' => __('Group'),
            'value' => 'Group',
         ],
         [
            'name' => __('Groups of computers', 'glpiinventory'),
            'value' => 'PluginGlpiinventoryDeployGroup',
         ]
        ];
    }


   /**
    * Get actions
    *
    * @global object $DB
    * @param array $params
    * @return string in JSON format
    */
    public static function getActions($params)
    {
        global $DB;

        $res = '';
        if (!isset($params['get'])) {
            exit;
        }
        switch ($params['get']) {
            case "type":
                $res = json_encode([
                'action_types' => self::getActionTypes()
                ]);
                break;
            case "selection":
                switch ($params['type']) {
                    case 'Computer':
                        $where = [];
                        if (isset($params['query'])) {
                            $where['name'] = ['LIKE', '%' . $like];
                        }

                        $i = 0;
                        $iterator = $DB->request([
                            'SELECT' => ['id', 'name'],
                            'FROM' => 'glpi_computers',
                            'WHERE' => $where,
                            'ORDER' => 'name ASC'
                        ]);

                        foreach ($iterator as $row) {
                            $res['action_selections'][$i]['id'] = $row['id'];
                            $res['action_selections'][$i]['name'] = $row['name'];
                            $i++;
                        }

                        $res = json_encode($res);
                        break;

                    case 'Group':
                        $like = [];
                        if (isset($params['query'])) {
                            //FIXME: not sure escape is mandatory here
                            $like += ['name' => ['LIKE', '%' . $DB->escape($params['query'])]];
                        }
                        $group = new Group();
                        $group_datas = $group->find($like);
                        $i = 0;
                        foreach ($group_datas as $group_data) {
                            $res['action_selections'][$i]['id'] = $group_data['id'];
                            $res['action_selections'][$i]['name'] = $group_data['name'];
                            $i++;
                        }
                        $res = json_encode($res);
                        break;

                    case 'PluginGlpiinventoryDeployGroup':
                        $res = PluginGlpiinventoryDeployGroup::getAllDatas('action_selections');
                        break;
                }
                break;

            case "oneSelection":
                break;

            default:
                $res = '';
        }
        return $res;
    }
}
