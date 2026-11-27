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
use Safe\Exceptions\JsonException;

use function Safe\json_decode;
use function Safe\json_encode;

/**
 * Manage the rows returned by osquery queries run by the collect module of agent.
 *
 * The agent sends one answer per result row, each row is stored as a JSON object.
 */
class PluginGlpiinventoryCollect_Osquery_Content extends PluginGlpiinventoryCollectContentCommon
{
    public string $collect_itemtype = PluginGlpiinventoryCollect_Osquery::class;
    public string $collect_table    = 'glpi_plugin_glpiinventory_collects_osqueries';

    public string $collect_type = 'osquery';

    /**
     * Store one result row sent by the agent
     *
     * @param int $computers_id id of the computer
     * @param array<string,mixed> $osquery_data one result row (column => value)
     * @param int $collects_osqueries_id
     */
    public function updateComputer($computers_id, $osquery_data, $collects_osqueries_id): void
    {
        unset($osquery_data['_sid']);
        if (count($osquery_data) === 0) {
            return;
        }
        $this->add([
            'computers_id' => $computers_id,
            'plugin_glpiinventory_collects_osqueries_id' => $collects_osqueries_id,
            'data' => json_encode($osquery_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Record that a query ran on a computer without returning any row
     *
     * The marker has no data, it is skipped when displaying rows.
     */
    public function markNoRow(int $computers_id, int $collects_osqueries_id): void
    {
        $this->add([
            'computers_id' => $computers_id,
            'plugin_glpiinventory_collects_osqueries_id' => $collects_osqueries_id,
        ]);
    }

    /**
     * Remove rows of all queries of a collect for a computer, before storing a fresh result
     */
    public static function resetContent(int $collects_id, int $computers_id): void
    {
        /** @var DBmysql $DB */
        global $DB;

        $osquery = new PluginGlpiinventoryCollect_Osquery();
        $ids = array_keys($osquery->find(['plugin_glpiinventory_collects_id' => $collects_id]));
        if ($ids === []) {
            return;
        }
        $DB->delete(
            'glpi_plugin_glpiinventory_collects_osqueries_contents',
            [
                'plugin_glpiinventory_collects_osqueries_id' => $ids,
                'computers_id'                               => $computers_id,
            ]
        );
    }

    /**
     * Build datatable columns and entries from stored rows
     *
     * @param array<array<string,mixed>> $rows content rows from DB
     * @param array<string,string> $columns leading columns
     * @param callable(array<string,mixed>): array<string,mixed> $prefix values of the leading columns
     * @return array{columns: array<string,string>, entries: array<array<string,mixed>>}
     */
    public static function buildDatatable(array $rows, array $columns, callable $prefix): array
    {
        $entries = [];
        foreach ($rows as $row) {
            if ($row['data'] === null) {
                // "no row returned" marker
                continue;
            }
            try {
                $data = json_decode($row['data'], true);
            } catch (JsonException) {
                $data = [];
            }
            if (!is_array($data)) {
                $data = [];
            }
            // Agent-provided column names are namespaced so they can never reach the
            // raw_html formatter of the server-built computer link
            $entry = $prefix($row);
            foreach ($data as $key => $value) {
                $columns['col_' . $key] ??= (string) $key;
                $entry['col_' . $key] = is_scalar($value) ? (string) $value : json_encode($value);
            }
            $entries[] = $entry;
        }
        return ['columns' => $columns, 'entries' => $entries];
    }

    /**
     * Build the template item of one query
     *
     * @param array<array<string,mixed>> $rows content rows from DB
     * @param array<string,string> $columns leading columns
     * @param callable(array<string,mixed>): array<string,mixed> $prefix values of the leading columns
     * @return array<string,mixed>
     */
    private static function buildItem(PluginGlpiinventoryCollect_Osquery $osquery, array $rows, array $columns, callable $prefix): array
    {
        ['columns' => $columns, 'entries' => $entries] = self::buildDatatable($rows, $columns, $prefix);
        return [
            'name'       => $osquery->fields['name'] ?? '',
            'query'      => $osquery->fields['query'] ?? '',
            'query_html' => PluginGlpiinventoryCollect_Osquery::highlightQuery($osquery->fields['query'] ?? ''),
            'columns'    => $columns,
            'formatters' => isset($columns['computer']) ? ['computer' => 'raw_html'] : [],
            'entries'    => $entries,
        ];
    }

    /**
     * @param array<array<string,mixed>> $items
     */
    private static function renderItems(array $items, bool $accordion): void
    {
        TemplateRenderer::getInstance()->display('@glpiinventory/collect/osquery_results.html.twig', [
            'items'     => $items,
            'accordion' => $accordion,
        ]);
    }

    /**
     * Display osquery results of computer, one accordion item per query
     *
     * @param int $computers_id id of computer
     */
    public function showForComputer(int $computers_id): void
    {
        $rows_by_query = [];
        foreach ($this->find(['computers_id' => $computers_id], ['id']) as $row) {
            $rows_by_query[$row['plugin_glpiinventory_collects_osqueries_id']][] = $row;
        }

        $items = [];
        foreach ($rows_by_query as $osqueries_id => $rows) {
            $osquery = new PluginGlpiinventoryCollect_Osquery();
            if (!$osquery->getFromDB($osqueries_id)) {
                continue;
            }
            $items[] = self::buildItem($osquery, $rows, [], static fn() => []);
        }
        if ($items !== []) {
            self::renderItems($items, true);
        }
    }

    /**
     * Display osquery results of collect_osquery_id
     */
    public function showContent(int $id): void
    {
        $osquery = new PluginGlpiinventoryCollect_Osquery();
        if (!$osquery->getFromDB($id)) {
            return;
        }
        $computer = new Computer();
        $rows = $this->find(['plugin_glpiinventory_collects_osqueries_id' => $id], ['computers_id', 'id']);
        self::renderItems([
            self::buildItem(
                $osquery,
                $rows,
                ['computer' => Computer::getTypeName(1)],
                static fn(array $row) => [
                    'computer' => $computer->getFromDB($row['computers_id']) ? $computer->getLink() : '',
                ]
            ),
        ], false);
    }
}
