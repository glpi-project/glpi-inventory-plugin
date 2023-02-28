<?php

/**
 * ---------------------------------------------------------------------
 * GLPI Inventory Plugin
 * Copyright (C) 2021-2023 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on FusionInventory for GLPI
 * Copyright (C) 2010-2023 by the FusionInventory Development Team.
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
 * Manage the search in groups (static and dynamic).
 */
class PluginGlpiinventoryComputer extends Computer
{
   /**
    * The right name for this class
    *
    * @var string
    */
    public static $rightname = "plugin_glpiinventory_group";


    public function rawSearchOptions()
    {
        $computer = new Computer();
        $options  = $computer->rawSearchOptions();

        $plugin = new Plugin();
        if ($plugin->isInstalled('fields')) {
            if ($plugin->isActivated('fields')) {
                // Include Fields hook from correct installation folder (marketplace or plugins)
                include_once(Plugin::GetPhpDir("fields") . "/hook.php");
                $options['fields_plugin'] = [
                 'id'   => 'fields_plugin',
                 'name' => __('Plugin fields')
                ];
                $fieldsoptions =  plugin_fields_getAddSearchOptions('Computer');
                foreach ($fieldsoptions as $id => $data) {
                    $data['id'] = $id;
                    $options[$id] = $data;
                }
            }
        }

        return $options;
    }

    /**
     * Return the table used to store this object
     *
     * @param string $classname Force class (to avoid late_binding on inheritance)
     *
     * @return string
     **/
    public static function getTable($classname = null)
    {
        return 'glpi_computers';
    }


   /**
    * Define the standard massive actions to hide for this class
    *
    * @return array list of massive actions to hide
    */
    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        $forbidden[] = 'add';
        $forbidden[] = 'delete';
        return $forbidden;
    }


   /**
    * Execution code for massive action
    *
    * @param object $ma MassiveAction instance
    * @param object $item item on which execute the code
    * @param array $ids list of ID on which execute the code
    */
    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
    {

        $group_item = new PluginGlpiinventoryDeployGroup_Staticdata();
        switch ($ma->getAction()) {
            case 'add':
                foreach ($ids as $key) {
                    if ($item->can($key, UPDATE)) {
                        if (
                            !countElementsInTable(
                                $group_item->getTable(),
                                [
                                'plugin_glpiinventory_deploygroups_id' => $_POST['id'],
                                'itemtype'                               => 'Computer',
                                'items_id'                               => $key,
                                ]
                            )
                        ) {
                            $group_item->add([
                            'plugin_glpiinventory_deploygroups_id'
                            => $_POST['id'],
                            'itemtype' => 'Computer',
                            'items_id' => $key]);
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        }
                    } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                    }
                }
                return;

            case 'deleteitem':
                foreach ($ids as $key) {
                    if (
                        $group_item->deleteByCriteria(['items_id' => $key,
                                                       'itemtype' => 'Computer',
                                                       'plugin_glpiinventory_deploygroups_id'
                                                          => $_POST['item_items_id']])
                    ) {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                    } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                    }
                }
        }
    }


   /**
    * Display form related to the massive action selected
    *
    * @param object $ma MassiveAction instance
    * @return boolean
    */
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        if ($ma->getAction() == 'add') {
            echo "<br><br>" . Html::submit(
                _x('button', 'Add'),
                ['name' => 'massiveaction']
            );
            return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }
}
