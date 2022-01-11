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

/**
 * Manage update the task system
 *
 * @global object $DB
 * @param object $migration
 * @param integer $plugin_id
 */
function pluginGlpiinventoryUpdateTasks($migration, $plugin_id)
{
    global $DB;

   /*
    * Table glpi_plugin_glpiinventory_tasks
    */
    $table = [];
    $table['name'] = 'glpi_plugin_glpiinventory_tasks';
    $table['oldname'] = [];

    $table['fields']  = [
      'id' => [
         'type'    => 'autoincrement',
         'value'   => ''
      ],
      'entities_id' => [
         'type'    => 'int unsigned NOT NULL DEFAULT 0',
         'value'   => null
      ],
      'name' => [
         'type'    => 'string',
         'value'   => null
      ],
      'date_creation' => [
         'type'    => 'datetime',
         'value'   => null
      ],
      'comment'    => [
         'type'    => 'text',
         'value'   => null
      ],
      'is_active'  => [
         'type'    => 'bool',
         'value'   => null
      ],
      'datetime_start' => [
         'type'    => 'datetime',
         'value'   => null
      ],
      'datetime_end' => [
         'type'    => 'datetime',
         'value'   => null
      ],
      'plugin_glpiinventory_timeslots_prep_id' => [
         'type'    => 'int unsigned NOT NULL DEFAULT 0',
         'value'   => null
      ],
      'plugin_glpiinventory_timeslots_exec_id' => [
         'type'    => 'int unsigned NOT NULL DEFAULT 0',
         'value'   => null
      ],
    ];

    $table['oldfields'] = [
      "communication",
      "permanent",
      "periodicity_count",
      "periodicity_type",
      "execution_id",
      "is_advancedmode"
    ];

    $table['renamefields'] = [
      'date_scheduled'                      => 'datetime_start',
      'plugin_glpiinventory_timeslots_id' => 'plugin_glpiinventory_timeslots_prep_id'
    ];

    $table['keys']   = [];
    $table['keys'][] = ['field' => 'entities_id', 'name' => '', 'type' => 'INDEX'];
    $table['keys'][] = ['field' => 'is_active', 'name' => '', 'type' => 'INDEX'];
    $table['keys'][] = ['field' => 'plugin_glpiinventory_timeslots_prep_id', 'name' => '', 'type' => 'INDEX'];
    $table['keys'][] = ['field' => 'plugin_glpiinventory_timeslots_exec_id', 'name' => '', 'type' => 'INDEX'];

    $table['oldkeys'] = [];

    migratePluginTables($migration, $table);

   /*
    * Table glpi_plugin_glpiinventory_taskjobs
    */
    $table = [];
    $table['name'] = 'glpi_plugin_glpiinventory_taskjobs';
    $table['oldname'] = [];

    $table['oldfields'] = [
      'retry_nb',
      'retry_time',
      'plugins_id',
      'users_id',
      'status',
      'statuscomment',
      'periodicity_count',
      'periodicity_type',
      'execution_id',
      'ranking'
    ];

    $table['renamefields'] = [
      'definition' => 'targets',
      'action' => 'actors'
    ];

    $table['fields'] = [
      'id' => [
         'type'    => 'autoincrement',
         'value'   => ''
      ],
      'plugin_glpiinventory_tasks_id' => [
         'type'    => 'int unsigned NOT NULL DEFAULT 0',
         'value'   => null
      ],
      'entities_id' => [
         'type'    => 'int unsigned NOT NULL DEFAULT 0',
         'value'   => null
      ],
      'name' => [
         'type'    => 'string',
         'value'   => null
      ],
      'date_creation' => [
         'type'    => 'datetime',
         'value'   => null
      ],
      'method' => [
         'type'    => 'string',
         'value'   => null
      ],
      'targets' => [
         'type'    => 'text',
         'value'   => null
      ],
      'actors' => [
         'type'    => 'text',
         'value'   => null
      ],
      'comment' => [
         'type'    => 'text',
         'value'   => null
      ]
    ];

    $table['keys']   = [];
    $table['keys'][] = [
      'field' => 'plugin_glpiinventory_tasks_id',
      'name' => '', 'type' => 'INDEX'
    ];
    $table['keys'][] = [
      'field' => 'entities_id',
      'name' => '',
      'type' => 'INDEX'
    ];
    $table['keys'][] = [
      'field' => 'method',
      'name' => '',
      'type' => 'INDEX'
    ];

    $table['oldkeys'] = [
      'plugins_id',
      'users_id',
      'rescheduled_taskjob_id'
    ];

    migratePluginTables($migration, $table);

   // * Update method name changed
    $DB->update(
        'glpi_plugin_glpiinventory_taskjobs',
        [
         'method' => 'InventoryComputerESX'
        ],
        [
         'method' => 'ESX'
        ]
    );
    $DB->update(
        'glpi_plugin_glpiinventory_taskjobs',
        [
         'method' => 'networkinventory'
        ],
        [
         'method' => 'snmpinventory'
        ]
    );
    $DB->update(
        'glpi_plugin_glpiinventory_taskjobs',
        [
         'method' => 'networkdiscovery'
        ],
        [
         'method' => 'netdiscovery'
        ]
    );

   /*
    * Table glpi_plugin_glpiinventory_taskjoblogs
    */
    $table = [];
    $table['name'] = 'glpi_plugin_glpiinventory_taskjoblogs';
    $table['oldname'] = [];

    $table['fields']  = [
      'id' => [
         'type' => 'BIGINT unsigned NOT NULL AUTO_INCREMENT',
         'value' => ''
      ],
      'plugin_glpiinventory_taskjobstates_id' => [
         'type' => 'int unsigned NOT NULL DEFAULT 0',
         'value' => null
      ],
      'date' => [
         'type' => 'datetime',
         'value' => null
      ],
      'items_id' => [
         'type' => 'int unsigned NOT NULL DEFAULT 0',
         'value' => null
      ],
      'itemtype' => [
         'type' => 'varchar(100) DEFAULT NULL',
         'value' => null
      ],
      'state' => [
         'type' => 'integer',
         'value' => null
      ],
      'comment' => [
         'type' => 'text',
         'value' => null
      ]
    ];

    $table['oldfields']  = [];

    $table['renamefields'] = [
      'plugin_glpiinventory_taskjobstatus_id' => 'plugin_glpiinventory_taskjobstates_id'
    ];

    $table['keys']   = [
      ['field' => ['plugin_glpiinventory_taskjobstates_id', 'state', 'date'],
      'name' => 'plugin_glpiinventory_taskjobstates_id', 'type' => 'INDEX']
    ];

    $table['oldkeys'] = [
      'plugin_glpiinventory_taskjobstatus_id'
    ];

    migratePluginTables($migration, $table);

   // rename comments for new lang system (gettext in 0.84)
    $texts = [
      'fusinvsnmp::1' => 'devicesqueried',
      'fusinvsnmp::2' => 'devicesfound',
      'fusinvsnmp::3' => 'diconotuptodate',
      'fusinvsnmp::4' => 'addtheitem',
      'fusinvsnmp::5' => 'updatetheitem',
      'fusinvsnmp::6' => 'inventorystarted',
      'fusinvsnmp::7' => 'detail',
      'fusioninventory::1' => 'badtoken',
      'fusioninventory::2' => 'agentcrashed',
      'fusioninventory::3' => 'importdenied'
    ];

    $iterator = $DB->request([
      'FROM'   => $table['name'],
      'WHERE'  => ['comment' => ['LIKE', '%==%']]
    ]);
    if (count($iterator)) {
        $update = $DB->buildUpdate(
            $table['name'],
            [
            'comment'   => new \QueryParam()
            ],
            [
            'id'        => new \QueryParam()
            ]
        );
        $stmt = $DB->prepare($update);
        foreach ($iterator as $data) {
            $comment = $data['comment'];
            foreach ($texts as $key => $value) {
                $comment = str_replace("==" . $key . "==", "==" . $value . "==", $comment);
            }

            $comment = $DB->escape($comment);
            $stmt->bind_param(
                'ss',
                $comment,
                $data['id']
            );
        }
        mysqli_stmt_close($stmt);
    }

   /*
    * Table glpi_plugin_glpiinventory_taskjobstates
    */
    $table = [];
    $table['name'] = 'glpi_plugin_glpiinventory_taskjobstates';
    $table['oldname'] = [
      'glpi_plugin_glpiinventory_taskjobstatus'
    ];

    $table['fields'] = [
      'id' => [
         'type' => 'bigint unsigned not null auto_increment',
         'value' => '0'
      ],
      'plugin_glpiinventory_taskjobs_id' => [
         'type' => 'int unsigned NOT NULL DEFAULT 0',
         'value' => null
      ],
      'items_id' => [
         'type' => 'int unsigned NOT NULL DEFAULT 0',
         'value' => null
      ],
      'itemtype' => [
         'type' => 'varchar(100) DEFAULT NULL',
         'value' => null
      ],
      'plugin_glpiinventory_agents_id' => [
         'type' => 'int unsigned NOT NULL DEFAULT 0',
         'value' => null
      ],
      'specificity' => [
         'type' => 'text',
         'value' => null
      ],
      'uniqid' => [
         'type' => 'string',
         'value' => null
      ],
      'state' => [
         'type' => 'integer',
         'value' => null
      ],
      'date_start' => [
         'type' => 'datetime',
         'value' => null
      ],
      'nb_retry' => [
         'type' => 'integer',
         'value' => 0
      ],
      'max_retry' => [
         'type' => 'integer',
         'value' => 1
      ]
    ];

    $table['renamefields'] = [];
    $table['oldfields'] = [
      'execution_id'
    ];

    $table['keys'] = [
      [
         'field' => [
            'plugin_glpiinventory_taskjobs_id'
         ],
         'name' => '', 'type' => 'INDEX'
      ],
      [
         'field' => [
            'plugin_glpiinventory_agents_id',
            'state'
         ],
         'name' => '', 'type' => 'INDEX'
      ],
      [
         'field' => [
            'plugin_glpiinventory_agents_id',
            'plugin_glpiinventory_taskjobs_id',
            'items_id',
            'itemtype',
            'id',
            'state'
         ],
         'name' => 'plugin_glpiinventory_agents_items_states',
         'type' => 'INDEX'
      ]

    ];
    $table['oldkeys'] = [];
    migratePluginTables($migration, $table);
}
