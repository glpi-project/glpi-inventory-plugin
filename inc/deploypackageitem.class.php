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

use function Safe\json_decode;
use function Safe\json_encode;

/**
* Abstract class to manage display, add, update, remove and move of items
* in a package
* @since 9.2
*/
abstract class PluginGlpiinventoryDeployPackageItem extends CommonDBTM
{
    //Display modes
    public const CREATE      = 'create';
    public const EDIT        = 'edit';
    public const INIT        = 'init';

    public string $shortname = '';

    //The section name in the JSON representation
    public string $json_name = '';


    /**
     * Get an event label by its identifier
     */
    public function getLabelForAType(string $type): string
    {
        $types = $this->getTypes();
        return $types[$type] ?? '';
    }


    /**
    * Get the types already in used, so they cannot be selected anymore
    *
    * @param PluginGlpiinventoryDeployPackage $package the package to check
    * @return array<string> the types already in used
    */
    public function getTypesAlreadyInUse(PluginGlpiinventoryDeployPackage $package)
    {
        return [];
    }


    /**
     * Display the dropdown to select type of element
     *
     * @param PluginGlpiinventoryDeployPackage $package the package
     * @param array<string,mixed> $config order item configuration
     * @param string $rand unique element id used to identify/update an element
     * @param string $mode mode in use (create, edit...)
     */
    public function displayDropdownType(
        PluginGlpiinventoryDeployPackage $package,
        $config,
        $rand,
        $mode
    ): void {
        global $CFG_GLPI;

        //In case of a file item, there's no type, so don't display dropdown
        //in edition mode
        if (!isset($config['type']) && $mode == self::EDIT) {
            return;
        }

        /*
        * Display dropdown html
        */
        echo "<table class='package_item'>";
        echo "<tr>";
        echo "<th>" . _n("Type", "Types", 1) . "</th>";
        echo "<td>";

        $type_field = $this->shortname . "type";

        if ($mode === self::CREATE) {
            $types      = $this->getTypes();
            array_unshift($types, Dropdown::EMPTY_VALUE);

            Dropdown::showFromArray(
                $type_field,
                $types,
                ['rand' => $rand,
                    'used' => $this->getTypesAlreadyInUse($package),
                ]
            );
            $params = [
                'value'  => '__VALUE__',
                'rand'   => $rand,
                'myname' => 'method',
                'type'   => $this->shortname,
                'class'  => get_class($this),
                'mode'   => $mode,
            ];

            Ajax::updateItemOnEvent(
                "dropdown_" . $type_field . $rand,
                "show_" . $this->shortname . "_value$rand",
                $CFG_GLPI['root_doc'] . "/plugins/glpiinventory/ajax/deploy_displaytypevalue.php",
                $params,
                ["change", "load"]
            );
        } else {
            echo Html::hidden($type_field, ['value' => $config['type']]);
            echo $this->getLabelForAType($config['type']);
        }

        echo "</td>";
        echo "</tr></table>";
    }


    /**
    * Create a configuration request data
     *
     * @param PluginGlpiinventoryDeployPackage $package the package
     * @param array<string,mixed> $request_data the request data
     *
     * @return array<string,mixed> the item configuration
    */
    public function getItemConfig(PluginGlpiinventoryDeployPackage $package, array $request_data): array
    {
        $config  = [];
        $element = $package->getSubElement($this->json_name, $request_data['index']);
        if (is_array($element) && count($element)) {
            $config = [ 'type' => $element['type'],
                'data' => $element,
            ];
        }
        return $config;
    }


    /**
     * Display form
     *
     * @param PluginGlpiinventoryDeployPackage $package PluginGlpiinventoryDeployPackage instance
     * @param array<string,mixed> $request_data
     * @param string $rand unique element id used to identify/update an element
     * @param string $mode possible values: init|edit|create
     */
    public function displayForm(
        PluginGlpiinventoryDeployPackage $package,
        $request_data,
        $rand,
        $mode
    ): void {
        /*
         * Get element config in 'edit' mode
         */
        $config = null;
        if ($mode === self::EDIT && isset($request_data['index'])) {
            /*
             * Add an hidden input about element's index to be updated
             */
            echo Html::hidden('index', ['value' => $request_data['index']]);
            $config = $this->getItemConfig($package, $request_data);
        }

        /*
        * Display start of div form
        */
        if (in_array($mode, [self::INIT], true)) {
            echo "<div id='" . $this->shortname . "_block$rand' style='display:none'>";
        }

        /*
        * Display element's dropdownType in 'create' or 'edit' mode
        */
        if (in_array($mode, [self::CREATE, self::EDIT], true)) {
            $this->displayDropdownType($package, $config, $rand, $mode);
        }

        /*
        * Display element's values in 'edit' mode only.
        * In 'create' mode, those values are refreshed with dropdownType 'change'
        * javascript event.
        */
        if (in_array($mode, [self::CREATE, self::EDIT], true)) {
            echo "<span id='show_" . $this->shortname . "_value{$rand}'>";
            if ($mode === self::EDIT) {
                $this->displayAjaxValues($config, $request_data, $rand, $mode);
            }
            echo "</span>";
        }

        /*
        * Close form div
        */
        if (in_array($mode, [self::INIT], true)) {
            echo "</div>";
        }
    }


    /**
    * Get an HTML mandatory mark (a red star)
    * @since 9.2
    * @return string the html code for a red star
    */
    public function getMandatoryMark()
    {
        return "&nbsp;<span class='red'>*</span>";
    }


    /**
     * Common method to add an item to the package JSON definition
     *
     * @since 9.2
     * @param int $id the package ID
     * @param array<string,mixed> $item the item to add to the package definition
     * @param string $order the order of the item
     *
     * @return void
     */
    public function addToPackage($id, $item, $order)
    {
        //get current json package defintion
        $data = json_decode($this->getJson($id), true);

        //add new entry
        $data['jobs'][$order][] = $item;

        //Update package
        $this->updateOrderJson($id, $data);
    }


    /**
     * Get the json
     *
     * @param int $packages_id id of the order
     * @return bool|string the string is in json format
     */
    public function getJson($packages_id)
    {
        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();
        $pfDeployPackage->getFromDB($packages_id);
        if (!empty($pfDeployPackage->fields['json'])) {
            return $pfDeployPackage->fields['json'];
        } else {
            return false;
        }
    }


    /**
     * @param array<string,mixed> $params
     * @param array<string,mixed> $entry
     * @return array<string,mixed>
     */
    public function prepareDataToSave($params, $entry)
    {
        //get current order json
        $data = json_decode($this->getJson($params['id']), true);

        //unset index
        unset($data['jobs'][$this->json_name][$params['index']]);

        //add new data at index position
        //(array_splice for insertion, ex : http://stackoverflow.com/a/3797526)
        array_splice(
            $data['jobs'][$this->json_name],
            $params['index'],
            0,
            [$entry]
        );

        return $data;
    }


    /**
     * Update the order json
     *
     * @param int $packages_id
     * @param array<string,mixed> $data
     * @return int error number
     */
    public function updateOrderJson($packages_id, $data)
    {
        $pfDeployPackage   = new PluginGlpiinventoryDeployPackage();
        $options           = JSON_UNESCAPED_SLASHES;
        $json              = json_encode($data, $options);
        $json_error_consts = [
            JSON_ERROR_NONE           => "JSON_ERROR_NONE",
            JSON_ERROR_DEPTH          => "JSON_ERROR_DEPTH",
            JSON_ERROR_STATE_MISMATCH => "JSON_ERROR_STATE_MISMATCH",
            JSON_ERROR_CTRL_CHAR      => "JSON_ERROR_CTRL_CHAR",
            JSON_ERROR_SYNTAX         => "JSON_ERROR_SYNTAX",
            JSON_ERROR_UTF8           => "JSON_ERROR_UTF8",
        ];
        $error_json         = json_last_error();
        $error_json_message = json_last_error_msg();
        $error              = 0;
        if ($error_json != JSON_ERROR_NONE) {
            $error_msg = $json_error_consts[$error_json];
            Session::addMessageAfterRedirect(
                __("The modified JSON contained a syntax error :", "glpiinventory") . "<br/>"
                . $error_msg . "<br/>" . $error_json_message,
                false,
                ERROR,
                false
            );
            $error = 1;
        } else {
            $error = $pfDeployPackage->update([
                'id'   => $packages_id,
                'json' => $json,
            ]);
        }
        return $error;
    }

    /**
     * Add a new item in files of the package
     *
     * @param array<string,mixed> $params list of fields with value of the file
     */
    abstract public function add_item(array $params): bool;

    /**
     * Save the item in files
     *
     * @param array<string,mixed> $params list of fields with value of the file
     */
    abstract public function save_item(array $params): bool;

    /**
     * Remove an item
     *
     * @param array<string,mixed> $params
     */
    public function remove_item(array $params): bool
    {
        if (!isset($params[$this->shortname . '_entries'])) {
            return false;
        }

        //get current order json
        $data = json_decode($this->getJson($params['packages_id']), true);
        //remove selected checks
        foreach ($params[$this->shortname . '_entries'] as $index => $checked) {
            if ($checked >= "1" || $checked == "on") {
                unset($data['jobs'][$this->shortname][$index]);
            }
        }

        //Ensure actions list is an array and not a dictionnary
        //Note: This happens when removing an array element from the begining
        $data['jobs'][$this->shortname] = array_values($data['jobs'][$this->shortname]);

        //update order
        $this->updateOrderJson($params['packages_id'], $data);
        return true;
    }


    /**
     * Move an item
     *
     * @param array<string,mixed> $params
     */
    public function move_item(array $params): bool
    {
        //get current order json
        $data = json_decode($this->getJson($params['id']), true);

        //get data on old index
        $moved_check = $data['jobs'][$this->json_name][$params['old_index']];

        //remove this old index in json
        unset($data['jobs'][$this->json_name][$params['old_index']]);

        //insert it in new index (array_splice for insertion, ex : http://stackoverflow.com/a/3797526)
        array_splice($data['jobs'][$this->json_name], $params['new_index'], 0, [$moved_check]);

        //update order
        $this->updateOrderJson($params['id'], $data);
        return true;
    }


    /**
     * Get the size of file
     *
     * @param mixed $filesize
     * @return string
     */
    public function processFilesize($filesize)
    {
        if (is_numeric($filesize)) {
            if ($filesize >= (1024 * 1024 * 1024)) {
                $filesize = round($filesize / (1024 * 1024 * 1024), 1) . "GiB";
            } elseif ($filesize >= 1024 * 1024) {
                $filesize = round($filesize /  (1024 * 1024), 1) . "MiB";
            } elseif ($filesize >= 1024) {
                $filesize = round($filesize / 1024, 1) . "KB";
            } else {
                $filesize .= "B";
            }
            return $filesize;
        } else {
            return NOT_AVAILABLE;
        }
    }


    /**
    * Display add or save button
    *
    * @param PluginGlpiinventoryDeployPackage $pfDeployPackage the package in use
    * @param string $mode the mode (edit or create)
     *
     * @return void
    */
    public function addOrSaveButton(PluginGlpiinventoryDeployPackage $pfDeployPackage, $mode)
    {
        echo "<tr>";
        echo "<td>";
        echo "</td>";
        echo "<td>";
        if ($pfDeployPackage->can($pfDeployPackage->getID(), UPDATE)) {
            if ($mode === self::EDIT) {
                echo "<input type='submit' name='save_item' value=\""
                 . _sx('button', 'Save') . "\" class='submit' >";
            } else {
                echo "<input type='submit' name='add_item' value=\""
                . _sx('button', 'Add') . "\" class='submit' >";
            }
        }
        echo "</td>";
        echo "</tr>";
    }


    /**
     * @return array<stirng,mixed>
     */
    public function getItemValues(int $packages_id)
    {
        $data = json_decode($this->getJson($packages_id), true);
        if ($data) {
            return $data['jobs'][$this->json_name];
        } else {
            return [];
        }
    }

    /**
     * @param ?array<string,mixed> $config
     * @param array<string,mixed> $request_data
     * @param string $rand
     * @param string $mode mode to sue (create, edit...)
     *
     * @return void
     */
    public function displayAjaxValues(?array $config, array $request_data, string $rand, string $mode): void
    {
    }

    /**
     * @return array<string,string|array<string,string>>
     */
    public function getTypes()
    {
        return [];
    }


    /**
     * Display list
     *
     * @param PluginGlpiinventoryDeployPackage $package PluginGlpiinventoryDeployPackage instance
     * @param array<string,mixed> $data array converted of 'json' field in DB where stored checks
     * @param string $rand unique element id used to identify/update an element
     */

    abstract public function displayDeployList(PluginGlpiinventoryDeployPackage $package, array $data, string $rand): void;
}
