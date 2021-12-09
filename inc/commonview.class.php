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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * It's a common code for display information in GLPI.
 */
class PluginGlpiinventoryCommonView extends CommonDBTM
{

   /**
    * Define the number for the message information constant
    *
    * @var interger
    */
    const MSG_INFO = 0;

   /**
    * Define the number for the message warning constant
    *
    * @var interger
    */
    const MSG_WARNING = 1;

   /**
    * Define the number for the message error constant
    *
    * @var interger
    */
    const MSG_ERROR = 2;

   /**
    * Define default value for the base URLs
    *
    * @var array
    */
    public $base_urls = [];


   /**
    * __contruct function and the different base URLs
    *
    * @global array $CFG_GLPI
    */
    public function __construct()
    {
        global $CFG_GLPI;
        parent::__construct();

        $fi_path = Plugin::getWebDir('glpiinventory');

        $this->base_urls = [
         'fi.base'   => $fi_path,
         'fi.ajax'   => $fi_path . "/ajax",
         'fi.front'  => $fi_path . "/front",
         'fi.pics'   => $fi_path . "/pics",
         'glpi.pics' => $CFG_GLPI['root_doc'] . "/pics",
        ];
    }


   /**
    * Get a specific url root by type name
    *
    * @param string $name the type of url requested (can be used for ajax call
    *                     or pictures location)
    * @return string the requested url if found otherwise empty string
    */
    function getBaseUrlFor($name)
    {
        if (array_key_exists($name, $this->base_urls)) {
            return $this->base_urls[$name];
        }
        trigger_error(
            "The requested url type '$name' doesn't exists. " .
            "Maybe the developper have forgotten to register it in the 'base_urls' variable."
        );
        return "";
    }


   /**
    * Show Search list for this itemtype
    */
    public function showList()
    {
        Search::show(get_class($this));
    }


   /**
    * Display input form element
    *
    * @param string $title
    * @param string $varname
    */
    public function showTextField($title, $varname)
    {
        echo "<div class='mb-2 row col-12 col-sm-6'>";
        echo "<label class='form-label col-sm-4 col-form-label'>" . $title . "&nbsp;:</label>";
        echo "<div class='col-sm-6'>";
        echo Html::input($varname, ['value' => $this->fields[$varname]]);
        echo "</div>";
        echo "</div>";
    }


   /**
    * Display input form element only with numbers
    *
    * @param string $title
    * @param string $varname
    * @param array $options
    */
    public function showIntegerField($title, $varname, $options = [])
    {
        echo "<div class='mb-2 row col-12 col-sm-6'>";
        echo "<label class='form-label col-sm-4 col-form-label'>" . $title . "&nbsp;:</label>";
        echo "<div class='col-sm-6'>";
        Dropdown::showNumber($varname, $options);
        echo "</div>";
        echo "</div>";
    }


   /**
    * Display checkbox form element
    *
    * @param string $title
    * @param string $varname
    * @param array $options
    */
    public function showCheckboxField($title, $varname, $options = [])
    {
        echo "<div class='mb-2 row col-12 col-sm-6'>";
        echo "<label class='form-label col-sm-4 col-form-label'>" . $title . "&nbsp;:" . "</label>";
        echo "<div class='col-sm-6'>";
        $options['name'] = $varname;
        $options['checked'] = $this->fields[$varname];
        $options['zero_on_empty'] = true;

        Html::showCheckbox($options);
        echo "</div>";
        echo "</div>";
    }


   /**
    * Display dropdown form element for itemtype
    *
    * @param string $title
    * @param string $itemtype a glpi/plugin itemtype
    * @param array $options
    * @return string the rand number can be used with ajax to update something
    */
    public function showDropdownForItemtype($title, $itemtype, $options = [])
    {
        echo "<div class='mb-2 row col-12 col-sm-6'>";
        echo "<label class='form-label col-sm-4 col-form-label'>" . $title . "&nbsp;:" . "</label>";
        echo "<div class='col-sm-6'>";
        $dropdown_options = array_merge(
            [
            'width' => '90%',
            'display' => true,
            ],
            $options
        );
        $rand = Dropdown::show($itemtype, $dropdown_options);
        echo "</div>";
        echo "</div>";
        return $rand;
    }


   /**
    * Display dropdown form element with array data
    *
    * @param string $title
    * @param string $varname
    * @param array $values
    * @param array $options
    * @return string the rand number can be used with ajax to update something
    */
    public function showDropdownFromArray($title, $varname, $values = [], $options = [])
    {
        echo "<div class='mb-2 row col-12 col-sm-6'>";
        echo "<label class='form-label col-sm-4 col-form-label'>" . $title . "&nbsp;:" . "</label>";
        echo "<div class='col-sm-6'>";
        if (!isset($options['width'])) {
            $options['width'] = '100%';
        }

        if (!is_null($varname)) {
            $options['value'] = $this->fields[$varname];
        }
        $rand = Dropdown::showFromArray(
            $varname,
            $values,
            $options
        );
        echo "</div>";
        echo "</div>";
        return $rand;
    }


   /**
    * Display date time select form element
    *
    * @param string $title
    * @param string $varname
    * @param array $options
    */
    public function showDateTimeField($title, $varname, $options = [])
    {

       // Get datetime value if the object is defined
        if ($this->fields['id'] > 0) {
            $value = $this->fields[$varname];
        } else {
           // Else set default value to current date and time
            if (
                array_key_exists('maybeempty', $options)
                 and $options['maybeempty']
            ) {
                $value = "";
            } else {
                $value = date("Y-m-d H:i:s");
            }
        }
        $options['value'] = $value;

        echo "<div class='mb-2 row col-12 col-sm-6'>";
        echo "<label class='form-label col-sm-4 col-form-label'>" . $title . "&nbsp;:</label>";
        echo "<div class='col-sm-6 datetime'>";
        Html::showDateTimeField(
            $varname,
            $options
        );
        echo "</div>";
        echo "</div>";
    }


   /**
    * Display a text area form element
    *
    * @param string $title
    * @param string $varname
    */
    public function showTextArea($title, $varname)
    {
        echo "<div class='mb-2 row col-12 col-sm-6'>";
        echo "<label class='form-label col-sm-4 col-form-label'>" . $title . "&nbsp;:</label>";
        echo "<div class='col-sm-6'>";
        echo
         "<textarea class='autogrow form-control' name='" . $varname . "' >" .
         $this->fields[$varname] .
         "</textarea>";
        echo "</div>";
        echo "</div>";
    }


   /**
    * Get a HTML message
    *
    * @param string $msg
    * @param integer $type
    * @return string
    */
    public function getMessage($msg, $type = self::MSG_INFO)
    {
        switch ($type) {
            case self::MSG_WARNING:
                $msg = __('Warning:', 'glpiinventory') . " $msg";
                $class_msg = 'warning';
                break;

            case self::MSG_ERROR:
                $msg = __('Error:', 'glpiinventory') . " $msg";
                $class_msg = 'error';
                break;

            case self::MSG_INFO:
            default:
                $class_msg = '';
                break;
        }
        return "<div class='box' style='margin-bottom:20px;'>
               <div class='box-tleft'>
                  <div class='box-tright'>
                     <div class='box-tcenter'></div>
                  </div>
               </div>
               <div class='box-mleft'>
                  <div class='box-mright'>
                     <div class='box-mcenter'>
                        <span class='b $class_msg'>$msg</span>
                     </div>
                  </div>
               </div>
               <div class='box-bleft'>
                  <div class='box-bright'>
                     <div class='box-bcenter'></div>
                  </div>
               </div>
              </div>";
    }
}
