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

/**
 * Manage the hours in the timeslot.
 */
class PluginGlpiinventoryTimeslotEntry extends CommonDBTM
{
    /**
     * We activate the history.
     *
     * @var bool
     */
    public $dohistory = true;

    /**
     * The right name for this class
     *
     * @var string
     */
    public static $rightname = 'plugin_glpiinventory_task';


    /**
     * Get name of this type by language of the user connected
     *
     * @param int $nb number of elements
     * @return string name of this type
     */
    public static function getTypeName($nb = 0)
    {
        return __('Time slot entry', 'glpiinventory');
    }


    /**
     * Get search function for the class
     *
     * @return array<array<string,mixed>>
     */
    public function rawSearchOptions()
    {

        $tab = [];

        $tab[] = [
            'id' => 'common',
            'name' => __('Time slot', 'glpiinventory'),
        ];

        $tab[] = [
            'id'        => '1',
            'table'     => $this->getTable(),
            'field'     => 'name',
            'name'      => __('Name'),
            'datatype'  => 'itemlink',
        ];

        $tab[] = [
            'id'       => '2',
            'table'    => 'glpi_entities',
            'field'    => 'completename',
            'name'     => Entity::getTypeName(1),
            'datatype' => 'dropdown',
        ];

        $tab[] = [
            'id'        => '3',
            'table'     => $this->getTable(),
            'field'     => 'is_recursive',
            'name'      => __('Child entities'),
            'datatype'  => 'bool',
        ];

        $tab[] = [
            'id'        => '4',
            'table'     => $this->getTable(),
            'field'     => 'name',
            'name'      => __('Name'),
            'datatype'  => 'string',
        ];

        return $tab;
    }


    /**
     * Display the time slot entries management: add form, entries list and the weekly grid.
     *
     * @param int $timeslots_id
     */
    public function formEntry(int $timeslots_id): void
    {
        // Days of week, keyed by their ISO-8601 numeric value (1 = Monday ... 7 = Sunday).
        $days = Toolbox::getDaysOfWeekArray();
        $days[7] = $days[0];
        unset($days[0]);

        // Available hours (in seconds since midnight) with a 15 minutes step.
        $hours = [];
        for ($seconds = 0; $seconds <= (24 * 3600); $seconds += (15 * 60)) {
            $hours[$seconds] = PluginGlpiinventoryToolbox::getHourMinute($seconds);
        }

        // Existing entries grouped by day.
        $dbentries = getAllDataFromTable(
            'glpi_plugin_glpiinventory_timeslotentries',
            [
                'WHERE'  => ['plugin_glpiinventory_timeslots_id' => $timeslots_id],
                'ORDER'  => ['day', 'begin ASC'],
            ]
        );

        $entries_by_day = [];
        $entries = [];
        foreach ($dbentries as $dbentry) {
            $entries_by_day[$dbentry['day']][] = $dbentry;
            $entries[] = [
                'id'        => $dbentry['id'],
                'day_label' => $days[$dbentry['day']] ?? $dbentry['day'],
                'begin'     => PluginGlpiinventoryToolbox::getHourMinute($dbentry['begin']),
                'end'       => PluginGlpiinventoryToolbox::getHourMinute($dbentry['end']),
            ];
        }

        TemplateRenderer::getInstance()->display('@glpiinventory/forms/timeslot/entry.html.twig', [
            'timeslots_id' => $timeslots_id,
            'target_entry' => self::getFormURL(),
            'days'         => $days,
            'hours'        => $hours,
            'entries'      => $entries,
            'canedit'      => Session::haveRight(self::$rightname, PURGE),
            'grid'         => $this->getWeekGrid($days, $entries_by_day),
        ]);
    }


    /**
     * Build the weekly grid used to visualise the configured time slots.
     *
     * Each day is split into 96 quarter-hour cells; a cell is active when it falls within
     * a configured entry range [begin, end[ (boundaries expressed in seconds).
     *
     * @param array<int,string> $days           Days of week keyed by ISO numeric value.
     * @param array<int,array<array<string,mixed>>> $entries_by_day Entries grouped by day.
     * @return array<array{day:int,label:string,cells:array<bool>}>
     */
    private function getWeekGrid(array $days, array $entries_by_day): array
    {
        $grid = [];
        foreach ($days as $daynum => $label) {
            $cells = [];
            for ($quarter = 0; $quarter < (24 * 4); $quarter++) {
                $active = false;
                foreach ($entries_by_day[$daynum] ?? [] as $entry) {
                    if ($quarter >= ($entry['begin'] / 900) && $quarter < ($entry['end'] / 900)) {
                        $active = true;
                        break;
                    }
                }
                $cells[] = $active;
            }
            $grid[] = [
                'day'   => $daynum,
                'label' => $label,
                'cells' => $cells,
            ];
        }
        return $grid;
    }


    /**
     * Add a new entry
     *
     * @param array<string,int> $data
     */
    public function addEntry(array $data): void
    {
        if ($data['lastday'] < $data['beginday']) {
            Session::addMessageAfterRedirect(
                __('End day must be after start day', 'glpiinventory'),
                true,
                ERROR
            );
            return;
        } elseif (
            $data['lastday'] == $data['beginday']
              && $data['lasthours'] <= $data['beginhours']
        ) {
            Session::addMessageAfterRedirect(
                __('End time must be after start time', 'glpiinventory'),
                true,
                ERROR
            );
            return;
        }
        // else ok, we can update DB
        for ($day = $data['beginday']; $day <= $data['lastday']; $day++) {
            $range = [];

            $range['beginhours'] = $data['beginhours'];
            $range['lasthours'] = $data['lasthours'];
            if ($data['beginday'] < $day) {
                $range['beginhours'] = 0;
            }
            if ($data['lastday'] > $day) {
                $range['lasthours'] = (24 * 3600);
            }

            // now get from DB
            $dbentries = getAllDataFromTable(
                'glpi_plugin_glpiinventory_timeslotentries',
                [
                    'WHERE'  => [
                        'plugin_glpiinventory_timeslots_id' => $data['timeslots_id'],
                        'day'                                 => $day,
                    ],
                    'ORDER'  => 'begin ASC',
                ]
            );

            $inThePeriod = false;
            $afterPeriod = false;
            $updateEntries = [];
            $deleteEntries = [];
            $addEntries = [];

            foreach ($dbentries as $entries) {
                if ($afterPeriod) {
                    continue;
                }

                if ($inThePeriod) {
                    // So we need manage the end
                    if ($range['lasthours'] < $entries['begin']) {
                        $addEntries[] = [
                            'plugin_glpiinventory_timeslots_id' => $data['timeslots_id'],
                            'day'   => $day,
                            'begin' => $range['beginhours'],
                            'end'   => $range['lasthours'],
                        ];
                        $inThePeriod = false;
                        $afterPeriod = true;
                        continue;
                    } elseif ($range['lasthours'] > $entries['end']) {
                        $deleteEntries[] = $entries;
                        continue;
                    } else {
                        $entries['begin'] = $range['beginhours'];
                        $updateEntries[] = $entries;
                        $inThePeriod = false;
                        $afterPeriod = true;
                        continue;
                    }
                } elseif (($range['lasthours'] < $entries['begin'])) {
                    // We add
                    $this->add([
                        'plugin_glpiinventory_timeslots_id' => $data['timeslots_id'],
                        'day'   => $day,
                        'begin' => $range['beginhours'],
                        'end'   => $range['lasthours'],
                    ]);
                    continue 2;
                } elseif ($range['beginhours'] > $entries['end']) {
                    // Not manage, hop to next entry
                    continue;
                }

                if ($range['beginhours'] < $entries['begin']) {
                    $inThePeriod = true;

                    if ($range['lasthours'] <= $entries['end']) {
                        $entries['begin'] = $range['beginhours'];
                        $updateEntries[] = $entries;
                        $inThePeriod = false;
                        $afterPeriod = true;
                    } else {
                        $deleteEntries[] = $entries;
                    }
                } elseif ($range['beginhours'] < $entries['end']) {
                    $inThePeriod = true;
                    $range['beginhours'] = $entries['begin'];

                    if ($range['lasthours'] <= $entries['end']) {
                        $entries['begin'] = $range['beginhours'];
                        $updateEntries[] = $entries;
                        $inThePeriod = false;
                        $afterPeriod = true;
                    } else {
                        $deleteEntries[] = $entries;
                    }
                }
            }
            if (count($dbentries) == 0) {
                $addEntries[] = [
                    'plugin_glpiinventory_timeslots_id' => $data['timeslots_id'],
                    'day'   => $day,
                    'begin' => $range['beginhours'],
                    'end'   => $range['lasthours'],
                ];
            } elseif ($inThePeriod || (count($updateEntries) == 0 && count($deleteEntries) == 0 & count($addEntries) == 0)) {
                $addEntries[] = [
                    'plugin_glpiinventory_timeslots_id' => $data['timeslots_id'],
                    'day'   => $day,
                    'begin' => $range['beginhours'],
                    'end'   => $range['lasthours'],
                ];
            }

            foreach ($updateEntries as $entry) {
                $this->update($entry);
            }
            foreach ($deleteEntries as $entry) {
                $this->delete(['id' => $entry['id']]);
            }
            foreach ($addEntries as $entry) {
                $this->add($entry);
            }
        }
    }
}
