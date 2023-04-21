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
 * Manage the checks before deploy a package.
 */
class PluginGlpiinventoryDeployCheck extends PluginGlpiinventoryDeployPackageItem
{
    public $shortname = 'checks';
    public $json_name = 'checks';


   /**
    * Get types of checks with name => description
    *
    * @return array
    */
    public function getTypes()
    {
        return [
         __('Registry', 'glpiinventory') => [
                  'winkeyExists'       => __("Registry key exists", 'glpiinventory'),
                  'winvalueExists'     => __("Registry value exists", 'glpiinventory'),
                  'winkeyMissing'      => __("Registry key missing", 'glpiinventory'),
                  'winvalueMissing'    => __("Registry value missing", 'glpiinventory'),
                  'winkeyEquals'       => __("Registry value equals to", 'glpiinventory'),
                  'winkeyNotEquals'    => __("Registry value not equals to", 'glpiinventory'),
                  'winvalueType'       => __("Type of registry value equals to", 'glpiinventory')
               ],
               __('File') => [
                  'fileExists'         => __("File exists", 'glpiinventory'),
                  'fileMissing'        => __("File is missing", 'glpiinventory'),
                  'fileSizeGreater'    => __("File size is greater than", 'glpiinventory'),
                  'fileSizeEquals'     => __("File size is equal to", 'glpiinventory'),
                  'fileSizeLower'      => __("File size is lower than", 'glpiinventory'),
                  'fileSHA512'         => __("SHA-512 hash value matches", 'glpiinventory'),
                  'fileSHA512mismatch' => __("SHA-512 hash value mismatch", 'glpiinventory'),
               ],
               __('Directory') => [
                  'directoryExists'    => __("Directory exists", 'glpiinventory'),
                  'directoryMissing'   => __("Directory is missing", 'glpiinventory'),
               ],
             __('Other') => [
            'freespaceGreater'   => __("Free space is greater than", 'glpiinventory')
             ]
        ];
    }


   /**
    * Get label for a type
    * @param the type value
    * @return the type label
    */
    public function getLabelForAType($type)
    {
        $alltypes = [];
        foreach ($this->getTypes() as $label => $types) {
            $alltypes += $types;
        }
        if (isset($alltypes[$type])) {
            return $alltypes[$type];
        } else {
            return '';
        }
    }


   /**
    * Get Unit name
    *
    * @return array
    */
    public function getUnitLabel()
    {
        return [
               "B"  => __('o'),
               "KB" => __('Kio'),
               "MB" => __('Mio'),
               "GB" => __('Gio')
             ];
    }


    public function getAuditDescription($type, $return)
    {
        $return_string = $this->getLabelForAType($type);
       //The skip case is a litte bit different. So we notice to the user
       //that if audit is successful, the audit check process continue
        if ($return == 'skip') {
            $return_string .= ' : ' . __('continue', 'glpiinventory');
        } else {
            $return_string .= ' : ' . __('passed', 'glpiinventory');
        }
        $return_string .= ', ' . __('otherwise', 'glpiinventory') . ' : ';
        $return_string .= $this->getValueForReturn($return);

        return $return_string;
    }


   /**
    * Get the number to multiply to have in B relative to the unit
    *
    * @param string $unit the unit of number
    * @return integer the number to multiply
    */
    public function getUnitSize($unit)
    {
        $units = [ "B"  => 1,
                 "KB" => 1024,
                 "MB" => 1024 * 1024,
                 "GB" => 1024 * 1024 * 1024
               ];
        if (array_key_exists($unit, $units)) {
            return $units[$unit];
        } else {
            return 1;
        }
    }


   /**
   * Get all registry value types handled by the agent
   *
   * @since 9.2
   * @return an array of registry values types
   */
    public function getRegistryTypes()
    {
        return [
         'REG_SZ'                  => 'REG_SZ',
         'REG_DWORD'               => 'REG_DWORD',
         'REG_BINARY'              => 'REG_BINARY',
         'REG_EXPAND_SZ'           => 'REG_EXPAND_SZ',
         'REG_MULTI_SZ'            => 'REG_MULTI_SZ',
         'REG_LINK'                => 'REG_LINK',
         'REG_DWORD_BIG_ENDIAN'    => 'REG_DWORD_BIG_ENDIAN',
         'REG_NONE'                => 'REG_NONE'
        ];
    }


    public function dropdownRegistryTypes($value = 'REG_SZ')
    {
        return Dropdown::showFromArray(
            'value',
            $this->getRegistryTypes(),
            ['value' => $value]
        );
    }


   /**
    * Display list of checks
    *
    * @global array $CFG_GLPI
    * @param object $package PluginGlpiinventoryDeployPackage instance
    * @param array $data array converted of 'json' field in DB where stored checks
    * @param string $rand unique element id used to identify/update an element
    */
    public function displayList(PluginGlpiinventoryDeployPackage $package, $data, $rand)
    {
        global $CFG_GLPI;

        $checks_types = $this->getTypes();
        $package_id   = $package->getID();
        $canedit      = $package->canUpdateContent();
        echo "<table class='tab_cadrehov package_item_list' id='table_checks_$rand'>";
        $i = 0;
        foreach ($data['jobs']['checks'] as $check) {
            switch ($check['type']) {
                case 'freespaceGreater':
                    $check['value'] = $check['value'] * 1024 * 1024;
                    break;
                case 'fileSizeLower':
                case 'fileSizeGreater':
                case 'fileSizeEquals':
                    $check['value'] = $this->processFilesize($check['value']);
                    break;
            }

            echo Search::showNewLine(Search::HTML_OUTPUT, ($i % 2));
            if ($canedit) {
                echo "<td class='control'>";
                Html::showCheckbox(['name' => 'checks_entries[' . $i . ']']);
                echo "</td>";
            }

           //Get the audit full description (with type and return value)
           //to be displayed in the UI
            $text = $this->getAuditDescription($check['type'], $check['return']);
            if (isset($check['name']) && !empty($check['name'])) {
                $check_label = $check['name'] . ' (' . $text . ')';
            } else {
                $check_label = $text;
            }
            echo "<td>";
            if ($canedit) {
                echo "<a class='edit'
                     onclick=\"edit_subtype('check', $package_id, $rand ,this)\">";
            }
            echo $check_label;
            if ($canedit) {
                echo "</a>";
            }

            if ($check['return'] === 'startnow') {
                echo "<br />";
                $warning = sprintf(__('GLPI-Agent or Fusioninventory-Agent >= %1s mandatory', 'glpiinventory'), '2.4.2');
                echo "<img src='" . $CFG_GLPI['root_doc'] . "/pics/warning_min.png'>";
                echo "<span class='red'><i>" . $warning . "</i></span>";
            }

            echo "<br />";
            $type_values = $this->getLabelsAndTypes($check['type'], false);
            if (isset($type_values['path_label'])) {
                echo $type_values['path_label'] . ': ' . $check['path'];
            }

            if (!empty($check['value']) && $check['value'] != NOT_AVAILABLE) {
                echo "&nbsp;&nbsp;&nbsp;<b>";
                switch ($check['type']) {
                    case 'freespaceGreater':
                    case 'fileSizeGreater':
                        echo "&gt;";
                        break;
                    case 'fileSizeLower':
                        echo "&lt;";
                        break;
                    default:
                        echo "=";
                        break;
                }
                echo "</b>&nbsp;&nbsp;&nbsp;";
                echo $check['value'];
            }

            echo "</td>";
            if ($canedit) {
                echo "<td class='rowhandler control' title='" . __('drag', 'glpiinventory') .
                "'><div class='drag row'></div></td>";
            }
            echo "</tr>";
            $i++;
        }
        if ($canedit) {
            echo "<tr><th>";
            echo Html::getCheckAllAsCheckbox("checksList$rand", mt_rand());
            echo "</th><th colspan='3' class='mark'></th></tr>";
        }
        echo "</table>";
        if ($canedit) {
            echo "&nbsp;&nbsp;<img src='" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png' alt='' />";
            echo "<input type='submit' name='delete' value=\"" .
            __('Delete', 'glpiinventory') . "\" class='submit' />";
        }
    }


   /**
    * Get fields for the check type requested
    *
    * @param string $type the type of check
    * @param array $data fields yet defined in edit mode
    * @param string $mode mode in use (create, edit...)
    *
    * @return string|false
    */
    public function getValues($type, $data, $mode)
    {
        $values = [
         'warning_message' => false,
         'name_value'  => "",
         'name_label'  => __('Audit label', 'glpiinventory'),
         'name_type'   => "input",
         'path_label'  => "",
         'path_value'  => "",
         'path_comment' => "",
         'value_type'  => "input",
         'value_label' => "",
         'value'       => "",
         'return'      => "error"
        ];

        if ($mode === self::EDIT) {
            $values['name_value'] = isset($data['name']) ? $data['name'] : "";
            $values['path_value'] = isset($data['path']) ? $data['path'] : "";
            $values['value']      = isset($data['value']) ? $data['value'] : "";
            $values['return']     = isset($data['return']) ? $data['return'] : "error";
        }

        $type_values = $this->getLabelsAndTypes($type, true);
        foreach ($type_values as $key => $value) {
            $values[$key] = $value;
        }
        if ($type == 'freespaceGreater' && !is_numeric($values['value'])) {
            $values['value'] = 0;
        }
        return $values;
    }


   /**
   *  Get labels and type for a check
   * @param check_type the type of check
   * @param mandatory indicates if mandatory mark must be added to the label
   * @return the labels and type for a check
   */
    public function getLabelsAndTypes($check_type, $mandatory = false)
    {
        $values = [];
        $mandatory_mark = ($mandatory ? $this->getMandatoryMark() : '');

        switch ($check_type) {
            case "winkeyExists":
            case "winkeyMissing":
                $values['path_label']         = __("Path to the key", 'glpiinventory') . $mandatory_mark;
                $values['value_label']     = false;
                $values['path_comment']    = __('Example of registry key', 'glpiinventory') . ': HKEY_LOCAL_MACHINE\SOFTWARE\GLPI-Agent\\';
                $values['warning_message'] = sprintf(__('GLPI-Agent or Fusioninventory-Agent >= %1s recommended', 'glpiinventory'), '2.3.20');
                break;

            case "winvalueExists":
            case "winvalueMissing":
                $values['path_label']      = __("Path to the value", 'glpiinventory') . $mandatory_mark;
                $values['value_label']     = false;
                $values['path_comment']    = __('Example of registry value', 'glpiinventory') . ': HKEY_LOCAL_MACHINE\SOFTWARE\GLPI-Agent\server';
                $values['warning_message'] = sprintf(__('GLPI-Agent or Fusioninventory-Agent >= %1s mandatory', 'glpiinventory'), '2.3.20');
                break;

            case "winkeyEquals":
            case "winkeyNotEquals":
                $values['path_label']      = __("Path to the value", 'glpiinventory') . $mandatory_mark;
                $values['value_label']     = __('Value', 'glpiinventory');
                $values['path_comment']    = __('Example of registry value', 'glpiinventory') . ': HKEY_LOCAL_MACHINE\SOFTWARE\GLPI-Agent\server';
                if ($check_type == 'winkeyEquals') {
                    $values['warning_message'] = sprintf(__('GLPI-Agent or Fusioninventory-Agent >= %1s recommended', 'glpiinventory'), '2.3.20');
                } else {
                    $values['warning_message'] = sprintf(__('GLPI-Agent or Fusioninventory-Agent >= %1s mandatory', 'glpiinventory'), '2.3.21');
                }
                break;

            case "winvalueType":
                $values['path_label']      = __("Path to the value", 'glpiinventory') . $mandatory_mark;
                $values['value_label']     = __('Type of value', 'glpiinventory') . $mandatory_mark;
                $values['value_type']      = 'registry_type';
                $values['path_comment']    = __('Example of registry value', 'glpiinventory') . ': HKEY_LOCAL_MACHINE\SOFTWARE\GLPI-Agent\server';
                $values['warning_message'] = sprintf(__('GLPI-Agent or Fusioninventory-Agent >= %1s mandatory', 'glpiinventory'), '2.3.20');
                break;

            case "fileExists":
            case "fileMissing":
                $values['path_label']  = __("File", 'glpiinventory') . $mandatory_mark;
                $values['value_label'] = false;
                break;

            case "directoryExists":
            case "directoryMissing":
                $values['path_label']  = __("Directory", 'glpiinventory') . $mandatory_mark;
                $values['value_label'] = false;
                break;

            case "fileSizeGreater":
            case "fileSizeEquals":
            case "fileSizeLower":
                $values['path_label']  = __("File", 'glpiinventory') . $mandatory_mark;
                $values['value_label'] = __('Value', 'glpiinventory') . $mandatory_mark;
                $values['value_type']  = "input+unit";
                break;

            case "fileSHA512":
            case "fileSHA512mismatch":
                $values['path_label']  = __("File", 'glpiinventory') . $mandatory_mark;
                $values['value_label'] = __('Value', 'glpiinventory') . $mandatory_mark;
                $values['value_type']  = "textarea";
                break;

            case "freespaceGreater":
                $values['path_label']  = __("Disk or directory", 'glpiinventory') . $mandatory_mark;
                $values['value_label'] = __('Value', 'glpiinventory') . $mandatory_mark;
                $values['value_type']  = "input+unit";
                break;

            default:
                break;
        }
        return $values;
    }


   /**
    * Display different fields relative the check selected
    *
    * @param array $config
    * @param array $request_data
    * @param string $rand unique element id used to identify/update an element
    * @param string $mode mode in use (create, edit...)
    * @return boolean
    */
    public function displayAjaxValues($config, $request_data, $rand, $mode)
    {
        global $CFG_GLPI;

        $pfDeployPackage = new PluginGlpiinventoryDeployPackage();

        if (isset($request_data['packages_id'])) {
            $pfDeployPackage->getFromDB($request_data['orders_id']);
        } else {
            $pfDeployPackage->getEmpty();
        }

       /*
       * Get type from request params
       */
        $type = null;
        if ($mode === self::CREATE) {
            $type = $request_data['value'];
            $config_data = null;
        } else {
            $type = $config['type'];
            $config_data = $config['data'];
        }

        $values = $this->getValues($type, $config_data, $mode);
        if ($values === false) {
            return false;
        }

        echo "<table class='package_item'>";
        echo "<tr>";
        echo "<th>" . __('Audit label', 'glpiinventory') . "</th>";
        echo "<td><input type='text' name='name' id='check_name{$rand}' value=\"{$values['name_value']}\" /></td>";
        echo "</tr>";
        echo "<th>{$values['path_label']}</th>";
        echo "<td><input type='text' name='path' id='check_path{$rand}' value=\"{$values['path_value']}\" />";
        if ($values['path_comment']) {
            echo "<br/><i>" . $values['path_comment'] . "</i>";
        }
        echo "</td>";
        echo "</tr>";

        if ($values['value_label'] !== false) {
            echo "<tr>";
            echo "<th>{$values['value_label']}</th>";

            switch ($values['value_type']) {
                case "textarea":
                    echo "<td><textarea name='value' id='check_value{$rand}' rows='5'>" .
                    $values['value'] . "</textarea></td>";
                    break;

                case "input":
                    echo "<td><input type='text' name='value' id='check_value{$rand}' value='" .
                    $values['value'] . "' /></td>";
                    break;

                case 'registry_type':
                    echo "<td>";
                    $this->dropdownRegistryTypes($values['value']);
                    echo "</td>";
                    break;

                case "input+unit":
                    $value = $values['value'];
                   // freespaceGreater check is saved as MiB
                    if ($type == 'freespaceGreater') {
                        $value = $value * 1024 * 1024;
                    }

                    $options['value'] = 'KB';
                    if ($mode === 'edit') {
                        if ($value >= $this->getUnitSize('GB')) {
                            $value = $value / ($this->getUnitSize('GB'));
                            $options['value'] = 'GB';
                        } elseif ($value >= ($this->getUnitSize('MB'))) {
                            $value = $value / ($this->getUnitSize('MB'));
                            $options['value'] = 'MB';
                        } elseif ($value >= ($this->getUnitSize('KB'))) {
                            $value = $value / ($this->getUnitSize('KB'));
                            $options['value'] = 'KB';
                        } else {
                            $options['value'] = 'B';
                        }
                    }
                    echo "<td>";
                    echo "<input type='text' name='value' id='check_value{$rand}' "
                    . "value='{$value}' />";
                    echo "</td>";
                    echo "</tr><tr>";
                    echo "<th>" . __("Unit", 'glpiinventory') . "</th>";
                    echo "<td>";
                    $unit_labels = $this->getUnitLabel();

                   /*
                   * The freespaceGreater check does not need to propose KiB or B
                   * because its value is based on MiB according to REST API.
                   *                               -- Kevin 'kiniou' Roy
                   */

                    if ($type == 'freespaceGreater') {
                        unset($unit_labels['KB']);
                        unset($unit_labels['B']);
                    }

                    Dropdown::showFromArray('unit', $unit_labels, $options);
                    echo "</td>";
                    break;
            }
            echo "</tr>";
        }

        echo "<tr>";
        echo "<th>" . __("If not successful", 'glpiinventory') . "</th>";
        echo "<td>";
        Dropdown::showFromArray(
            'return',
            $this->getAllReturnValues(),
            ['value' => $values['return']]
        );
        echo "</td>";
        echo "</tr>";

        if ($values['warning_message']) {
            echo "<tr>";
            echo "<td></td>";
            echo "<td>";
            echo "<img src='" . $CFG_GLPI['root_doc'] . "/pics/warning_min.png'>";
            echo "<span class='red'><i>" . $values['warning_message'] . "</i></span></td>";
            echo "</tr>";
        }

        $this->addOrSaveButton($pfDeployPackage, $mode);

        echo "</table>";
    }


   /**
   * Get all possible return values for a check
   * @return an array of return values and their labels
   */
    public function getAllReturnValues()
    {
        return  ["error"   => __('abort job', 'glpiinventory'),
               "skip"    => __("skip job", 'glpiinventory'),
               "startnow" => __("start job now", 'glpiinventory'),
               "info"    => __("report info", 'glpiinventory'),
               "warning" => __("report warning", 'glpiinventory')
              ];
    }


   /**
   * Get the label for a return value
   * @param the check return value
   * @return the label for the return value
   */
    public function getValueForReturn($value)
    {
        $values = $this->getAllReturnValues();
        if (isset($values[$value])) {
            return $values[$value];
        } else {
            return '';
        }
    }


   /**
   * Return an array corresponding to a check, ready to be serialized
   * @param params the check's parameters
   * @return array the array to be encoded in json and serialized
   */
    public static function formatCheckForJson($params)
    {
        if (!isset($params['value'])) {
            $params['value'] = "";
        }
        if (!isset($params['name'])) {
            $params['name'] = "";
        }

        if (!empty($params['unit'])) {
            $params['value'] = str_replace(",", ".", $params['value']);
            if (!empty($params['value']) && is_numeric($params['value'])) {
               //Make an exception for freespaceGreater check which is saved as MiB
                if ($params['checkstype'] == "freespaceGreater") {
                    $params['value'] = $params['value'] / (1024 * 1024);
                } else {
                    $params['value'] = $params['value'] * self::getUnitSize($params['unit']);
                }
            }
        }

       //prepare updated check entry to insert in json
        $entry = [
         'name'   => $params['name'],
         'type'   => $params['checkstype'],
         'path'   => $params['path'],
         'value'  => strval($params['value']),
         'return' => $params['return']
        ];

        return $entry;
    }


   /**
    * Add a new item in checks of the package
    *
    * @param array $params list of fields with value of the check
    */
    public function add_item($params)
    {
        $entry = self::formatCheckForJson($params);

       //get current order json
        $datas = json_decode(
            $this->getJson($params['id']),
            true
        );

       //add new entry
        $datas['jobs']['checks'][] = $entry;

       //Add to package defintion
        $this->addToPackage($params['id'], $entry, 'checks');
    }


   /**
    * Save the item in checks
    *
    * @param array $params list of fields with value of the check
    */
    public function save_item($params)
    {
        $entry = self::formatCheckForJson($params);
       //get current order json
        $datas = json_decode($this->getJson($params['id']), true);

       //unset index
        unset($datas['jobs']['checks'][$params['index']]);

       //add new datas at index position
       //(array_splice for insertion, ex : http://stackoverflow.com/a/3797526)
        array_splice($datas['jobs']['checks'], $params['index'], 0, [$entry]);

       //update order
        $this->updateOrderJson($params['id'], $datas);
    }
}
