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


/**
 * The main function to update the plugin
 *
 * @global object $DB
 * @param string $current_version
 * @param string $migrationname
 */
function pluginGlpiinventoryUpdateNative($current_version, $migrationname = 'Migration')
{
    global $DB;

    $DB->disableTableCaching();

    ini_set("max_execution_time", "0");
    ini_set("memory_limit", "-1");

   /** @var Migration */
    $migration = new $migrationname($current_version);

    $migration->displayMessage("Migration Classname : " . $migrationname);
    $migration->displayMessage("Use core capabilities");

    //mappings
    $agents_mapping = [];
    $unmanageds_mapping = [];

    $migration->displayMessage("Use core agent");
    if ($DB->tableExists('glpi_plugin_glpiinventory_agents')) {
        $agents_tables = [
            'glpi_plugin_glpiinventory_ignoredimportdevices',
            'glpi_plugin_glpiinventory_rulematchedlogs',
            'glpi_plugin_glpiinventory_statediscoveries',
            'glpi_plugin_glpiinventory_taskjobstates',
            'glpi_plugin_glpiinventory_unmanageds',
        ];
        $iterator = $DB->request([
          'FROM' => 'glpi_plugin_glpiinventory_agents'
        ]);
        $agent = new Agent();

        $agenttype = new AgentType();
        if (!$agenttype->getFromDBByCrit(['name' => 'GLPI Inventory Plugin'])) {
            $agenttype->add(['name' => 'GLPI Inventory Plugin']);
        }

        foreach ($iterator as $data_agent) {
            $old_id = $data_agent['id'];

           //mappings
            $data_agent['deviceid'] = $data_agent['device_id'];
            $data_agent['itemtype'] = 'Computer';
            $data_agent['items_id'] = $data_agent['computers_id'];
            $data_agent['port'] = $data_agent['agent_port'];
            $data_agent['agenttypes_id'] = $agenttype->fields['id'];

            unset(
                $data_agent['id'],
                $data_agent['device_id'],
                $data_agent['computers_id'],
                $data_agent['senddico'],
                $data_agent['agent_port']
            );

            $new_id = $agent->add(Toolbox::addslashes_deep($data_agent));
            $agents_mapping[$old_id] = $new_id;
        }

        //update to new ids
        if (count($agents_mapping)) {
            foreach ($agents_tables as $agent_table) {
                if (!$DB->tableExists($agent_table)) {
                    continue;
                }
                foreach ($agents_mapping as $old_agent_id => $new_agent_id) {
                    if ($old_agent_id == $new_agent_id) {
                        continue;
                    }
                    $DB->queryOrDie(
                        $DB->buildUpdate(
                            $agent_table,
                            [
                                'agents_id' => $new_agent_id
                            ],
                            [
                                'agents_id' => $old_agent_id
                            ]
                        )
                    );
                }
            }
        }

        $migration->dropTable('glpi_plugin_glpiinventory_agents');
    }

    if ($DB->tableExists('glpi_plugin_glpiinventory_agentmodules')) {
        $agentmodules_iterator = $DB->request(['FROM' => 'glpi_plugin_glpiinventory_agentmodules']);
        foreach ($agentmodules_iterator as $agentmodule) {
            $old_agent_ids = importArrayFromDB($agentmodule['exceptions']);
            $new_agent_ids = [];
            foreach ($old_agent_ids as $old_agent_id) {
                if (!array_key_exists($old_agent_id, $agents_mapping)) {
                    continue; // Agent does not exist anymore, data is probably stale
                }
                $new_agent_ids[] = $agents_mapping[$old_agent_id];
            }
            $DB->queryOrDie(
                $DB->buildUpdate(
                    'glpi_plugin_glpiinventory_agentmodules',
                    [
                        'exceptions' => exportArrayToDB($new_agent_ids)
                    ],
                    [
                        'id' => $agentmodule['id']
                    ]
                )
            );
        }
    }

    $migration->displayMessage("Use core SNMP credentials");
    if ($DB->tableExists('glpi_plugin_glpiinventory_configsecurities')) {
        $cs_mapping = [];
        $cs_tables = [
          'glpi_plugin_glpiinventory_unmanageds',
          'glpi_plugin_glpiinventory_ipranges_configsecurities',
          'glpi_plugin_glpiinventory_networkequipments',
          'glpi_plugin_glpiinventory_printers'
        ];
        $iterator = $DB->request([
          'FROM' => 'glpi_plugin_glpiinventory_configsecurities'
        ]);
        $snmpcred = new SNMPCredential();
        foreach ($iterator as $cs) {
            $old_id = $cs['id'];
            unset($cs['id']);
           //crypt passwords
            if (!empty($cs['auth_passphrase'])) {
                $cs['auth_passphrase'] = (new GLPIKey())->encrypt($cs['auth_passphrase']);
            }
            if (!empty($cs['priv_passphrase'])) {
                $cs['priv_passphrase'] = (new GLPIKey())->encrypt($cs['priv_passphrase']);
            }
            $search_crit = [
                'name'              => $cs['name'],
                'snmpversion'       => $cs['snmpversion'],
                'community'         => $cs['community'],
                'username'          => !empty($cs['username']) ? $cs['username'] : null,
                'authentication'    => !empty($cs['authentication']) && $cs['authentication'] != '0' ? $cs['authentication'] : null,
                'auth_passphrase'   => !empty($cs['auth_passphrase']) ? $cs['auth_passphrase'] : null,
                'encryption'        => !empty($cs['encryption']) && $cs['encryption'] != '0' ? $cs['encryption'] : null,
                'priv_passphrase'   => !empty($cs['priv_passphrase']) ? $cs['priv_passphrase'] : null,
                'is_deleted'        => $cs['is_deleted'],
            ];
            if ($snmpcred->getFromDBByCrit($search_crit)) {
                $new_id = $snmpcred->fields['id'];
            } else {
                $new_id = $snmpcred->add($cs);
            }
            if ($new_id != $old_id) {
                $cs_mapping[$old_id] = $new_id;
            }
        }

        //update to new ids
        if (count($cs_mapping)) {
            foreach ($cs_tables as $cs_table) {
                if (!$DB->tableExists($cs_table)) {
                    continue;
                }
                foreach ($cs_mapping as $old_cs_id => $new_cs_id) {
                    $DB->queryOrDie(
                        $DB->buildUpdate(
                            $cs_table,
                            [
                                'plugin_glpiinventory_configsecurities_id' => $new_cs_id
                            ],
                            [
                                'plugin_glpiinventory_configsecurities_id' => $old_cs_id
                            ]
                        )
                    );
                }
            }
        }

        $migration->dropTable('glpi_plugin_glpiinventory_configsecurities');
    }

    if (
        $DB->tableExists('glpi_plugin_glpiinventory_ipranges_configsecurities')
        && !$DB->tableExists('glpi_plugin_glpiinventory_ipranges_snmpcredentials')
    ) {
        // snmp credentials must be migrated before that one
        $migration->renameTable(
            'glpi_plugin_glpiinventory_ipranges_configsecurities',
            'glpi_plugin_glpiinventory_ipranges_snmpcredentials'
        );
        $migration->changeField(
            'glpi_plugin_glpiinventory_ipranges_snmpcredentials',
            'plugin_glpiinventory_configsecurities_id',
            'snmpcredentials_id',
            'int unsigned NOT NULL DEFAULT 0'
        );
        $migration->dropKey('glpi_plugin_glpiinventory_ipranges_snmpcredentials', 'unicity');
        $migration->addKey('glpi_plugin_glpiinventory_ipranges_snmpcredentials', ['plugin_glpiinventory_ipranges_id', 'snmpcredentials_id'], 'unicity');
    } elseif ($DB->tableExists('glpi_plugin_glpiinventory_ipranges_configsecurities')) {
        $migration->dropTable('glpi_plugin_glpiinventory_ipranges_configsecurities');
    }

    $migration->displayMessage("Use core network ports");
    if ($DB->tableExists('glpi_plugin_glpiinventory_networkports')) {
        $DB->queryOrDie(
            "UPDATE `glpi_networkports` AS `ports`
            INNER JOIN (
              SELECT
                `networkports_id`,
                `ifmtu`,
                `ifspeed`,
                `ifinternalstatus`,
                `ifconnectionstatus`,
                `iflastchange`,
                `ifinoctets`,
                `ifinerrors`,
                `ifoutoctets`,
                `ifouterrors`,
                `ifstatus`,
                `mac`,
                `ifdescr`,
                `ifalias`,
                `portduplex`,
                `trunk`,
                `lastup`
              FROM `glpi_plugin_glpiinventory_networkports`
          ) AS `plugin_ports` ON `plugin_ports`.`networkports_id` = `ports`.`id`
          SET
             `ports`.`ifmtu` = `plugin_ports`.`ifmtu`,
             `ports`.`ifspeed` = `plugin_ports`.`ifspeed`,
             `ports`.`ifinternalstatus` = `plugin_ports`.`ifinternalstatus`,
             `ports`.`ifconnectionstatus` = `plugin_ports`.`ifconnectionstatus`,
             `ports`.`iflastchange` = `plugin_ports`.`iflastchange`,
             `ports`.`ifinbytes` = `plugin_ports`.`ifinoctets`,
             `ports`.`ifinerrors` = `plugin_ports`.`ifinerrors`,
             `ports`.`ifoutbytes` = `plugin_ports`.`ifoutoctets`,
             `ports`.`ifouterrors` = `plugin_ports`.`ifouterrors`,
             `ports`.`ifstatus` = `plugin_ports`.`ifstatus`,
             `ports`.`mac` = `plugin_ports`.`mac`,
             `ports`.`ifdescr` = `plugin_ports`.`ifdescr`,
             `ports`.`ifalias` = `plugin_ports`.`ifalias`,
             `ports`.`portduplex` = `plugin_ports`.`portduplex`,
             `ports`.`trunk` = `plugin_ports`.`trunk`,
             `ports`.`lastup` = `plugin_ports`.`lastup`
          ;"
        );
        $migration->dropTable('glpi_plugin_glpiinventory_networkports');
    }

    $migration->displayMessage("Use core computers");
    if ($DB->tableExists('glpi_plugin_glpiinventory_inventorycomputercomputers')) {
        $DB->queryOrDie(
            "UPDATE `glpi_computers` AS `computers`
            INNER JOIN (
              SELECT
                `computers_id`,
                `last_inventory_update`
              FROM `glpi_plugin_glpiinventory_inventorycomputercomputers`
          ) AS `plugin_computers` ON `plugin_computers`.`computers_id` = `computers`.`id`
          SET
          `computers`.`last_inventory_update` = `plugin_computers`.`last_inventory_update`
          WHERE `computers`.`last_inventory_update` IS NULL;"
        );

        $DB->queryOrDie(
            "UPDATE `glpi_computers` AS `computers`
            INNER JOIN (
              SELECT
                `computers_id`,
                `last_boot`
              FROM `glpi_plugin_glpiinventory_inventorycomputercomputers`
          ) AS `plugin_computers` ON `plugin_computers`.`computers_id` = `computers`.`id`
          SET
          `computers`.`last_boot` = `plugin_computers`.`last_boot`
          WHERE `computers`.`last_boot` IS NULL
          ;"
        );

        $DB->queryOrDie(
            "UPDATE `glpi_agents` AS `agents`
            INNER JOIN (
              SELECT
                `computers_id`,
                `remote_addr`
              FROM `glpi_plugin_glpiinventory_inventorycomputercomputers`
          ) AS `plugin_computers` ON `plugin_computers`.`computers_id` = `agents`.`items_id` AND `agents`.`itemtype` = 'Computer'
          SET
          `agents`.`remote_addr` = `plugin_computers`.`remote_addr`
          WHERE `agents`.`remote_addr` IS NULL OR `agents`.`remote_addr` = ''
          ;"
        );
        $migration->dropTable('glpi_plugin_glpiinventory_inventorycomputercomputers');
    }

    $migration->displayMessage("Use core network equipments");
    if ($DB->tableExists('glpi_plugin_glpiinventory_networkequipments')) {
        // agents and snmp credentials must be migrated before that one
        $DB->queryOrDie(
            "UPDATE `glpi_networkequipments` AS `neteq`
            INNER JOIN (
              SELECT
                `networkequipments_id`,
                `plugin_glpiinventory_configsecurities_id`,
                `sysdescr`,
                `cpu`,
                `uptime`,
                `last_inventory_update`
              FROM `glpi_plugin_glpiinventory_networkequipments`
          ) AS `plugin_neteq` ON `plugin_neteq`.`networkequipments_id` = `neteq`.`id`
          SET
              `neteq`.`snmpcredentials_id` = `plugin_neteq`.`plugin_glpiinventory_configsecurities_id`,
              `neteq`.`sysdescr` = `plugin_neteq`.`sysdescr`,
              `neteq`.`cpu` = `plugin_neteq`.`cpu`,
              `neteq`.`uptime` = `plugin_neteq`.`uptime`,
              `neteq`.`last_inventory_update` = `plugin_neteq`.`last_inventory_update`
          ;"
        );
        $migration->dropTable('glpi_plugin_glpiinventory_networkequipments');
    }

    $migration->displayMessage("Use core printers");
    if ($DB->tableExists('glpi_plugin_glpiinventory_printers')) {
        // agents and snmp credentials must be migrated before that one
        $DB->queryOrDie(
            "UPDATE `glpi_printers` AS `printers`
            INNER JOIN (
              SELECT
                `printers_id`,
                `plugin_glpiinventory_configsecurities_id`,
                `sysdescr`,
                `last_inventory_update`
              FROM `glpi_plugin_glpiinventory_printers`
          ) AS `plugin_printers` ON `plugin_printers`.`printers_id` = `printers`.`id`
          SET
              `printers`.`snmpcredentials_id` = `plugin_printers`.`plugin_glpiinventory_configsecurities_id`,
              `printers`.`sysdescr` = `plugin_printers`.`sysdescr`,
              `printers`.`last_inventory_update` = `plugin_printers`.`last_inventory_update`
          ;"
        );
        $migration->dropTable('glpi_plugin_glpiinventory_printers');
    }
    $migration->dropTable('glpi_plugin_glpiinventory_printercartridges');

    $migration->displayMessage("Use core printer logs");
    if ($DB->tableExists('glpi_plugin_glpiinventory_printerlogs')) {
        $DB->queryOrDie(
            "INSERT INTO `glpi_printerlogs` (
                `printers_id`,
                `date`,
                `total_pages`,
                `bw_pages`,
                `color_pages`,
                `rv_pages`,
                `scanned`,
                `prints`,
                `bw_prints`,
                `color_prints`,
                `copies`,
                `bw_copies`,
                `color_copies`,
                `faxed`,
                `date_creation`,
                `date_mod`
              )
              SELECT
                `printers_id`,
                DATE(`date`) as `log_date`,
                `pages_total`,
                `pages_n_b`,
                `pages_color`,
                `pages_recto_verso`,
                `scanned`,
                `pages_total_print`,
                `pages_n_b_print`,
                `pages_color_print`,
                `pages_total_copy`,
                `pages_n_b_copy`,
                `pages_color_copy`,
                `pages_total_fax`,
                `date`,
                `date`
              FROM `glpi_plugin_glpiinventory_printerlogs`
              WHERE `id` IN (SELECT MAX(`id`) as `id` from glpi_plugin_glpiinventory_printerlogs GROUP BY `printers_id`, DATE(`date`))
            ;"
        );
        $migration->dropTable('glpi_plugin_glpiinventory_printerlogs');
    }

    $migration->displayMessage("Use core networkports logs");
    if ($DB->tableExists('glpi_plugin_glpiinventory_networkportconnectionlogs')) {
        $DB->queryOrDie(
            "INSERT INTO `glpi_networkportconnectionlogs` (
                `date`,
                `connected`,
                `networkports_id_source`,
                `networkports_id_destination`
              )
              SELECT
                `date_mod`,
                `creation`,
                `networkports_id_source`,
                `networkports_id_destination`
              FROM `glpi_plugin_glpiinventory_networkportconnectionlogs`;"
        );
        $migration->dropTable('glpi_plugin_glpiinventory_networkportconnectionlogs');
    }

    $migration->displayMessage("Use core network ports types");
    if ($DB->tableExists('glpi_plugin_glpiinventory_networkporttypes')) {
        $DB->queryOrDie(
            "UPDATE `glpi_networkporttypes` AS `types`
            INNER JOIN (
              SELECT
                `name`,
                `number`,
                `import`
              FROM `glpi_plugin_glpiinventory_networkporttypes`
          ) AS `plugin_types` ON `plugin_types`.`name` = `types`.`name` AND `plugin_types`.`number` = `types`.`value_decimal`
          SET
              `types`.`is_importable` = `plugin_types`.`import`
          ;"
        );
        $migration->dropTable('glpi_plugin_glpiinventory_networkporttypes');
    }

    $migration->displayMessage("Use core unmanaged equipments");
    if ($DB->tableExists('glpi_plugin_glpiinventory_unmanageds')) {
        // agents and snmp credentials must be migrated before that one
        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_glpiinventory_unmanageds'
        ]);
        $unmanaged = new Unmanaged();

        foreach ($iterator as $data_unmanaged) {
            $old_id = $data_unmanaged['id'];

            //mappings
            $data_unmanaged['domains_id'] = $data_unmanaged['domain'];
            $data_unmanaged['itemtype'] = $data_unmanaged['item_type'];
            $data_unmanaged['snmpcredentials_id'] = $data_unmanaged['plugin_glpiinventory_configsecurities_id'];

            unset(
                $data_unmanaged['id'],
                $data_unmanaged['domain'],
                $data_unmanaged['item_type'],
                $data_unmanaged['plugin_glpiinventory_configsecurities_id']
            );

            $new_id = $unmanaged->add(Toolbox::addslashes_deep($data_unmanaged));
            $unmanageds_mapping[$old_id] = $new_id;
        }
        $migration->dropTable('glpi_plugin_glpiinventory_unmanageds');
    }

    $migration->displayMessage("Drop ignored equipments");
    if ($DB->tableExists('glpi_plugin_glpiinventory_ignoredimportdevices')) {
        $migration->dropTable('glpi_plugin_glpiinventory_ignoredimportdevices');
    }

    $migration->displayMessage("Use core rules");
    // get max rankings
    $rank_ria = 0;
    $rank_rie = 0;
    $rank_ril = 0;

    $iterator = $DB->request([
        'SELECT' => ['MAX' => 'ranking AS max_ranking'],
        'FROM'   => 'glpi_rules',
        'WHERE'  => ['sub_type' => 'RuleImportAsset']
    ]);
    if (count($iterator)) {
        $rank_ria = $iterator->current()['max_ranking'];
    }
    $iterator = $DB->request([
        'SELECT' => ['MAX' => 'ranking AS max_ranking'],
        'FROM'   => 'glpi_rules',
        'WHERE'  => ['sub_type' => 'RuleImportEntity']
    ]);
    if (count($iterator)) {
        $rank_rie = $iterator->current()['max_ranking'];
    }
    $iterator = $DB->request([
        'SELECT' => ['MAX' => 'ranking AS max_ranking'],
        'FROM'   => 'glpi_rules',
        'WHERE'  => ['sub_type' => 'RuleLocation']
    ]);
    if (count($iterator)) {
        $rank_ril = $iterator->current()['max_ranking'];
    }

    $DB->update(
        'glpi_rules',
        [
         'sub_type'  => 'RuleImportAsset',
         'name'      => new QueryExpression('CONCAT(' . $DB->quoteValue('[MIGRATED_FROM_FUSION]') . ', ' . $DB->quoteName('name') . ')'),
         'ranking'   => new QueryExpression($DB->quoteName('ranking') . " + " . ($rank_ria ?? 0))
        ],
        [
            'OR' => [
                ['sub_type'  => 'PluginFusioninventoryInventoryRuleImport'],
                ['sub_type'  => 'PluginGlpiinventoryInventoryRuleImport']
            ]
        ]
    );
    $DB->update(
        'glpi_rules',
        [
         'sub_type'  => 'RuleImportEntity',
         'name'      => new QueryExpression('CONCAT(' . $DB->quoteValue('[MIGRATED_FROM_FUSION]') . ', ' . $DB->quoteName('name') . ')'),
         'ranking'   => new QueryExpression($DB->quoteName('ranking') . " + " . ($rank_rie ?? 0))
        ],
        [
            'OR' => [
                ['sub_type'  => 'PluginFusinvinventoryRuleEntity'],
                ['sub_type'  => 'PluginFusioninventoryInventoryRuleEntity']
            ]
        ]
    );
    $DB->update(
        'glpi_rules',
        [
            'sub_type'  => 'RuleLocation',
            'name'      => new QueryExpression('CONCAT(' . $DB->quoteValue('[MIGRATED_FROM_FUSION]') . ', ' . $DB->quoteName('name') . ')'),
            'ranking'   => new QueryExpression($DB->quoteName('ranking') . " + " . ($rank_ril ?? 0))
        ],
        [
            'sub_type'  => 'PluginFusioninventoryInventoryRuleLocation'
        ]
    );

    //update _fusion action to _inventory
    $DB->update(
        'glpi_ruleactions',
        [
            'field'  => '_inventory',
        ],
        [
            'field'  => '_fusion',
        ]
    );

    if ($DB->tableExists('glpi_plugin_glpiinventory_rulematchedlogs')) {
        // agents must be migrated before that one
        $DB->queryOrDie(
            "INSERT INTO `glpi_rulematchedlogs` (
               `date`,
               `items_id`,
               `itemtype`,
               `rules_id`,
               `agents_id`,
               `method`
             )
             SELECT
               `date`,
               `items_id`,
               `itemtype`,
               `rules_id`,
               `agents_id`,
               `method`
             FROM `glpi_plugin_glpiinventory_rulematchedlogs`;"
        );
        $migration->dropTable('glpi_plugin_glpiinventory_rulematchedlogs');
    }

    $migration->displayMessage("Use core remote management");
    if ($DB->tableExists('glpi_plugin_glpiinventory_computerremotemanagements')) {
        // agents must be migrated before that one
        $DB->queryOrDie(
            "INSERT INTO `glpi_items_remotemanagements` (
                `itemtype`,
                `items_id`,
                `remoteid`,
                `type`,
                `is_dynamic`
              )
              SELECT
                'Computer',
                `computers_id`,
                `number`,
                `type`,
                1
              FROM `glpi_plugin_glpiinventory_computerremotemanagements`;"
        );
        $migration->dropTable('glpi_plugin_glpiinventory_computerremotemanagements');
    }

    $migration->dropTable('glpi_plugin_glpiinventory_computerlicenseinfos');

    $migration->displayMessage('Use core fields locks');
    if ($DB->tableExists('glpi_plugin_glpiinventory_locks')) {
        $lock = new Lockedfield();
        $lock_table = $lock->getTable();
        $iterator = $DB->request([
         'FROM' => 'glpi_plugin_glpiinventory_locks'
        ]);
        foreach ($iterator as $row) {
            $fields = importArrayFromDB($row['tablefields']);
            foreach ($fields as $field) {
                $input = Toolbox::addslashes_deep(
                    [
                        'itemtype' => getItemTypeForTable($row['tablename']),
                        'items_id' => $row['items_id'],
                        'field'    => $field,
                        'is_global' => ($row['items_id'] == 0) ? 1 : 0
                    ]
                );

                if (countElementsInTable($lock_table, $input) > 0) {
                    continue; // Field is already locked
                }

                $lock->add($input);
            }
        }
        $migration->dropTable('glpi_plugin_glpiinventory_locks');
    }

    $migration->displayMessage('Drop dblock tables');
    $migration->dropTable('glpi_plugin_glpiinventory_dblockinventories');
    $migration->dropTable('glpi_plugin_glpiinventory_dblockinventorynames');
    $migration->dropTable('glpi_plugin_glpiinventory_dblocksoftwares');
    $migration->dropTable('glpi_plugin_glpiinventory_dblocksoftwareversions');

    $migration->displayMessage("Use core entities");
    if ($DB->tableExists('glpi_plugin_glpiinventory_entities')) {
        $DB->queryOrDie(
            "UPDATE `glpi_entities` AS `entities`
            INNER JOIN (
              SELECT
                `entities_id`,
                `transfers_id_auto`,
                `agent_base_url`
              FROM `glpi_plugin_glpiinventory_entities`
          ) AS `plugin_entities` ON `plugin_entities`.`entities_id` = `entities`.`id`
          SET
              `entities`.`transfers_id` = `plugin_entities`.`transfers_id_auto`,
              `entities`.`agent_base_url` = `plugin_entities`.`agent_base_url`
          ;"
        );
        $migration->dropTable('glpi_plugin_glpiinventory_entities');
    }

    $migration->displayMessage("Drop network ports and printers logs tables");
    $migration->dropTable('glpi_plugin_glpiinventory_configlogfields');
    $migration->dropTable('glpi_plugin_glpiinventory_networkportlogs');

    $migration->displayMessage("Drop inventory mapping table");
    $migration->dropTable('glpi_plugin_glpiinventory_mappings');

    $migration->displayMessage("Drop blacklists tables");
    $migration->dropTable('glpi_plugin_glpiinventory_inventorycomputerblacklists');
    $migration->dropTable('glpi_plugin_glpiinventory_inventorycomputercriterias');

    // Remove deleted crontasks
    $crontask = new CronTask();
    $crontask->deleteByCriteria(['itemtype' => 'PluginGlpiinventoryNetworkPortLog', 'name' => 'cleannetworkportlogs']);
    $crontask->deleteByCriteria(['itemtype' => 'PluginGlpiinventoryAgent', 'name' => 'cleanoldagents']);

    // Remove old display preferences
    $displaypref = new DisplayPreference();
    $displaypref->deleteByCriteria(['itemtype' => 'PluginGlpiinventoryAgent'], true, false);
    $displaypref->deleteByCriteria(['itemtype' => 'PluginGlpiinventoryUnmanaged'], true, false);

    //Fix old types
    $types = [
        'PluginGlpiinventoryAgent' => 'Agent',
        'PluginGlpiinventoryUnmanaged' => 'Unmanaged'
    ];

    $mappings = [
        'Agent' => $agents_mapping,
        'Unmanaged' => $unmanageds_mapping,
    ];

    $types_iterator = $DB->request(
        [
            'SELECT' => [
                'table_name AS TABLE_NAME',
                'column_name AS COLUMN_NAME',
            ],
            'FROM'   => 'information_schema.columns',
            'WHERE'  => [
                'table_schema' => $DB->dbdefault,
                'table_name'   => ['LIKE', 'glpi\_%'],
                'OR' => [
                    ['column_name'  => 'itemtype'],
                    ['column_name'  => ['LIKE', 'itemtype_%']],
                ],
                // Handle edge case where an id column (i.e. a kind of foreign key) is prefixed by `itemtype_`.
                ['NOT' => ['column_name'  => ['LIKE', '%_id']]],
            ],
            'ORDER'  => 'TABLE_NAME',
        ]
    );

    foreach ($types_iterator as $type) {
        $table_name   = $type['TABLE_NAME'];
        $itemtype_col = $type['COLUMN_NAME'];
        $items_id_col = str_replace('itemtype', 'items_id', $itemtype_col);
        foreach ($types as $orig_type => $new_type) {
            if ($DB->fieldExists($table_name, $items_id_col)) {
                // items_id field exists, update itemtype and items_id
                // and remove data related to items that does not exists anymore
                $mapping = $mappings[$new_type];
                foreach ($mapping as $orig_id => $new_id) {
                    $migration->addPostQuery(
                        $DB->buildUpdate(
                            $table_name,
                            [
                                $itemtype_col => $new_type,
                                $items_id_col => $new_id
                            ],
                            [
                                $itemtype_col => $orig_type,
                                $items_id_col => $orig_id
                            ]
                        )
                    );
                }
                $migration->addPostQuery(
                    $DB->buildDelete(
                        $type['TABLE_NAME'],
                        [
                            $itemtype_col => $orig_type,
                        ],
                    )
                );
            } else {
                // items_id field does not exists, just rename the itemtype
                $migration->addPostQuery(
                    $DB->buildUpdate(
                        $table_name,
                        [
                            $itemtype_col => $new_type,
                        ],
                        [
                            $itemtype_col => $orig_type,
                        ]
                    )
                );
            }
        }

        $migration->addPostQuery(
            $DB->buildDelete(
                $table_name,
                [
                    $itemtype_col => 'PluginGlpiinventoryIgnoredimportdevice'
                ]
            )
        );
    }

    if ($DB->tableExists('glpi_plugin_glpiinventory_taskjobs')) {
        $taskjobs_iterator = $DB->request(['FROM' => 'glpi_plugin_glpiinventory_taskjobs']);
        foreach ($taskjobs_iterator as $taskjob) {
            $updated_values = [
                'actors'  => [],
                'targets' => [],
            ];
            foreach (['actors', 'targets'] as $fieldname) {
                $existing_values = importArrayFromDB($taskjob[$fieldname]);
                foreach ($existing_values as $item_specs) {
                    $itemtype = key($item_specs);
                    $items_id = current($item_specs);
                    if ($itemtype === 'PluginGlpiinventoryAgent' || $itemtype === 'PluginFusioninventoryAgent') {
                        $itemtype = 'Agent';
                        if (array_key_exists($items_id, $agents_mapping)) {
                            $items_id = $agents_mapping[$items_id];
                        }
                    } else {
                        $itemtype = str_replace('PluginFusioninventory', 'PluginGlpiinventory', $itemtype);
                    }
                    $updated_values[$fieldname][] = [$itemtype => $items_id];
                }
            }

            $DB->queryOrDie(
                $DB->buildUpdate(
                    'glpi_plugin_glpiinventory_taskjobs',
                    [
                        'actors'  => exportArrayToDB($updated_values['actors']),
                        'targets' => exportArrayToDB($updated_values['targets'])
                    ],
                    [
                        'id' => $taskjob['id']
                    ]
                )
            );
        }

        //new field
        $migration->addField('glpi_plugin_glpiinventory_taskjobs', 'restrict_to_task_entity', 'bool', ['value'   => '1']);
    }

    // Use core configuration entries
    $plugin_configs = $DB->request('glpi_plugin_glpiinventory_configs');
    $mapping = [
        'agents_action'   => 'stale_agents_action',
        'agents_old_days' => 'stale_agents_delay',
        'agents_status'   => 'stale_agents_status',
    ];
    foreach ($plugin_configs as $plugin_config) {
        $plugin_name = $plugin_config['type'];
        if (array_key_exists($plugin_name, $mapping)) {
            $glpi_name = $mapping[$plugin_name];
            if (Config::getConfigurationValue('inventory', $glpi_name) == null) {
                // Only set configuration if it has never been set in GLPI
                Config::setConfigurationValues(
                    'inventory',
                    [
                        $glpi_name => $plugin_config['value'],
                    ]
                );
            }
            // Delete configuration, value, it is not needed anymore
            $migration->addPostQuery(
                $DB->buildDelete('glpi_plugin_glpiinventory_configs', ['id' => $plugin_config['id']])
            );
        }
    }

    // /!\ Keep it at the end
    $migration->executeMigration();
}
