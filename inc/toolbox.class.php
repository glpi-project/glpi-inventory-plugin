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
 * Manage the functions used in many classes.
 **/
class PluginGlpiinventoryToolbox
{


   /**
    * Log if extra debug enabled
    *
    * @param string $file
    * @param string $message
    */
    publicstatic  function logIfExtradebug($file, $message)
    {
        $config = new PluginGlpiinventoryConfig();
        if (PluginGlpiinventoryConfig::isExtradebugActive()) {
            if (is_array($message)) {
                $message = print_r($message, true);
            }
            Toolbox::logInFile($file, $message . "\n", true);
        }
    }


   /**
    * Format XML, ie indent it for pretty printing
    *
    * @param object $xml simplexml instance
    * @return string
    */
    publicstatic  function formatXML($xml)
    {
        $string     = str_replace("><", ">\n<", $xml->asXML());
        $token      = strtok($string, "\n");
        $result     = '';
        $pad        = 0;
        $matches    = [];
        $indent     = 0;

        while ($token !== false) {
           // 1. open and closing tags on same line - no change
            if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) {
                $indent = 0;
                // 2. closing tag - outdent now
            } elseif (preg_match('/^<\/\w/', $token, $matches)) {
                $pad = $pad - 3;
               // 3. opening tag - don't pad this one, only subsequent tags
            } elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches)) {
                $indent = 3;
            } else {
                $indent = 0;
            }

            $line    = Toolbox::str_pad($token, strlen($token) + $pad, '  ', STR_PAD_LEFT);
            $result .= $line . "\n";
            $token   = strtok("\n");
            $pad    += $indent;
            $indent = 0;
        }

        return $result;
    }


   /**
    * Add AUTHENTICATION string to XML node
    *
    * @param integer $p_id Authenticate id
    **/
    public function addAuth($p_id)
    {
        $node = [];
        $credentials = new SNMPCredential();
        if ($credentials->getFromDB($p_id)) {
            $node = [
            'AUTHENTICATION' => [
               'ID' => $p_id,
               'VERSION' => $credentials->getRealVersion()
            ]
            ];

            if ($credentials->fields['snmpversion'] == '3') {
                $node['AUTHENTICATION']['USERNAME'] = $credentials->fields['username'];
                if ($credentials->fields['authentication'] != '0') {
                    $node['AUTHENTICATION']['AUTHPROTOCOL'] = $credentials->getAuthProtocol();
                }
                $node['AUTHENTICATION']['AUTHPASSPHRASE'] = (new GLPIKey())->decrypt($credentials->fields['auth_passphrase']);
                if ($credentials->fields['encryption'] != '0') {
                    $node['AUTHENTICATION']['PRIVPROTOCOL'] = $credentials->getEncryption();
                }
                $node['AUTHENTICATION']['PRIVPASSPHRASE'] = (new GLPIKey())->decrypt($credentials->fields['priv_passphrase']);
            } else {
                $node['AUTHENTICATION']['COMMUNITY'] = $credentials->fields['community'];
            }
        }

        return $node;
    }


   /**
    * Get IP for device
    *
    * @param string $itemtype
    * @param integer $items_id
    * @return array
    */
    publicstatic  function getIPforDevice($itemtype, $items_id)
    {
        $NetworkPort = new NetworkPort();
        $networkName = new NetworkName();
        $iPAddress   = new IPAddress();

        $a_ips = [];
        $a_ports = $NetworkPort->find(
            ['itemtype'           => $itemtype,
             'items_id'           => $items_id,
            'instantiation_type' => ['!=',
            'NetworkPortLocal']]
        );
        foreach ($a_ports as $a_port) {
            $a_networknames = $networkName->find(
                ['itemtype' => 'NetworkPort',
                'items_id' => $a_port['id']]
            );
            foreach ($a_networknames as $a_networkname) {
                 $a_ipaddresses = $iPAddress->find(
                     ['itemtype' => 'NetworkName',
                     'items_id' => $a_networkname['id']]
                 );
                foreach ($a_ipaddresses as $data) {
                    if (
                        $data['name'] != '127.0.0.1'
                           && $data['name'] != '::1'
                    ) {
                        $a_ips[$data['name']] = $data['name'];
                    }
                }
            }
        }
        return array_unique($a_ips);
    }


   // *********************** Functions used for inventory *********************** //


   /**
    * Send the XML (last inventory) to user browser (to download)
    *
    * @param integer $items_id
    * @param string $itemtype
    */
    publicstatic  function sendXML($items_id, $itemtype)
    {
        if (
            preg_match("/^([a-zA-Z]+)\/(\d+)\/(\d+)\.xml$/", $items_id)
            && call_user_func([$itemtype, 'canView'])
        ) {
            $xml = file_get_contents(GLPI_PLUGIN_DOC_DIR . "/glpiinventory/xml/" . $items_id);
            echo $xml;
        } else {
            Html::displayRightError();
        }
    }


   /**
    *  This function fetch rows from a MySQL result in an array with each table as a key
    *
    *  example:
    *  $query =
    *     "SELECT table_a.*,table_b.* ".
    *     "FROM table_b ".
    *     "LEFT JOIN table_a ON table_a.id = table_b.linked_id";
    *  $result = mysqli_query( $query );
    *  print_r( fetchTableAssoc( $result ) )
    *
    *  output:
    *  $results = Array
    *     (
    *        [0] => Array
    *           (
    *              [table_a] => Array
    *                 (
    *                    [id] => 1
    *                 )
    *              [table_b] => Array
    *                 (
    *                    [id] => 2
    *                    [linked_id] => 1
    *                 )
    *           )
    *           ...
    *     )
    *
    * @param object $mysql_result
    * @return array
    */
    publicstatic  function fetchAssocByTable($mysql_result)
    {
        $results = [];
       //get fields header infos
        $fields = mysqli_fetch_fields($mysql_result);
       //associate row data as array[table][field]
        while ($row = mysqli_fetch_row($mysql_result)) {
            $result = [];
            for ($i = 0; $i < count($row); $i++) {
                $tname = $fields[$i]->table;
                $fname = $fields[$i]->name;
                if (!isset($result[$tname])) {
                    $result[$tname] = [];
                }
                $result[$tname][$fname] = $row[$i];
            }
            if (count($result) > 0) {
                $results[] = $result;
            }
        }
        return $results;
    }


   /**
    * Format a json in a pretty json
    *
    * @param string $json
    * @return string
    */
    publicstatic  function formatJson($json)
    {
        $version = phpversion();

        if (version_compare($version, '5.4', 'lt')) {
            return pretty_json($json);
        } elseif (version_compare($version, '5.4', 'ge')) {
            return json_encode(
                json_decode($json, true),
                JSON_PRETTY_PRINT
            );
        }
    }


   /**
    * Dropdown for display hours
    *
    * @param string $name
    * @param array $options
    * @return string unique html element id
    */
    publicstatic  function showHours($name, $options = [])
    {

        $p['value']          = '';
        $p['display']        = true;
        $p['width']          = '80%';
        $p['step']           = 5;
        $p['begin']          = 0;
        $p['end']            = (24 * 3600);

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }
        if ($p['step'] <= 0) {
            $p['step'] = 5;
        }

        $values   = [];

        $p['step'] = $p['step'] * 60; // to have in seconds
        for ($s = $p['begin']; $s <= $p['end']; $s += $p['step']) {
            $values[$s] = PluginGlpiinventoryToolbox::getHourMinute($s);
        }
        return Dropdown::showFromArray($name, $values, $p);
    }


   /**
    * Get hour:minute from number of seconds
    *
    * @param integer $seconds
    * @return string
    */
    publicstatic  function getHourMinute($seconds)
    {
        $hour = floor($seconds / 3600);
        $minute = (($seconds - ((floor($seconds / 3600)) * 3600)) / 60);
        return sprintf("%02s", $hour) . ":" . sprintf("%02s", $minute);
    }


   /**
    * Get information if allow_url_fopen is activated and display message if not
    *
    * @param integer $wakecomputer (1 if it's for wakeonlan, 0 if it's for task)
    * @return boolean
    */
    publicstatic  function isAllowurlfopen($wakecomputer = 0)
    {

        if (!ini_get('allow_url_fopen')) {
            echo "<center>";
            echo "<table class='tab_cadre' height='30' width='700'>";
            echo "<tr class='tab_bg_1'>";
            echo "<td align='center'><strong>";
            if ($wakecomputer == '0') {
                echo __('PHP allow_url_fopen is off, remote can\'t work') . " !";
            } else {
                echo __('PHP allow_url_fopen is off, can\'t wake agent to do inventory') . " !";
            }
            echo "</strong></td>";
            echo "</tr>";
            echo "</table>";
            echo "</center>";
            echo "<br/>";
            return false;
        }
        return true;
    }


   /**
    * Execute a function as as pllugin user
    *
    * @param string|array $function
    * @param array $args
    * @return array the normaly returned value from executed callable
    */
    public function executeAsGlpiinventoryUser($function, array $args = [])
    {

        $config = new PluginGlpiinventoryConfig();
        $user = new User();

       // Backup _SESSION environment
        $OLD_SESSION = [];

        foreach (
            ['glpiID', 'glpiname','glpiactiveentities_string',
            'glpiactiveentities', 'glpiparententities'] as $session_key
        ) {
            if (isset($_SESSION[$session_key])) {
                $OLD_SESSION[$session_key] = $_SESSION[$session_key];
            }
        }

       // Configure impersonation
        $users_id  = $config->getValue('users_id');
        $user->getFromDB($users_id);

        $_SESSION['glpiID']   = $users_id;
        $_SESSION['glpiname'] = $user->getField('name');
        $_SESSION['glpiactiveentities'] = getSonsOf('glpi_entities', 0);
        $_SESSION['glpiactiveentities_string'] =
         "'" . implode("', '", $_SESSION['glpiactiveentities']) . "'";
        $_SESSION['glpiparententities'] = [];

       // Execute function with impersonated SESSION
        $result = call_user_func_array($function, $args);

       // Restore SESSION
        foreach ($OLD_SESSION as $key => $value) {
            $_SESSION[$key] = $value;
        }
       // Return function results
        return $result;
    }


   /**
   * Check if an item is inventoried by plugin
   *
   * @since 9.2
   *
   * @param CommonDBTM $item the item to check
   *
   * @return boolean
   */
    publicstatic  function isAnInventoryDevice($item)
    {
        switch ($item->getType()) {
            case 'Computer':
            case 'NetworkEquipment':
            case 'Printer':
                return $item->isDynamic();
        }

        return $item->isDynamic()
         && countElementsInTable(
             RuleMatchedLog::getTable(),
             ['itemtype' => $item->getType(), 'items_id' => $item->fields['id']]
         );
    }
}
