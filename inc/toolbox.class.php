<?php

/**
 * ---------------------------------------------------------------------
 * GLPI Inventory Plugin
 * @basedon   FusionInventory for GLPI
 * @copyright 2021-2026 Teclib' and contributors.
 * @copyright 2010-2021 by the FusionInventory Development Team.
 *
 * http://glpi-project.org
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

use Glpi\Agent\Communication\AbstractRequest;
use Glpi\Asset\AssetDefinition;
use Glpi\Asset\AssetDefinitionManager;
use Glpi\Asset\Capacity\IsInventoriableCapacity;
use Glpi\Inventory\MainAsset\NetworkEquipment as NetworkEquipmentMainAsset;
use Glpi\Inventory\Request;

use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\preg_match;

/**
 * Manage the functions used in many classes.
 **/
class PluginGlpiinventoryToolbox
{
    /**
     * Active custom asset definitions, read before they are booted
     *
     * @var ?array<AssetDefinition>
     */
    private static ?array $preboot_definitions = null;


    /**
     * Log if extra debug enabled
     *
     * @param string $file
     * @param string|array $message //@phpstan-ignore missingType.iterableValue
     */
    public static function logIfExtradebug(string $file, string|array $message): void
    {
        if (PluginGlpiinventoryConfig::isExtradebugActive()) {
            if (is_array($message)) {
                $message = print_r($message, true);
            }
            Toolbox::logInFile($file, $message . "\n", true);
        }
    }


    /**
     * Format XML, ie indent it for pretty printing
     */
    public static function formatXML(SimpleXMLElement $xml): string
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
                $pad -= 3;
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
     * @param int $p_id Authenticate id
     *
     * @return array<string,array<string,mixed>>
     **/
    public function addAuth(int $p_id): array
    {
        $node = [];
        $credentials = new SNMPCredential();
        if ($credentials->getFromDB($p_id)) {
            $node = [
                'AUTHENTICATION' => [
                    'ID' => $p_id,
                    'VERSION' => $credentials->getRealVersion(),
                ],
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
     * @param class-string<CommonDBTM> $itemtype
     * @param int $items_id
     * @return array<string,string>
     */
    public static function getIPforDevice(string $itemtype, int $items_id): array
    {
        $NetworkPort = new NetworkPort();
        $networkName = new NetworkName();
        $iPAddress   = new IPAddress();

        $a_ips = [];
        $a_ports = $NetworkPort->find(
            ['itemtype'           => $itemtype,
                'items_id'           => $items_id,
                'instantiation_type' => ['!=',
                    'NetworkPortLocal',
                ],
            ]
        );
        foreach ($a_ports as $a_port) {
            $a_networknames = $networkName->find(
                ['itemtype' => 'NetworkPort',
                    'items_id' => $a_port['id'],
                ]
            );
            foreach ($a_networknames as $a_networkname) {
                $a_ipaddresses = $iPAddress->find(
                    ['itemtype' => 'NetworkName',
                        'items_id' => $a_networkname['id'],
                    ]
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
     *  This function fetch rows from a DBMysqlIterator result in an array with each table as a key
     *
     *  example:
     *  $iterator = $DB->request([
     *     'SELECT' => ['table_a.*', 'table_b.*'],
     *     'FROM' => 'table_b'
     *     'LEFT JOIN' => [
     *          'table_a' => [
     *              'ON' => [
     *                  'table_a' => id,
     *                  'table_b' => 'linked_id'
     *              ]
     *          ]
     *      ]
     *  ]);
     *  print_r(fetchTableAssocIterator($iterator))
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
     * @param DBmysqlIterator $iterator
     * @return array<int,array<string,array<string,mixed>>>
     */
    public static function fetchAssocByTableIterator(DBmysqlIterator $iterator): array
    {
        $results = [];
        //get fields header infos
        $fields = $iterator->fetchFields();

        //associate row data as array[table][field]
        foreach ($iterator as $row) {
            $result = [];
            $i = 0;
            foreach (array_keys($row) as $col) {
                $tname = $fields[$i]->table;
                $fname = $fields[$i]->orgname;
                if (!isset($result[$tname])) {
                    $result[$tname] = [];
                }
                $result[$tname][$fname] = $row[$col];
                ++$i;
            }

            if (count($result) > 0) {
                $results[] = $result;
            }
        }
        return $results;
    }


    /**
    * Format a JSON in a pretty JSON
    */
    public static function formatJson(string $json): string
    {
        return json_encode(
            json_decode($json, true),
            JSON_PRETTY_PRINT
        );
    }


    /**
     * Get hour:minute from number of seconds
     */
    public static function getHourMinute(int $seconds): string
    {
        $hour = floor($seconds / 3600);
        $minute = (($seconds - ((floor($seconds / 3600)) * 3600)) / 60);
        return sprintf("%02s", $hour) . ":" . sprintf("%02s", $minute);
    }


    /**
     * Get the itemtypes an inventory agent can be linked to
     *
     * @return array<class-string<CommonDBTM>>
     */
    public static function getAgentItemtypes(): array
    {
        global $CFG_GLPI;

        // Custom assets are added to agent_types when their definition is booted, which happens
        // after plugins initialization: they must be computed here to be visible at any time.
        $itemtypes = $CFG_GLPI['agent_types'];
        foreach (self::getAgentAssetDefinitions() as $definition) {
            $itemtype = $definition->getAssetClassName();
            if (!in_array($itemtype, $itemtypes, true)) {
                $itemtypes[] = $itemtype;
            }
        }
        return $itemtypes;
    }


    /**
     * Get the active custom asset definitions
     *
     * Definitions are booted after the plugins are initialized, so they have to be read from
     * the database to be able to declare the plugin tabs and hooks.
     *
     * @return array<AssetDefinition>
     */
    private static function getAssetDefinitions(): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $definitions = AssetDefinitionManager::getInstance()->getDefinitions(true);
        if ($definitions !== []) {
            return $definitions;
        }

        if (self::$preboot_definitions === null) {
            self::$preboot_definitions = [];
            if ($DB->connected && $DB->tableExists(AssetDefinition::getTable())) {
                $iterator = $DB->request([
                    'FROM'  => AssetDefinition::getTable(),
                    'WHERE' => ['is_active' => 1],
                ]);
                foreach ($iterator as $row) {
                    $definition = new AssetDefinition();
                    $definition->getFromResultSet($row);
                    self::$preboot_definitions[] = $definition;
                }
            }
        }
        return self::$preboot_definitions;
    }


    /**
     * Get the custom asset definitions whose assets are inventoried by an agent
     *
     * @return array<AssetDefinition>
     */
    private static function getAgentAssetDefinitions(): array
    {
        $capacity    = new IsInventoriableCapacity();
        $definitions = [];

        foreach (self::getAssetDefinitions() as $definition) {
            if (!$definition->hasCapacityEnabled($capacity)) {
                continue;
            }
            // Network assets are inventoried without being linked to an agent
            $mainasset = $definition
                ->getCapacityConfiguration(IsInventoriableCapacity::class)
                ->getValue('inventory_mainasset');
            if (is_a($mainasset ?? '', NetworkEquipmentMainAsset::class, true)) {
                continue;
            }
            $definitions[] = $definition;
        }
        return $definitions;
    }


    /**
     * Check whether an inventory agent can be linked to this itemtype
     */
    public static function isAgentItemtype(string $itemtype): bool
    {
        return in_array($itemtype, self::getAgentItemtypes(), true);
    }


    /**
     * Get the itemtypes an inventory agent can be linked to, indexed by itemtype
     *
     * @return array<class-string<CommonDBTM>, string>
     */
    public static function getAgentItemtypeNames(): array
    {
        $names = [];
        foreach (self::getAgentItemtypes() as $itemtype) {
            $names[$itemtype] = $itemtype::getTypeName(1);
        }
        return $names;
    }


    /**
     * Get the agent linked to an item
     */
    public static function getAgentForItem(string $itemtype, int $items_id): ?Agent
    {
        $agent = new Agent();
        if (!$agent->getFromDBByCrit(['itemtype' => $itemtype, 'items_id' => $items_id])) {
            return null;
        }
        return $agent;
    }


    /**
     * Execute a function as plugin user
     *
     * @param string|array<string> $function
     * @param array<string|int,mixed> $args
     * @return array the normally returned value from executed callable //@phpstan-ignore missingType.iterableValue
     */
    public function executeAsGlpiinventoryUser(string|array $function, array $args = []): array
    {

        $config = new PluginGlpiinventoryConfig();
        $user = new User();

        // Backup _SESSION environment
        $OLD_SESSION = [];

        foreach (
            ['glpiID', 'glpiname','glpiactiveentities_string',
                'glpiactiveentities', 'glpiparententities', 'glpiactiveprofile',
            ] as $session_key
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
        $_SESSION['glpiactiveentities_string']
         = "'" . implode("', '", $_SESSION['glpiactiveentities']) . "'";
        $_SESSION['glpiparententities'] = [];

        $_SESSION['glpiactiveprofile']['interface'] = 'central';

        $_SESSION["glpiactiveprofile"]["domain"] = READ;
        foreach (self::getAgentItemtypes() as $itemtype) {
            $_SESSION["glpiactiveprofile"][$itemtype::$rightname] = READ;
        }

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
    */
    public static function isAnInventoryDevice(CommonDBTM $item): bool
    {
        switch ($item::class) {
            case Computer::class:
            case NetworkEquipment::class:
            case Printer::class:
                return $item->isDynamic();
        }

        return $item->isDynamic()
         && countElementsInTable(
             RuleMatchedLog::getTable(),
             ['itemtype' => $item::class, 'items_id' => $item->fields['id']]
         );
    }

    public static function authInventory(Request $request): bool
    {
        // GLPI >= 11.0.8: the method is provided natively
        if (method_exists($request, 'authenticateRequest')) { //@phpstan-ignore function.alreadyNarrowedType
            return $request->authenticateRequest();
        }

        // TODO: remove v12 — backport for GLPI < 11.0.8
        // The endpoints instantiate a core \Glpi\Inventory\Request; since a method
        // cannot be added to an already built object, we run authenticateRequest()
        // on a Backport\Request carrying the real request state (transferred by
        // reflection), then copy the mutated state back to the original request.
        $backport = new GlpiPlugin\Glpiinventory\Backport\Request();
        $abstract = AbstractRequest::class;

        // Input state: 'headers' is an object, so it is shared by reference and
        // setHeader('www-authenticate') propagates back to $request automatically.
        // 'mode' + 'response' are required for addError()/addToResponse() to work.
        foreach (['headers', 'local', 'mode', 'response'] as $prop) {
            $rp = new ReflectionProperty($abstract, $prop);
            $rp->setValue($backport, $rp->getValue($request));
        }

        $result = $backport->authenticateRequest();

        // Copy the state mutated by addError() back to the request the endpoint
        // reads from (these are scalars/array, hence not shared by reference).
        foreach (['response', 'http_response_code', 'error'] as $prop) {
            $rp = new ReflectionProperty($abstract, $prop);
            $rp->setValue($request, $rp->getValue($backport));
        }

        return $result;
    }
}
