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

/**
 * Manage user interactions.
 */
class PluginGlpiinventoryDeployUserinteraction extends PluginGlpiinventoryDeployPackageItem
{
    public string $shortname = 'userinteractions';
    public string $json_name = 'userinteractions';

    //--------------- Events ---------------------------------------//

    //Audits are all been executed successfully, just before download
    public const EVENT_BEFORE_DOWNLOAD    = 'before';
    //File download has been done, just before actions execution
    public const EVENT_AFTER_DOWNLOAD  = 'after_download';
    //Actions have been executed, deployement is finished
    public const EVENT_AFTER_ACTIONS   = 'after';
    //At least one downlod has failed
    public const EVENT_DOWNLOAD_FAILURE = 'after_download_failure';
    //At least one action has failed
    public const EVENT_ACTION_FAILURE   = 'after_failure';

    //--------------- Responses ---------------------------------------//

    //The agent notice that the job must continue
    public const RESPONSE_CONTINUE        = 'continue';

    //The agent notice that the job must be postponed
    public const RESPONSE_POSTPONE        = 'postpone';

    //The agent notice that the job must be canceled
    public const RESPONSE_STOP            = 'stop';

    //The agent received a malformed or non existing event
    public const RESPONSE_BAD_EVENT       = 'error_bad_event';

    /**
     * Get name of this type by language of the user connected
     *
     * @param int $nb number of elements
     * @return string name of this type
     */
    public static function getTypeName($nb = 0)
    {
        return _n(
            'User interaction',
            'User interactions',
            $nb,
            'glpiinventory'
        );
    }


    /**
     * Get events with name => description
     *
     * @return array<string,string|array<string,string>>
     */
    public function getTypes()
    {
        return [
            self::EVENT_BEFORE_DOWNLOAD  => __("Before download", 'glpiinventory'),
            self::EVENT_AFTER_DOWNLOAD   => __("After download", 'glpiinventory'),
            self::EVENT_AFTER_ACTIONS    => __("After actions", 'glpiinventory'),
            self::EVENT_DOWNLOAD_FAILURE => __("On download failure", 'glpiinventory'),
            self::EVENT_ACTION_FAILURE   => __("On actions failure", 'glpiinventory'),
        ];
    }


    /**
     * Get an event label by its identifier
     * @param string $type event identifier
     * @return string
     */
    public function getLabelForAType(string $type): string
    {
        $events = $this->getTypes();
        return $events[$type] ?? '';
    }


    public function displayAjaxValues(?array $config, array $request_data, string $rand, string $mode): void
    {
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

        echo "<table class='package_item'>";
        echo "<tr>";
        echo "<th>{$values['name_label']}</th>";
        echo "<td><input type='text' name='name' id='userinteraction_name{$rand}' value=\"{$values['name_value']}\" /></td>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>{$values['title_label']}</th>";
        echo "<td><input type='text' name='title' id='userinteraction_title{$rand}' value=\"{$values['title_value']}\" />";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>{$values['description_label']}</th>";
        echo "<td><textarea name='text' id='userinteraction_description{$rand}' rows='5'>{$values['description_value']}</textarea>";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>{$values['template_label']}</th>";
        echo "<td>";
        Dropdown::show(
            PluginGlpiinventoryDeployUserinteractionTemplate::class,
            ['value' => $values['template_value'], 'name' => 'template']
        );
        echo "</td>";
        echo "</tr>";

        $this->addOrSaveButton($pfDeployPackage, $mode);

        echo "</table>";
    }


    /**
     * Get fields for the check type requested
     *
     * @param string $type the type of check
     * @param array<string,mixed> $data fields yet defined in edit mode
     * @param string $mode mode in use (create, edit...)
     *
     * @return array<string,mixed>
     */
    public function getValues($type, $data, $mode)
    {
        $values = [
            'name_value'          => "",
            'name_label'          => __('Interaction label', 'glpiinventory'),
            'name_type'           => "input",
            'title_label'         => __('Title') . $this->getMandatoryMark(),
            'title_value'         => "",
            'title_type'          => "input",
            'description_label'   => __('Message'),
            'description_type'    => "text",
            'description_value'   => "",
            'template_label'
            => PluginGlpiinventoryDeployUserinteractionTemplate::getTypeName(1)
               . $this->getMandatoryMark(),
            'template_value'      => "",
            'template_type'       => "dropdown",
        ];

        if ($mode === self::EDIT) {
            $values['name_value']        = $data['name'] ?? "";
            $values['title_value']       = $data['title'] ?? "";
            $values['description_value'] = $data['text'] ?? "";
            $values['template_value']    = $data['template'] ?? "";
        }

        return $values;
    }


    /**
     * Display list of user interactions
     *
     * @param PluginGlpiinventoryDeployPackage $package PluginGlpiinventoryDeployPackage instance
     * @param array<string,mixed> $data array converted of 'json' field in DB where stored checks
     * @param string $rand unique element id used to identify/update an element
     */
    public function displayDeployList(PluginGlpiinventoryDeployPackage $package, array $data, string $rand): void
    {
        $package_id        = $package->getID();
        $canedit           = $package->canUpdateContent();
        $i                 = 0;

        echo "<table class='tab_cadrehov package_item_list' id='table_userinteractions_$rand'>";
        foreach ($data['jobs']['userinteractions'] as $interaction) {
            echo Search::showNewLine(Search::HTML_OUTPUT, (bool) ($i % 2));
            if ($canedit) {
                echo "<td class='control'>";
                Html::showCheckbox(['name' => 'userinteractions_entries[' . $i . ']', 'class' => 'massive_action_checkbox']);
                echo "</td>";
            }

            //Get the audit full description (with type and return value)
            //to be displayed in the UI
            $text = $this->getInteractionDescription($interaction);
            echo "<td>";
            if ($canedit) {
                echo "<a class='edit'
                     onclick=\"edit_subtype('userinteraction', $package_id, $rand ,this)\">";
            }
            echo $text;
            if ($canedit) {
                echo "</a>";
            }

            echo "</td>";
            if ($canedit) {
                echo "<td class='rowhandler control' title='" . __('drag', 'glpiinventory')
                . "'><div class='drag row ti ti-menu-2'></div></td>";
            }
            echo "</tr>";
            $i++;
        }
        if ($canedit) {
            echo "<tr><th>";
            echo Html::getCheckAllAsCheckbox("userinteractionsList$rand", mt_rand());
            echo "</th><th colspan='3' class='mark'></th></tr>";
        }
        echo "</table>";
        if ($canedit) {
            echo "<input type='submit' name='delete' value=\""
            . __('Delete', 'glpiinventory') . "\" class='submit' />";
        }
    }


    /**
    * Get of a short description of a user interaction
    *
    * @since 9.2
    * @param array<string,mixed> $interaction an array representing an interaction
    * @return string a short description
    */
    public function getInteractionDescription($interaction)
    {
        $text = '';

        if (isset($interaction['label'])) {
            $text = $interaction['label'];
        } elseif (isset($interaction['name'])) {
            $text .= $interaction['name'];
        }
        $text .= ' - ' . $this->getLabelForAType($interaction['type']);

        if ($interaction['template']) {
            $text .= ' (';
            $text .= Dropdown::getDropdownName(
                'glpi_plugin_glpiinventory_deployuserinteractiontemplates',
                $interaction['template']
            );
            $text .= ')';
        }

        return $text;
    }


    public function add_item(array $params): bool
    {
        if (!isset($params['text'])) {
            $params['text'] = "";
        }
        if (!isset($params['template'])) {
            $params['template'] = 0;
        }

        //prepare new check entry to insert in json
        $entry = [
            'name'        => $params['name'],
            'title'       => $params['title'],
            'text'        => $params['text'],
            'type'        => $params['userinteractionstype'],
            'template'    => $params['template'],
        ];

        //Add to package defintion
        $this->addToPackage($params['id'], $entry, 'userinteractions');
        return true;
    }


    public function save_item(array $params): bool
    {
        if (!isset($params['value'])) {
            $params['value'] = "";
        }
        if (!isset($params['name'])) {
            $params['name'] = "";
        }
        //prepare new check entry to insert in json
        $entry = [
            'name'        => $params['name'],
            'title'       => $params['title'],
            'text'        => $params['text'],
            'type'        => $params['userinteractionstype'],
            'template'    => $params['template'],
        ];

        //update order
        $this->updateOrderJson(
            $params['id'],
            $this->prepareDataToSave($params, $entry)
        );
        return true;
    }


    /**
     * @param PluginGlpiinventoryDeployPackage $package
     * @return array<string>
     */
    public function getTypesAlreadyInUse(PluginGlpiinventoryDeployPackage $package)
    {
        $used_interactions = [];
        $json              = json_decode($package->fields['json'], true);

        if (
            isset($json['jobs'][$this->json_name])
            && !empty($json['jobs'][$this->json_name])
        ) {
            foreach ($json['jobs'][$this->json_name] as $interaction) {
                if (!isset($used_interactions[$interaction['type']])) {
                    $used_interactions[$interaction['type']] = $interaction['type'];
                }
            }
        }
        return $used_interactions;
    }


    /**
    * Get a log message depending on an agent response
    * @since 9.2
    *
    * @param string $behavior the behavior the agent must adopt for the job
    * @param string $type the type of event that triggered the user interaction
    * @param string $event the button clicked by the user
    *         (or the what's happened in special cases, as defined in a template)
    * @param int $user userid the user who performed the interaction
    * @return string the message to be display in a taskjob log
    */
    public function getLogMessage($behavior, $type, $event, $user)
    {
        $message  = self::getTypeName(1);
        $message .= ': ' . $this->getLabelForAType($type);
        $message .= '/';
        switch ($behavior) {
            case self::RESPONSE_STOP:
                $message .= sprintf(__(
                    'Job cancelled by the user %1$s',
                    'glpiinventory'
                ), $user);
                break;

            case self::RESPONSE_CONTINUE:
                $message .= sprintf(__(
                    'User %1$s agreed to continue the job',
                    'glpiinventory'
                ), $user);
                break;

            case self::RESPONSE_POSTPONE:
                $message .= sprintf(
                    __('Job postponed by the user %1$s', 'glpiinventory'),
                    $user
                );
                break;

            case self::RESPONSE_BAD_EVENT:
                $message .= __('Bad event sent to the agent', 'glpiinventory');
                break;
        }
        $message .= ' (' . $this->getEventMessage($event) . ')';
        return $message;
    }


    public function getEventMessage(string $event = ''): string
    {
        $message = __('%1$s button pressed');
        switch ($event) {
            case 'on_ok':
                return sprintf($message, __('OK'));

            case 'on_yes':
                return sprintf($message, __('Yes'));

            case 'on_async':
                return __('Alert displayed, no input required', 'glpiinventory');

            case 'on_no':
                return sprintf($message, __('No'));

            case 'on_retry':
                return sprintf($message, __('Retry', 'glpiinventory'));

            case 'on_cancel':
                return sprintf($message, __('Cancel'));

            case 'on_abort':
                return sprintf($message, __('Abort', 'glpiinventory'));

            case 'on_ignore':
                return sprintf($message, __('Ignore', 'glpiinventory'));

            case 'on_continue':
                return sprintf($message, __('Continue'));

            case 'on_timeout':
                return __('Alert duration exceeded', 'glpiinventory');

            case 'on_nouser':
                return __('No user connected', 'glpiinventory');

            case 'on_multiusers':
                return __('Multiple users connected', 'glpiinventory');
        }
        return '';
    }

    public static function getIcon()
    {
        return "ti ti-hand-finger";
    }
}
