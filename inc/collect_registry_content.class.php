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

use Glpi\Application\View\TemplateRenderer;

use function Safe\preg_match;

/**
 * Manage the registry keys found by the collect module of agent.
 */
class PluginGlpiinventoryCollect_Registry_Content extends PluginGlpiinventoryCollectContentCommon
{
    public string $collect_itemtype = PluginGlpiinventoryCollect_Registry::class;
    public string $collect_table    = 'glpi_plugin_glpiinventory_collects_registries';

    public string $collect_type = 'registry';

    /**
     * Get the tab name used for item
     *
     * @param CommonGLPI $item the item object
     * @param int $withtemplate 1 if is a template form
     * @return string name of the tab
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        /** @var CommonDBTM $item */
        if ($item->fields['id'] > 0) {
            if (get_class($item) == PluginGlpiinventoryCollect::class) {
                if ($item->fields['type'] == 'registry') {
                    $a_colregs = getAllDataFromTable(
                        'glpi_plugin_glpiinventory_collects_registries',
                        ['plugin_glpiinventory_collects_id' => $item->fields['id']]
                    );
                    if (count($a_colregs) == 0) {
                        return '';
                    }
                    $in = array_keys($a_colregs);
                    if (
                        countElementsInTable(
                            'glpi_plugin_glpiinventory_collects_registries_contents',
                            ['plugin_glpiinventory_collects_registries_id' => $in]
                        ) > 0
                    ) {
                        return __('Windows registry content', 'glpiinventory');
                    }
                }
            }
        }
        return '';
    }


    /**
     * Update computer registry values (add and update) related to this
     * collect registry id
     *
     * @param int $computers_id id of the computer
     * @param array<string,mixed> $registry_data registry info sent by agent
     * @param int $collects_registries_id id of collect_registry
     */
    public function updateComputer($computers_id, $registry_data, $collects_registries_id): void
    {
        /** @var DBmysql $DB */
        global $DB;

        unset($registry_data['_sid']);

        $collect_registry = new PluginGlpiinventoryCollect_Registry();
        $mode = PluginGlpiinventoryCollect_Registry::MODE_DEFAULT;
        if ($collect_registry->getFromDB($collects_registries_id)) {
            $mode = (int) $collect_registry->fields['mode'];
        }

        // New agents can use the new value submission format (one "_path"/"_value" entry per
        // answer) even in the default mode; it is equivalent to the "All values" mode.
        if ($mode === PluginGlpiinventoryCollect_Registry::MODE_DEFAULT && array_key_exists('_path', $registry_data)) {
            $mode = PluginGlpiinventoryCollect_Registry::MODE_DEPTH;
        }

        switch ($mode) {
            case PluginGlpiinventoryCollect_Registry::MODE_PATH_EXISTS:
                // Use the "_exists" flag if the agent supports it, otherwise fall back on
                // the presence of returned data (agent that does not know the flag).
                $exists = isset($registry_data['_exists'])
                    ? (int) (bool) $registry_data['_exists']
                    : (int) self::hasReturnedData($registry_data);
                $this->storeSingleResult($computers_id, $collects_registries_id, '', (string) $exists);
                return;

            case PluginGlpiinventoryCollect_Registry::MODE_KEY_DEFINED:
                // Use the "_defined" flag if the agent supports it, otherwise consider the key
                // defined when the agent returned a value for it (agent without flag support).
                $defined = isset($registry_data['_defined'])
                    ? (int) (bool) $registry_data['_defined']
                    : (int) self::hasReturnedData($registry_data);
                $key = (string) ($collect_registry->fields['key'] ?? '');
                $this->storeSingleResult($computers_id, $collects_registries_id, $key, (string) $defined);
                return;

            case PluginGlpiinventoryCollect_Registry::MODE_DEPTH:
                // Upsert by path so a repeated entry (the agent may revisit the same value while
                // recursing, or re-send it) never creates duplicate rows.
                if (array_key_exists('_path', $registry_data)) {
                    $path  = (string) $registry_data['_path'];
                    $value = (string) ($registry_data['_value'] ?? '');
                    if (preg_match("/^0x[0-9a-fA-F]{1,}$/", $value)) {
                        $value = hexdec($value);
                    }
                    $existing = $this->find([
                        'computers_id' => $computers_id,
                        'plugin_glpiinventory_collects_registries_id' => $collects_registries_id,
                        'key'          => $path,
                    ], [], 1);
                    if (count($existing)) {
                        $this->update([
                            'id'    => current($existing)['id'],
                            'value' => $value,
                        ]);
                    } else {
                        $this->add([
                            'computers_id' => $computers_id,
                            'plugin_glpiinventory_collects_registries_id' => $collects_registries_id,
                            'key'          => $path,
                            'value'        => $value,
                        ]);
                    }
                    return;
                }
                return;
        }

        $db_registries = [];

        $iterator = $DB->request([
            'SELECT' => ['id', 'key', 'value'],
            'FROM'   => 'glpi_plugin_glpiinventory_collects_registries_contents',
            'WHERE'  => [
                'computers_id' => $computers_id,
                'plugin_glpiinventory_collects_registries_id' => $collects_registries_id,
            ],
        ]);

        foreach ($iterator as $data) {
            $idtmp = $data['id'];
            unset($data['id']);
            $db_registries[$idtmp] = $data;
        }

        foreach ($registry_data as $key => $value) {
            foreach ($db_registries as $keydb => $arraydb) {
                if ($arraydb['key'] == $key) {
                    $input = ['key'   => $arraydb['key'],
                        'id'    => $keydb,
                        'value' => $value,
                    ];
                    $this->update($input);
                    unset($registry_data[$key]);
                    unset($db_registries[$keydb]);
                    break;
                }
            }
        }

        // Stale keys are removed by resetContent() on the first received answer, so we only
        // update the values still reported and add the new ones here.
        foreach ($registry_data as $key => $value) {
            if (preg_match("/^0x[0-9a-fA-F]{1,}$/", $value)) {
                $value = hexdec($value);
            }
            $input = [
                'computers_id' => $computers_id,
                'plugin_glpiinventory_collects_registries_id' => $collects_registries_id,
                'key'          => $key,
                'value'        => $value,
            ];
            $this->add($input);
        }
    }

    /**
     * Reset the collected content of every registry of a collect, for the given computer.
     * Called when the first answer of a collect run is received, so the previously collected
     * data survives if the agent fails or is stopped before sending anything. This makes the
     * per-answer deletions unnecessary.
     *
     * @param int $collects_id  id of the collect
     * @param int $computers_id id of the computer
     */
    public static function resetContent(int $collects_id, int $computers_id): void
    {
        /** @var DBmysql $DB */
        global $DB;

        $registry   = new PluginGlpiinventoryCollect_Registry();
        $registries = $registry->find(['plugin_glpiinventory_collects_id' => $collects_id]);
        foreach ($registries as $one_registry) {
            $DB->delete(
                'glpi_plugin_glpiinventory_collects_registries_contents',
                [
                    'plugin_glpiinventory_collects_registries_id' => $one_registry['id'],
                    'computers_id'                                => $computers_id,
                ]
            );
        }
    }

    /**
     * Tell whether the agent returned actual registry data (i.e. anything else than
     * the control/flag keys). Used as a fallback for the "path existence" and
     * "key defined" modes when the agent does not support the dedicated flags:
     * getting a value back means the path/key exists.
     *
     * @param array<string,mixed> $registry_data
     */
    public static function hasReturnedData(array $registry_data): bool
    {
        $control = [
            '_exists' => true,
            '_defined' => true,
            '_path' => true,
            '_value' => true,
            '_cpt' => true,
            '_count' => true,
            'method' => true,
        ];
        return count(array_diff_key($registry_data, $control)) > 0;
    }

    /**
     * Build a human-readable job-log message for a registry collect answer, so the
     * task execution log shows the tested path and the verdict instead of raw JSON.
     * Returns null for the default and recursion modes (raw payload is kept).
     *
     * @param PluginGlpiinventoryCollect_Registry $registry the collect registry
     * @param array<string,mixed> $registry_data the agent answer
     * @param int                 $count         count of found values
     */
    public static function getAnswerLogMessage(PluginGlpiinventoryCollect_Registry $registry, array $registry_data, int $count): ?string
    {
        $mode = (int) ($registry->fields['mode'] ?? PluginGlpiinventoryCollect_Registry::MODE_DEFAULT);

        switch ($mode) {
            case PluginGlpiinventoryCollect_Registry::MODE_PATH_EXISTS:
                $exists = isset($registry_data['_exists'])
                    ? (bool) $registry_data['_exists']
                    : false;
                return sprintf(
                    $exists
                        ? __('%s path found', 'glpiinventory')
                        : __('%s path not found', 'glpiinventory'),
                    $registry->fields['name']
                );

            case PluginGlpiinventoryCollect_Registry::MODE_KEY_DEFINED:
                $defined = isset($registry_data['_defined'])
                    ? (bool) $registry_data['_defined']
                    : false;
                return sprintf(
                    $defined
                        ? __('%s value exists', 'glpiinventory')
                        : __('%s value does not exist', 'glpiinventory'),
                    $registry->fields['name']
                );

            default:
                if (isset($registry_data['_path'])) {
                    if ($count === 1) {
                        return sprintf(
                            __('%s: Found a value', 'glpiinventory'),
                            $registry->fields['name']
                        );
                    } else {
                        return sprintf(
                            __('%s: Found %d values', 'glpiinventory'),
                            $registry->fields['name'],
                            $count
                        );
                    }
                }
                return sprintf(
                    __('%s: Found a result', 'glpiinventory'),
                    $registry->fields['name']
                );
        }
    }

    /**
     * Replace the (single) collected result for a computer and a collect registry.
     * Used by the "path existence" and "key defined" modes, which
     * report a single yes/no result.
     *
     * @param int    $computers_id           id of the computer
     * @param int    $collects_registries_id id of the collect registry
     * @param string $key                    key to store (empty for the existence check)
     * @param string $value                  value to store ('0' or '1')
     */
    private function storeSingleResult(int $computers_id, int $collects_registries_id, string $key, string $value): void
    {
        // Upsert the single result row (content is already reset on the first received answer,
        // see resetContent(); this just keeps it idempotent).
        $existing = $this->find([
            'computers_id' => $computers_id,
            'plugin_glpiinventory_collects_registries_id' => $collects_registries_id,
        ], [], 1);
        if (count($existing)) {
            $this->update([
                'id'    => current($existing)['id'],
                'key'   => $key,
                'value' => $value,
            ]);
        } else {
            $this->add([
                'computers_id' => $computers_id,
                'plugin_glpiinventory_collects_registries_id' => $collects_registries_id,
                'key'          => $key,
                'value'        => $value,
            ]);
        }
    }

    /**
     * Get the label for the "path existence" result
     *
     * @param mixed $value the stored value (1 = present, 0 = absent)
     */
    public static function getExistenceLabel($value): string
    {
        return ((int) $value === 1)
            ? __('Exist', 'glpiinventory')
            : __('Does not exist', 'glpiinventory');
    }

    /**
     * Get the label for the "key defined" result
     *
     * @param mixed $value the stored value (1 = defined, 0 = not defined)
     */
    public static function getDefinedLabel($value): string
    {
        return ((int) $value === 1)
            ? __('Defined', 'glpiinventory')
            : __('Not defined', 'glpiinventory');
    }

    /**
     * Show registries keys of the computer
     *
     * @param int $computers_id id of the computer
     */
    public function showForComputer(int $computers_id): void
    {
        $pfCollect_Registry = new PluginGlpiinventoryCollect_Registry();
        echo "<table class='tab_cadre_fixe'>";
        $a_data = $this->find(
            ['computers_id' => $computers_id],
            ['plugin_glpiinventory_collects_registries_id', 'key']
        );
        $previous_key = 0;
        $mode         = PluginGlpiinventoryCollect_Registry::MODE_DEFAULT;
        foreach ($a_data as $data) {
            if ($previous_key != $data['plugin_glpiinventory_collects_registries_id']) {
                $pfCollect_Registry->getFromDB($data['plugin_glpiinventory_collects_registries_id']);
                $mode = (int) ($pfCollect_Registry->fields['mode'] ?? PluginGlpiinventoryCollect_Registry::MODE_DEFAULT);

                $colspan = ($mode === PluginGlpiinventoryCollect_Registry::MODE_PATH_EXISTS) ? 2 : 3;
                echo "<tr class='tab_bg_1'>";
                echo '<th colspan="' . $colspan . '">';
                echo $pfCollect_Registry->fields['name'];
                echo '</th>';
                echo '</tr>';

                echo "<tr>";
                echo "<th>" . __('Path', 'glpiinventory') . "</th>";
                if ($mode === PluginGlpiinventoryCollect_Registry::MODE_KEY_DEFINED) {
                    echo "<th>" . __('Key', 'glpiinventory') . "</th>";
                    echo "<th>" . __('State', 'glpiinventory') . "</th>";
                } elseif ($mode === PluginGlpiinventoryCollect_Registry::MODE_PATH_EXISTS) {
                    echo "<th>" . __('State', 'glpiinventory') . "</th>";
                } else {
                    echo "<th>" . __('Value', 'glpiinventory') . "</th>";
                    echo "<th>" . __('Data', 'glpiinventory') . "</th>";
                }
                echo "</tr>";

                $previous_key = $data['plugin_glpiinventory_collects_registries_id'];
            }

            echo "<tr class='tab_bg_1'>";
            echo '<td>';
            echo $pfCollect_Registry->fields['hive']
              . $pfCollect_Registry->fields['path'];
            echo '</td>';

            switch ($mode) {
                case PluginGlpiinventoryCollect_Registry::MODE_PATH_EXISTS:
                    echo '<td>' . self::getExistenceLabel($data['value']) . '</td>';
                    break;

                case PluginGlpiinventoryCollect_Registry::MODE_KEY_DEFINED:
                    echo '<td>' . $data['key'] . '</td>';
                    echo '<td>' . self::getDefinedLabel($data['value']) . '</td>';
                    break;

                default:
                    echo '<td>' . $data['key'] . '</td>';
                    echo '<td>' . $data['value'] . '</td>';
                    break;
            }
            echo "</tr>";
        }
        echo '</table>';
    }


    /**
     * Display registry keys / values of collect_registry id
     *
     * @param int $id
     *
     * @return void
     */
    public function showContent(int $id): void
    {
        $collect_registry = new PluginGlpiinventoryCollect_Registry();
        $collect_registry->getFromDB($id);
        $computer = new Computer();

        $mode = (int) ($collect_registry->fields['mode'] ?? PluginGlpiinventoryCollect_Registry::MODE_DEFAULT);

        $data = $this->find(
            ['plugin_glpiinventory_collects_registries_id' => $id],
            ['computers_id', 'key']
        );

        $columns    = [];
        $formatters = ['computer' => 'raw_html'];
        $entries    = [];

        switch (true) {
            case $mode === PluginGlpiinventoryCollect_Registry::MODE_PATH_EXISTS:
                $columns = [
                    'computer' => Computer::getTypeName(1),
                    'value'    => __('Value', 'glpiinventory'),
                ];
                foreach ($data as $row) {
                    $computer->getFromDB($row['computers_id']);
                    $entries[] = [
                        'computer' => $computer->getLink(),
                        'value'    => self::getExistenceLabel($row['value']),
                    ];
                }
                break;

            case $mode === PluginGlpiinventoryCollect_Registry::MODE_KEY_DEFINED:
                $columns = [
                    'computer' => Computer::getTypeName(1),
                    'key'      => __('Key', 'glpiinventory'),
                    'value'    => __('Value', 'glpiinventory'),
                ];
                foreach ($data as $row) {
                    $computer->getFromDB($row['computers_id']);
                    $entries[] = [
                        'computer' => $computer->getLink(),
                        'key'      => $row['key'],
                        'value'    => self::getDefinedLabel($row['value']),
                    ];
                }
                break;

            default:
                $columns = [
                    'computer' => Computer::getTypeName(1),
                    'value'    => __('Value', 'glpiinventory'),
                    'data'     => __('Data', 'glpiinventory'),
                ];
                foreach ($data as $row) {
                    $computer->getFromDB($row['computers_id']);
                    $entries[] = [
                        'computer' => $computer->getLink(),
                        'value'    => $row['key'],
                        'data'     => $row['value'],
                    ];
                }
                break;
        }

        echo '<div class="card">
            <div class="card-body">
                <h3 class="card-title">' . $collect_registry->fields['name'] . ' - ' . $collect_registry->fields['hive']
            . $collect_registry->fields['path'] . '</h3>';
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => $columns,
            'formatters' => $formatters,
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
        ]);
        echo '</div></div>';
    }
}
