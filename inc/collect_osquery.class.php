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

use function Safe\preg_match;
use function Safe\preg_match_all;

/**
 * Manage the osquery queries to run in collect module.
 */
class PluginGlpiinventoryCollect_Osquery extends PluginGlpiinventoryCollectCommon
{
    public string $collect_type = 'osquery';

    /**
     * Get name of this type by language of the user connected
     *
     * @param int $nb number of elements
     * @return string name of this type
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Osquery query', 'Osquery queries', $nb, 'glpiinventory');
    }

    public static function getIcon()
    {
        return "ti ti-database-search";
    }

    public function getListHeaders(): array
    {
        return [
            'query' => __('Query', 'glpiinventory'),
        ];
    }

    public function displayOneRow(array $row = []): array
    {
        return [
            'query' => $row['query'],
        ];
    }

    /**
     * Agents only run a single SELECT statement through osqueryi.
     */
    public static function isValidQuery(string $query): bool
    {
        $query = rtrim(trim($query), ';');
        return preg_match('/^select\s/i', $query) === 1
            && !str_contains($query, ';');
    }

    private const SQL_KEYWORDS = [
        'ALL', 'AND', 'AS', 'ASC', 'BETWEEN', 'BY', 'CASE', 'CAST', 'COLLATE', 'CROSS', 'DESC',
        'DISTINCT', 'ELSE', 'END', 'ESCAPE', 'EXCEPT', 'EXISTS', 'FROM', 'FULL', 'GLOB', 'GROUP',
        'HAVING', 'IN', 'INNER', 'INTERSECT', 'IS', 'JOIN', 'LEFT', 'LIKE', 'LIMIT', 'NATURAL',
        'NOT', 'NULL', 'OFFSET', 'ON', 'OR', 'ORDER', 'OUTER', 'REGEXP', 'RIGHT', 'SELECT', 'THEN',
        'UNION', 'USING', 'WHEN', 'WHERE', 'WITH',
    ];

    /**
     * Light SQL syntax highlighting, every token is HTML-escaped
     */
    public static function highlightQuery(string $query): string
    {
        preg_match_all(
            '/(?<string>\'(?:[^\']|\'\')*\'?|"(?:[^"]|"")*"?)|(?<number>\b\d+(?:\.\d+)?\b)|(?<word>[A-Za-z_]\w*)|(?<other>\s+|.)/s',
            $query,
            $matches,
            PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL
        );

        $html = '';
        foreach ($matches as $match) {
            $token = $match[0];
            $class = match (true) {
                $match['string'] !== null => 'text-success',
                $match['number'] !== null => 'text-orange',
                $match['word'] !== null && in_array(strtoupper($token), self::SQL_KEYWORDS, true) => 'text-indigo fw-bold',
                default => null,
            };
            $html .= $class === null
                ? htmlescape($token)
                : '<span class="' . $class . '">' . htmlescape($token) . '</span>';
        }
        return $html;
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->checkQuery($input);
        if ($input === false) {
            return false;
        }
        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        if (!array_key_exists('query', $input)) {
            return parent::prepareInputForUpdate($input);
        }
        $input = $this->checkQuery($input);
        if ($input === false) {
            return false;
        }
        return parent::prepareInputForUpdate($input);
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>|false
     */
    private function checkQuery(array $input): array|false
    {
        $query = trim((string) ($input['query'] ?? ''));
        if (!self::isValidQuery($query)) {
            Session::addMessageAfterRedirect(
                __('The osquery query must be a single SELECT statement.', 'glpiinventory'),
                false,
                ERROR
            );
            return false;
        }
        $input['query'] = rtrim($query, "; \t\n\r");
        return $input;
    }

    public function post_purgeItem()
    {
        $content = new PluginGlpiinventoryCollect_Osquery_Content();
        $content->deleteByCriteria(['plugin_glpiinventory_collects_osqueries_id' => $this->getID()], true);
        parent::post_purgeItem();
    }
}
