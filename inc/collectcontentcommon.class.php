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

/**
 * Manage the files found by the collect module of agent.
 */
class PluginGlpiinventoryCollectContentCommon extends CommonDBTM
{
    /**
     * The right name for this class
     *
     * @var string
     */
    public static $rightname        = 'plugin_glpiinventory_collect';
    public string $collect_itemtype = '';
    public string $collect_table    = '';
    public string $collect_type     = '';

    /**
     * Get name of this type by language of the user connected
     *
     * @param int $nb number of elements
     * @return string name of this type
     */
    public static function getTypeName($nb = 0)
    {
        $class = static::class;
        return $class::getTypeName();
    }

    /**
     * Get the collect associated with the content class
     * @since 9.2+2.0
     *
     * @return string the collect class name
     */
    public function getCollectClass()
    {
        $class = static::class;
        $item  = new $class();
        return $item->collect_itemtype;
    }

    /**
     * Display the content of the tab
     *
     * @param CommonGLPI $item
     * @param int $tabnum number of the tab to display
     * @param int $withtemplate 1 if is a template form
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $class            = static::class;
        $pfCollectContent = new $class();
        if ($item instanceof PluginGlpiinventoryCollect) {
            $pfCollectContent->showForCollect($item->fields['id']);
        }
        return true;
    }

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
            $class   = $this->collect_itemtype;
            $collect = $this->getCollectClass();
            if (
                $item instanceof PluginGlpiinventoryCollect
                && $item->fields['type'] == $this->collect_type
            ) {
                $a_colfiles = getAllDataFromTable(
                    $collect::getTable(),
                    ['plugin_glpiinventory_collects_id' => $item->fields['id']]
                );
                if (count($a_colfiles) == 0) {
                    return '';
                }
                $in = array_keys($a_colfiles);
                $fk = getForeignKeyFieldForItemType($collect);
                if (
                    ($nb = countElementsInTable(
                        $this->getTable(),
                        [$fk => $in]
                    )) > 0
                ) {
                    return self::createTabEntry($collect::getTypeName(Session::getPluralNumber()), $nb, null, $class::getIcon());
                }
            }
        }
        return '';
    }

    /**
     * Delete all contents linked to the computer (most cases when delete a
     * computer)
     */
    public static function cleanComputer(int $computers_id): void
    {
        $classname = static::class;
        $content   = new $classname();
        $content->deleteByCriteria(['computers_id' => $computers_id]);
    }

    /**
     * Show all files defined
     *
     * @param int $collects_id id of collect
     *
     * @return void
     */
    public function showForCollect(int $collects_id): void
    {
        /** @var DBmysql $DB */
        global $DB;
        $class  = $this->collect_itemtype;
        $params = [
            'FROM'   => $class::getTable(),
            'FIELDS' => [
                'id',
            ],
            'WHERE'  => [
                'plugin_glpiinventory_collects_id' => $collects_id,
            ],
        ];
        $iterator = $DB->request($params);

        echo '<div class="d-flex gap-3 flex-column">';
        foreach ($iterator as $data) {
            $this->showContent((int) $data['id']);
        }
        echo "</div>";
    }

    public function showContent(int $id): void {}

    protected function countContentForDefinition(int $definitions_id): int
    {
        return countElementsInTable(
            $this->getTable(),
            [getForeignKeyFieldForItemType($this->getCollectClass()) => $definitions_id]
        );
    }

    /**
     * @param array<int, string> $order
     * @return array<int, array<string, mixed>>
     */
    protected function getContentPageForDefinition(int $definitions_id, array $order, int $start, int $limit): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $table    = $this->getTable();
        $iterator = $DB->request([
            'SELECT'    => [$table . '.*', 'glpi_computers.name AS computer_name'],
            'FROM'      => $table,
            'LEFT JOIN' => [
                'glpi_computers' => [
                    'ON' => [
                        $table           => 'computers_id',
                        'glpi_computers' => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                $table . '.' . getForeignKeyFieldForItemType($this->getCollectClass()) => $definitions_id,
            ],
            'ORDER'     => array_map(fn(string $field): string => $table . '.' . $field, $order),
            'START'     => $start,
            'LIMIT'     => $limit,
        ]);

        return iterator_to_array($iterator, false);
    }

    /**
     * @param array<string, mixed> $row
     */
    protected static function getComputerLinkFromRow(array $row): string
    {
        $computers_id = (int) $row['computers_id'];
        $name         = (string) ($row['computer_name'] ?? '');

        return sprintf(
            '<a href="%s">%s</a>',
            htmlescape(Computer::getFormURLWithID($computers_id)),
            htmlescape($name !== '' ? $name : sprintf('(%d)', $computers_id))
        );
    }

    protected static function getRequestedStart(): int
    {
        return max(0, (int) ($_GET['start'] ?? 0));
    }
}
