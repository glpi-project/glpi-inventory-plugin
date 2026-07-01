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
                // Case 3: content has been cleaned at job dispatch (see PluginGlpiinventoryCollect::run()),
                // so we only append the reported entry here.
                if (array_key_exists('_path', $registry_data)) {
                    $path  = (string) $registry_data['_path'];
                    $value = (string) ($registry_data['_value'] ?? '');
                    if (isset($registry_data['_depth'])) {
                        $depth = (int) $registry_data['_depth'];
                    } else {
                        // fall back to the depth encoded in the relative path
                        $depth = substr_count(trim($path, '/'), '/');
                    }
                    if (preg_match("/^0x[0-9a-fA-F]{1,}$/", $value)) {
                        $value = hexdec($value);
                    }
                    $this->add([
                        'computers_id' => $computers_id,
                        'plugin_glpiinventory_collects_registries_id' => $collects_registries_id,
                        'key'          => $path,
                        'value'        => $value,
                        'depth'        => $depth,
                    ]);
                    return;
                }
                break;
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

        foreach (array_keys($db_registries) as $id) {
            $this->delete(['id' => $id], true);
        }
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
            '_depth' => true,
            '_cpt' => true,
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
     */
    public static function getAnswerLogMessage(PluginGlpiinventoryCollect_Registry $registry, array $registry_data): ?string
    {
        $mode = (int) ($registry->fields['mode'] ?? PluginGlpiinventoryCollect_Registry::MODE_DEFAULT);
        $path = $registry->fields['hive'] . $registry->fields['path'];

        switch ($mode) {
            case PluginGlpiinventoryCollect_Registry::MODE_PATH_EXISTS:
                $exists = isset($registry_data['_exists'])
                    ? (bool) $registry_data['_exists']
                    : self::hasReturnedData($registry_data);
                return sprintf(
                    $exists
                        ? __('%s found', 'glpiinventory')
                        : __('%s not found', 'glpiinventory'),
                    self::toWindowsPath($path)
                );

            case PluginGlpiinventoryCollect_Registry::MODE_KEY_DEFINED:
                $defined = isset($registry_data['_defined'])
                    ? (bool) $registry_data['_defined']
                    : self::hasReturnedData($registry_data);
                return sprintf(
                    $defined
                        ? __('%s exists', 'glpiinventory')
                        : __("%s doesn't exist", 'glpiinventory'),
                    self::toWindowsPath($path . $registry->fields['key'])
                );

            default:
                return null;
        }
    }

    /**
     * Format a registry path the Windows way: use backslashes as separators and
     * collapse any duplicate backslashes so no "\\" appears in the output.
     */
    private static function toWindowsPath(string $path): string
    {
        $path = str_replace('/', '\\', $path);
        return preg_replace('/\\\\+/', '\\\\', $path);
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
        /** @var DBmysql $DB */
        global $DB;

        $DB->delete(
            'glpi_plugin_glpiinventory_collects_registries_contents',
            [
                'computers_id' => $computers_id,
                'plugin_glpiinventory_collects_registries_id' => $collects_registries_id,
            ]
        );
        $this->add([
            'computers_id' => $computers_id,
            'plugin_glpiinventory_collects_registries_id' => $collects_registries_id,
            'key'          => $key,
            'value'        => $value,
            'depth'        => 0,
        ]);
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
     * Render a registry path indented according to its depth
     *
     * @param string $path  the (relative) registry path
     * @param int    $depth the depth of the entry in the tree
     */
    public static function getIndentedPath(string $path, int $depth): string
    {
        $padding = max(0, $depth) * 20;
        return '<span style="padding-left: ' . $padding . 'px;">' . htmlspecialchars($path) . '</span>';
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
            ['plugin_glpiinventory_collects_registries_id', 'depth', 'key']
        );
        $previous_key  = 0;
        $mode          = PluginGlpiinventoryCollect_Registry::MODE_DEFAULT;
        $depth_enabled = false;
        foreach ($a_data as $data) {
            if ($previous_key != $data['plugin_glpiinventory_collects_registries_id']) {
                $pfCollect_Registry->getFromDB($data['plugin_glpiinventory_collects_registries_id']);
                $mode          = (int) ($pfCollect_Registry->fields['mode'] ?? PluginGlpiinventoryCollect_Registry::MODE_DEFAULT);
                $depth_enabled = ($mode === PluginGlpiinventoryCollect_Registry::MODE_DEPTH);

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
                    echo "<th>" . __('Value', 'glpiinventory') . "</th>";
                } elseif ($mode === PluginGlpiinventoryCollect_Registry::MODE_PATH_EXISTS) {
                    echo "<th>" . __('Value', 'glpiinventory') . "</th>";
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
                    echo '<td>';
                    echo $depth_enabled
                        ? self::getIndentedPath((string) $data['key'], (int) ($data['depth'] ?? 0))
                        : $data['key'];
                    echo '</td>';
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

            case $mode === PluginGlpiinventoryCollect_Registry::MODE_DEPTH:
                $columns = [
                    'computer' => Computer::getTypeName(1),
                    'value'    => __('Path', 'glpiinventory'),
                    'data'     => __('Data', 'glpiinventory'),
                ];
                $formatters['value'] = 'raw_html';
                foreach ($data as $row) {
                    $computer->getFromDB($row['computers_id']);
                    $entries[] = [
                        'computer' => $computer->getLink(),
                        'value'    => self::getIndentedPath((string) $row['key'], (int) ($row['depth'] ?? 0)),
                        'data'     => $row['value'],
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
