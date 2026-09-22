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

use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SearchOptionsTest extends DbTestCase
{
    /**
     * All plugin itemtypes that declare their own search options.
     *
     * @return iterable<string,array{class-string<CommonDBTM>}>
     */
    public static function itemtypeProvider(): iterable
    {
        foreach (glob(__DIR__ . '/../../inc/*.class.php') as $path) {
            $contents = file_get_contents($path);
            if (!preg_match('/^class (\w+) extends /m', $contents, $matches)) {
                continue;
            }
            $itemtype = $matches[1];
            if (!is_subclass_of($itemtype, CommonDBTM::class)) {
                continue;
            }
            $reflection = new ReflectionClass($itemtype);
            if ($reflection->isAbstract()) {
                continue;
            }
            if ($reflection->getMethod('rawSearchOptions')->getDeclaringClass()->getName() !== $itemtype) {
                continue;
            }
            yield $itemtype => [$itemtype];
        }
    }

    /**
     * An `itemlink` search option that does not declare its `itemtype` relies on
     * `getItemTypeForTable()` to guess it from the table name. That guess returns null
     * for our classes when the plugin is installed outside of the GLPI tree, and the
     * search then fails with "Class name must be a valid object or a string".
     *
     * Only our own tables are checked here; core tables always resolve to a core class.
     *
     * @see https://github.com/glpi-project/glpi-inventory-plugin/issues/975
     */
    #[DataProvider('itemtypeProvider')]
    public function testItemlinkSearchOptionsDeclareTheirItemtype(string $itemtype): void
    {
        foreach (Search::getOptions($itemtype) as $id => $option) {
            if (!is_array($option) || ($option['datatype'] ?? null) !== 'itemlink') {
                continue;
            }
            if (!str_starts_with($option['table'] ?? '', 'glpi_plugin_glpiinventory_')) {
                continue;
            }

            $this->assertArrayHasKey(
                'itemtype',
                $option,
                sprintf('Search option %s of %s is an itemlink without itemtype', $id, $itemtype)
            );
            $this->assertTrue(
                is_a($option['itemtype'], CommonDBTM::class, true),
                sprintf('Search option %s of %s declares an invalid itemtype', $id, $itemtype)
            );
        }
    }
}
