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

use Glpi\Application\View\TemplateRenderer;

/**
 * Manage user interactions templates.
 * @since 9.2
 */
class PluginGlpiinventoryDeployUserinteractionTemplate extends CommonDropdown
{
   /**
    * The right name for this class
    *
    * @var string
    */
    public static $rightname = 'plugin_glpiinventory_userinteractiontemplate';

    const ALERT_WTS                = 'win32'; //Alerts for win32 platform (WTS API)

   //Behaviors (to sent to the agent) :
   //in two parts :
   //- left part is the instruction for the agent
   //- right part is the code that the agent returns to the server

   //Continue a software deployment
    const BEHAVIOR_CONTINUE_DEPLOY = 'continue:continue';

   //Cancel a software deployment
    const BEHAVIOR_STOP_DEPLOY     = 'stop:stop';

   //Postpone a software deployment
    const BEHAVIOR_POSTPONE_DEPLOY = 'stop:postpone';

   //Available buttons for Windows WTS API
    const WTS_BUTTON_OK_SYNC             = 'ok';
    const WTS_BUTTON_OK_ASYNC            = 'ok_async';
    const WTS_BUTTON_OK_CANCEL           = 'okcancel';
    const WTS_BUTTON_YES_NO              = 'yesno';
    const WTS_BUTTON_ABORT_RETRY_IGNORE  = 'abortretryignore';
    const WTS_BUTTON_RETRY_CANCEL        = 'retrycancel';
    const WTS_BUTTON_YES_NO_CANCEL       = 'yesnocancel';
    const WTS_BUTTON_CANCEL_TRY_CONTINUE = 'canceltrycontinue';

   //Icons to be displayed
    const WTS_ICON_NONE                = 'none';
    const WTS_ICON_WARNING             = 'warn';
    const WTS_ICON_QUESTION            = 'question';
    const WTS_ICON_INFO                = 'info';
    const WTS_ICON_ERROR               = 'error';


   /**
    * @see CommonGLPI::defineTabs()
   **/
    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addStandardTab(__CLASS__, $ong, $options)
         ->addStandardTab('Log', $ong, $options);

        return $ong;
    }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $tabs[1] = __('General');
        $tabs[2] = _n('Behavior', 'Behaviors', 2, 'glpiinventory');
        return $tabs;
    }


   /**
    * @param $item         CommonGLPI object
    * @param $tabnum       (default 1)
    * @param $withtemplate (default 0)
   **/
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        global $CFG_GLPI;

        if ($item->getType() == __CLASS__) {
            switch ($tabnum) {
                case 1:
                    $item->showForm($item->fields['id']);
                    break;

                case 2:
                    $item->showBehaviors($item->fields['id']);
                    break;
            }
        }
        return true;
    }


   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
    public static function getTypeName($nb = 0)
    {
         return _n(
             'User interaction template',
             'User interaction templates',
             $nb,
             'glpiinventory'
         );
    }


   /**
    * Get list of supported interaction methods
    *
    * @since 9.2
    * @return array
    */
    public static function getTypes()
    {
        return [self::ALERT_WTS
               => __("Windows system alert (WTS)", 'glpiinventory')];
    }


   /**
    * Get available buttons for alerts, by interaction type
    *
    * @since 9.2
    * @param interaction_type the type of interaction
    * @return array
    */
    public static function getButtons($interaction_type = '')
    {
        $interactions = [
         self::ALERT_WTS => [
            self::WTS_BUTTON_OK_SYNC             => __('OK', 'glpiinventory'),
            self::WTS_BUTTON_OK_ASYNC            => __('OK (asynchronous)', 'glpiinventory'),
            self::WTS_BUTTON_OK_CANCEL           => __('OK - Cancel', 'glpiinventory'),
            self::WTS_BUTTON_YES_NO              => __('Yes - No', 'glpiinventory'),
            self::WTS_BUTTON_ABORT_RETRY_IGNORE  => __('OK - Abort - Retry', 'glpiinventory'),
            self::WTS_BUTTON_RETRY_CANCEL        => __('Retry - Cancel', 'glpiinventory'),
            self::WTS_BUTTON_ABORT_RETRY_IGNORE  => __('Abort - Retry - Ignore', 'glpiinventory'),
            self::WTS_BUTTON_CANCEL_TRY_CONTINUE => __('Cancel - Try - Continue', 'glpiinventory'),
            self::WTS_BUTTON_YES_NO_CANCEL       => __('Yes - No - Cancel', 'glpiinventory')
         ]
        ];
        if (isset($interactions[$interaction_type])) {
            return $interactions[$interaction_type];
        } else {
            return false;
        }
    }


   /**
    * Get available icons for alerts, by interaction type
    *
    * @since 9.2
    * @param interaction_type the type of interaction
    * @return array
    */
    public static function getIcons($interaction_type = self::ALERT_WTS)
    {
        $icons = [
         self::ALERT_WTS => [
            self::WTS_ICON_NONE     => __('None'),
            self::WTS_ICON_WARNING  => __('Warning'),
            self::WTS_ICON_INFO     => _n('Information', 'Informations', 1),
            self::WTS_ICON_ERROR    => __('Error'),
            self::WTS_ICON_QUESTION => __('Question', 'glpiinventory')
         ]
        ];
        if (isset($icons[$interaction_type])) {
            return $icons[$interaction_type];
        } else {
            return false;
        }
    }


   /**
    * Get available behaviors in case of user interactions
    *
    * @since 9.2
    * @return array
    */
    public static function getBehaviors()
    {
        return [self::BEHAVIOR_CONTINUE_DEPLOY => __('Continue job with no user interaction', 'glpiinventory'),
              self::BEHAVIOR_POSTPONE_DEPLOY => __('Retry job later', 'glpiinventory'),
              self::BEHAVIOR_STOP_DEPLOY     => __('Cancel job', 'glpiinventory')
             ];
    }


   /**
    * Display a dropdown with the list of available behaviors
    *
    * @since 9.2
    * @param type the type of bahaviors (if one already selected)
    * @return rand
    */
    public function dropdownBehaviors($name, $behavior = self::BEHAVIOR_CONTINUE_DEPLOY)
    {
        return Dropdown::showFromArray(
            $name,
            self::getBehaviors(),
            ['value' => $behavior]
        );
    }


   /**
   * Get the fields to be encoded in json
   * @since 9.2
   * @return an array of field names
   */
    public function getJsonFields()
    {
        return  array_merge(
            $this->getMainFormFields(),
            $this->getBehaviorsFields()
        );
    }


   /**
   * Get the fields to be encoded in json
   * @since 9.2
   * @return an array of field names
   */
    public function getMainFormFields()
    {
        return  ['platform', 'timeout', 'buttons', 'icon',
               'retry_after', 'nb_max_retry'];
    }


   /**
   * Get the fields to be encoded in json
   * @since 9.2
   * @return an array of field names
   */
    public function getBehaviorsFields()
    {
        return  ['on_timeout', 'on_nouser', 'on_multiusers', 'on_ok', 'on_no',
               'on_yes', 'on_cancel', 'on_abort', 'on_retry', 'on_tryagain',
               'on_ignore', 'on_continue', 'on_async'];
    }


   /**
   * Initialize json fields
   * @since 9.2
   *
   * @return an array of field names
   */
    public function initializeJsonFields($json_fields)
    {
        foreach ($this->getJsonFields() as $field) {
            if (!isset($json_fields[$field])) {
                $json_fields[$field] = $this->getDefaultBehaviorForAButton($field);
            }
        }
        return $json_fields;
    }


   /**
   * Save form data as a json encoded array
   * @since 9.2
   * @param params form parameters
   * @return json encoded array
   */
    public function saveToJson($params = [])
    {
        $result = [];
        foreach ($this->getJsonFields() as $field) {
            if (isset($params[$field])) {
                $result[$field] = $params[$field];
            }
        }
        return json_encode($result);
    }


   /**
   * Add the json template fields to package
   *
   * @since 9.2
   * @param the input array
   * @param array now containing input data + data from the template
   */
    public function addJsonFieldsToArray($params = [])
    {
        $fields = json_decode($this->fields['json'], true);
        foreach ($this->getJsonFields() as $field) {
            if (isset($fields[$field])) {
                $params[$field] = $fields[$field];
            }
        }
       //If we deal with an asynchronous OK, then wait must be set to 0
        if ($params['buttons'] == self::WTS_BUTTON_OK_ASYNC) {
            $params['buttons'] = self::WTS_BUTTON_OK_SYNC;
            $params['wait']    = 'no';
        } else {
           //Otherwise wait is 1
            $params['wait'] = 'yes';
        }
        return $params;
    }


   /**
   * Display an interaction template form
   * @since 9.2
   * @param $id id of a template to edit
   * @param options POST form options
   */
    public function showForm($id, $options = [])
    {
        $this->initForm($id, $options);

        $json_data = json_decode($this->fields['json'], true);
        $json_data = $this->initializeJsonFields($json_data);

        TemplateRenderer::getInstance()->display('@glpiinventory/forms/deployuserinteractiontemplate.html.twig', [
         'item'      => $this,
         'params'    => $options,
         'json_data' => $json_data,
        ]);

        return true;
    }


   /**
    * Array of Retries values
    *
    *  @return array
    **/
    public static function getRetries()
    {
        $tab = [
        0 => __('Never')
        ];

        $tab[MINUTE_TIMESTAMP]   = sprintf(_n('%d minute', '%d minutes', 1), 1);
        $tab[2 * MINUTE_TIMESTAMP] = sprintf(_n('%d minute', '%d minutes', 2), 2);
        $tab[3 * MINUTE_TIMESTAMP] = sprintf(_n('%d minute', '%d minutes', 3), 3);
        $tab[4 * MINUTE_TIMESTAMP] = sprintf(_n('%d minute', '%d minutes', 4), 4);

       // Minutes
        for ($i = 5; $i < 60; $i += 5) {
            $tab[$i * MINUTE_TIMESTAMP] = sprintf(_n('%d minute', '%d minutes', $i), $i);
        }

       // Heures
        for ($i = 1; $i < 24; $i++) {
            $tab[$i * HOUR_TIMESTAMP] = sprintf(_n('%d hour', '%d hours', $i), $i);
        }

       // Jours
        $tab[DAY_TIMESTAMP] = __('Each day');
        for ($i = 2; $i < 7; $i++) {
            $tab[$i * DAY_TIMESTAMP] = sprintf(_n('%d day', '%d days', $i), $i);
        }

        $tab[WEEK_TIMESTAMP]  = __('Each week');
        $tab[MONTH_TIMESTAMP] = __('Each month');

        return $tab;
    }

   /**
    * Array of frequency (interval between 2 actions)
    *
    *  @return array
    **/
    public static function getTimeouts()
    {
        $tab = [
         0 => __('Never')
        ];

       // Minutes
        for ($i = 30; $i < 60; $i += 5) {
            $tab[$i] = sprintf(_n('%d second', '%d seconds', $i), $i);
        }

        $tab[MINUTE_TIMESTAMP]   = sprintf(_n('%d minute', '%d minutes', 1), 1);
        $tab[2 * MINUTE_TIMESTAMP] = sprintf(_n('%d minute', '%d minutes', 2), 2);
        $tab[3 * MINUTE_TIMESTAMP] = sprintf(_n('%d minute', '%d minutes', 3), 3);
        $tab[4 * MINUTE_TIMESTAMP] = sprintf(_n('%d minute', '%d minutes', 4), 4);

       // Minutes
        for ($i = 5; $i < 60; $i += 5) {
            $tab[$i * MINUTE_TIMESTAMP] = sprintf(_n('%d minute', '%d minutes', $i), $i);
        }

       // Hours
        for ($i = 1; $i < 13; $i++) {
            $tab[$i * HOUR_TIMESTAMP] = sprintf(_n('%d hour', '%d hours', $i), $i);
        }

        return $tab;
    }


   /**
   * Get all events leading to an action on a task
   *
   * @since 9.2
   * @return array an array of event => event label
   */
    public function getEvents()
    {
        return ['on_ok'       => __('Button ok', 'glpiinventory'),
              'on_yes'      => __('Button yes', 'glpiinventory'),
              'on_continue' => __('Button continue', 'glpiinventory'),
              'on_retry'    => __('Button retry', 'glpiinventory'),
              'on_tryagain' => __('Button try', 'glpiinventory'),
              'on_no'       => __('Button no', 'glpiinventory'),
              'on_cancel'   => __('Button cancel', 'glpiinventory'),
              'on_abort'    => __('Button abort', 'glpiinventory'),
              'on_ignore'   => __('Button ignore', 'glpiinventory'),
              'on_nouser'   => __('No active session', 'glpiinventory'),
              'on_timeout'  => __('Alert timeout exceeded', 'glpiinventory'),
              'on_multiusers' => __('Several active sessions', 'glpiinventory')
             ];
    }


   /**
   * Get the behaviors to define for an agent to correctly handle the interaction
   *
   * @since 9.2
   * @param $param the button selected in the interaction template form
   * @return array an array of needed interaction behaviors
   */
    public function getBehaviorsToDisplay($button)
    {
        $display = ['on_timeout', 'on_nouser', 'on_multiusers'];
        switch ($button) {
            case self::WTS_BUTTON_OK_SYNC:
            case self::WTS_BUTTON_OK_ASYNC:
                $display[] = 'on_ok';
                break;

            case self::WTS_BUTTON_YES_NO:
                $display[] = 'on_yes';
                $display[] = 'on_no';
                break;

            case self::WTS_BUTTON_YES_NO_CANCEL:
                $display[] = 'on_yes';
                $display[] = 'on_no';
                $display[] = 'on_cancel';
                break;

            case self::WTS_BUTTON_OK_CANCEL:
                $display[] = 'on_ok';
                $display[] = 'on_cancel';
                break;

            case self::WTS_BUTTON_ABORT_RETRY_IGNORE:
                $display[] = 'on_abort';
                $display[] = 'on_retry';
                $display[] = 'on_ignore';
                break;

            case self::WTS_BUTTON_RETRY_CANCEL:
                $display[] = 'on_retry';
                $display[] = 'on_cancel';
                break;

            case self::WTS_BUTTON_CANCEL_TRY_CONTINUE:
                $display[] = 'on_tryagain';
                $display[] = 'on_cancel';
                $display[] = 'on_continue';
                break;
        }
        return $display;
    }


   /**
   * Get the default behavior for a button
   * @since 9.2
   * @param string $button the button for which the default behavior is request
   * @return string the behavior
   */
    public function getDefaultBehaviorForAButton($button)
    {
        $behavior = '';
        switch ($button) {
            case 'on_yes':
            case 'on_ok':
            case 'on_multiusers':
            case 'on_timeout':
            case 'on_nouser':
                $behavior = self::BEHAVIOR_CONTINUE_DEPLOY;
                break;

            case 'on_no':
            case 'on_cancel':
            case 'on_abort':
                $behavior = self::BEHAVIOR_STOP_DEPLOY;
                break;

            case 'on_retry':
            case 'on_ignore':
            case 'on_tryagain':
                $behavior = self::BEHAVIOR_POSTPONE_DEPLOY;
                break;
        }
        return $behavior;
    }


   /**
   * Show behaviors form
   *
   * @since 9.2
   * @param ID the template's ID
   */
    public function showBehaviors($ID)
    {

        $json_data = json_decode($this->fields['json'], true);
        $json_data = $this->initializeJsonFields($json_data);

        $this->initForm($ID);
        $this->showFormHeader();

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='4'>" . __('Behaviors', 'glpiinventory') . "</th>";
        echo "</tr>";

        foreach ($this->getMainFormFields() as $field) {
            echo Html::hidden($field, ['value' => $json_data[$field]]);
        }

        foreach ($this->getEvents() as $event => $label) {
            if (
                in_array(
                    $event,
                    $this->getBehaviorsToDisplay($json_data['buttons'])
                )
            ) {
                echo "<tr class='tab_bg_1'>";

                echo "<td>$label</td>";
                echo "<td>";
                if (empty($json_data[$event])) {
                    $value = $this->getDefaultBehaviorForAButton($event);
                } else {
                    $value = $json_data[$event];
                }
                $this->dropdownBehaviors($event, $value);
                echo "</td>";
                echo "</tr>";
            } else {
                echo Html::hidden($event, $json_data[$event]);
            }
        }

        $this->showFormButtons();

        return true;
    }


    public function prepareInputForAdd($input)
    {
       //Save params as a json array, ready to be saved in db
        $input['json'] = $this->saveToJson($input);
        return $input;
    }


    public function prepareInputForUpdate($input)
    {
        return $this->prepareInputForAdd($input);
    }


   /**
   * Get temlate values as an array
   * @since 9.2
   * @return array the template values as an array
   */
    public function getValues()
    {
        return json_decode($this->fields['json'], true);
    }
}
