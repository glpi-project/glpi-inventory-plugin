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
* Abstract class to manage display, add, update, remove and move of items
* in a package
* @since 9.2
*/
class PluginGlpiinventoryDeployPackageItem extends CommonDBTM
{
   //Display modes
    const CREATE      = 'create';
    const EDIT        = 'edit';
    const INIT        = 'init';

    public $shortname = '';

   //The section name in the JSON representation
    public $json_name = '';


   /**
    * Get an event label by its identifier
    * @since 9.2
    * @return array
    */
    public function getLabelForAType($type)
    {
        $types = $this->getTypes();
        if (isset($types[$type])) {
            return $type[$type];
        } else {
            return false;
        }
    }


   /**
   * Get the types already in used, so they cannot be selected anymore
   * @since 9.2
   * @param $package the package to check
   * @return the types already in used
   */
    public function getTypesAlreadyInUse(PluginGlpiinventoryDeployPackage $package)
    {
        return [];
    }


   /**
    * Display the dropdown to select type of element
    *
    * @global array $CFG_GLPI
    * @param object $package the package
    * @param array $config order item configuration
    * @param string $rand unique element id used to identify/update an element
    * @param string $mode mode in use (create, edit...)
    */
    public function displayDropdownType(
        PluginGlpiinventoryDeployPackage $package,
        $config,
        $rand,
        $mode
    ) {
        global $CFG_GLPI;

       //In case of a file item, there's no type, so don't display dropdown
       //in edition mode
        if (!isset($config['type']) && $mode == self::EDIT) {
            return true;
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
                                  'used' => $this->getTypesAlreadyInUse($package)
                ]
            );
            $params = [
                     'value'  => '__VALUE__',
                     'rand'   => $rand,
                     'myname' => 'method',
                     'type'   => $this->shortname,
                     'class'  => get_class($this),
                     'mode'   => $mode
            ];

            Ajax::updateItemOnEvent(
                "dropdown_" . $type_field . $rand,
                "show_" . $this->shortname . "_value$rand",
                Plugin::getWebDir('glpiinventory') .
                "/ajax/deploy_displaytypevalue.php",
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
   * @since 9.2
   */
    public function getItemConfig(PluginGlpiinventoryDeployPackage $package, $request_data)
    {
        $config  = [];
        $element = $package->getSubElement($this->json_name, $request_data['index']);
        if (is_array($element) && count($element)) {
            $config = [ 'type' => $element['type'],
                     'data' => $element];
        }
        return $config;
    }


   /**
    * Display form
    *
    * @param object $package PluginGlpiinventoryDeployPackage instance
    * @param array $request_data
    * @param string $rand unique element id used to identify/update an element
    * @param string $mode possible values: init|edit|create
    */
    public function displayForm(
        PluginGlpiinventoryDeployPackage $package,
        $request_data,
        $rand,
        $mode
    ) {
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
   * @return the html code for a red star
   */
    public function getMandatoryMark()
    {
        return "&nbsp;<span class='red'>*</span>";
    }


   /**
   * Common method to add an item to the package JSON definition
   *
   * @since 9.2
   * @param id the package ID
   * @param item the item to add to the package definition
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
    * @param integer $packages_id id of the order
    * @return boolean|string the string is in json format
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
    * @param integer $packages_id
    * @param array $data
    * @return integer error number
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
         JSON_ERROR_UTF8           => "JSON_ERROR_UTF8"
        ];
        $error_json         = json_last_error();
        $error_json_message = json_last_error_msg();
        $error              = 0;
        if ($error_json != JSON_ERROR_NONE) {
            $error_msg = $json_error_consts[$error_json];
            Session::addMessageAfterRedirect(
                __("The modified JSON contained a syntax error :", "glpiinventory") . "<br/>" .
                $error_msg . "<br/>" . $error_json_message,
                false,
                ERROR,
                false
            );
            $error = 1;
        } else {
            $error = $pfDeployPackage->update(['id'   => $packages_id,
                                            'json' => Toolbox::addslashes_deep($json)]);
        }
        return $error;
    }


   /**
    * Remove an item
    *
    * @param array $params
    * @return boolean
    */
    public function remove_item($params)
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
    }


   /**
    * Move an item
    *
    * @param array $params
    */
    public function move_item($params)
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
    }


   /**
    * Get the size of file
    *
    * @param integer $filesize
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
                $filesize = $filesize . "B";
            }
            return $filesize;
        } else {
            return NOT_AVAILABLE;
        }
    }


   /**
   * Display a add or save button
   * @since 9.2
   *
   * @param pfDeployPackage the package in use
   * @param mode the mode (edit or create)
   */
    public function addOrSaveButton(PluginGlpiinventoryDeployPackage $pfDeployPackage, $mode)
    {
        echo "<tr>";
        echo "<td>";
        echo "</td>";
        echo "<td>";
        if ($pfDeployPackage->can($pfDeployPackage->getID(), UPDATE)) {
            if ($mode === self::EDIT) {
                echo "<input type='submit' name='save_item' value=\"" .
                 _sx('button', 'Save') . "\" class='submit' >";
            } else {
                echo "<input type='submit' name='add_item' value=\"" .
                _sx('button', 'Add') . "\" class='submit' >";
            }
        }
        echo "</td>";
        echo "</tr>";
    }


    public function getItemValues($packages_id)
    {
        $data = json_decode($this->getJson($packages_id), true);
        if ($data) {
            return $data['jobs'][$this->json_name];
        } else {
            return [];
        }
    }

    public function displayAjaxValues($config, $request_data, $rand, $mode)
    {
        return true;
    }

    public function getTypes()
    {
        return [];
    }
}
