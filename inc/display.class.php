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
 * Manage the general display in plugin.
 */
class PluginGlpiinventoryDisplay extends CommonDBTM
{
    /**
     * Display static progress bar (used for SNMP cartridge state)
     *
     * @param integer $percentage
     * @param string $message
     * @param string $order
     * @param integer $width
     * @param integer $height
     */
    public static function bar($percentage, $message = '', $order = '', $width = 400, $height = 20)
    {
        if (!empty($percentage) && $percentage < 0) {
            $percentage = 0;
        } elseif (!empty($percentage) && $percentage > 100) {
            $percentage = 100;
        }
        echo "<div>
               <table class='tab_cadre' width='" . $width . "'>
                     <tr>
                        <td align='center' width='" . $width . "'>";

        if (!empty($percentage)) {
            echo $percentage . "% " . $message;
        }

        echo                  "</td>
                     </tr>
                     <tr>
                        <td>
                           <table cellpadding='0' cellspacing='0'>
                                 <tr>
                                    <td width='" . $width . "' height='0' colspan='2'></td>
                                 </tr>
                                 <tr>";
        if (empty($percentage)) {
            echo "<td></td>";
        } else {
            echo "                              <td bgcolor='";
            if ($order != '') {
                if ($percentage > 80) {
                    echo "red";
                } elseif ($percentage > 60) {
                    echo "orange";
                } else {
                    echo "green";
                }
            } else {
                if ($percentage < 20) {
                    echo "red";
                } elseif ($percentage < 40) {
                    echo "orange";
                } else {
                    echo "green";
                }
            }
            echo "' height='" . $height . "' width='" . (($width * $percentage) / 100) . "'>&nbsp;</td>";
        }
        if ($percentage == 0) {
            echo "                           <td height='" . $height . "' width='1'></td>";
        } else {
            echo "                           <td height='" . $height . "' width='"
                 . ($width - (($width * $percentage) / 100)) . "'></td>";
        }
        echo "                        </tr>
                           </table>
                        </td>
                     </tr>
               </table>
            </div>";
    }


    /**
     * Disable debug mode to not see php errors
     */
    public static function disableDebug()
    {
        error_reporting(0);
        set_error_handler([PluginGlpiinventoryDisplay::class, 'error_handler']);
    }


    /**
    * Enable debug mode if user is in debug mode
    **/
    public static function reenableusemode()
    {
        Toolbox::setDebugMode();
    }


    /**
     * When debug is disabled, we transfer every errors in this emtpy function.
     *
     * @param integer $errno
     * @param string $errstr
     * @param string $errfile
     * @param integer $errline
     */
    public static function error_handler($errno, $errstr, $errfile, $errline) {}


    /**
     * Display progress bar
     *
     * @param integer $width
     * @param integer|float $percent
     * @param array $options
     * @return string
     */
    public static function getProgressBar($width, $percent, array $options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $param = [];
        $param['title'] = __('Progress', 'glpiinventory');
        $param['simple'] = false;

        if (count($options)) {
            foreach ($options as $key => $val) {
                $param[$key] = $val;
            }
        }

        $percentwidth = floor($percent * $width / 100);
        $output = "<div class='center'><table class='tab_cadre' width='" . ($width + 20) . "px'>";
        if (!$param['simple']) {
            $output .= "<tr><th class='center'>" . $param['title'] . "&nbsp;" . $percent . "%</th></tr>";
        }
        $output .= "<tr><td>
                <table><tr><td class='center' style='background:url(" . $CFG_GLPI["root_doc"]
                . "/pics/loader.png) repeat-x;' width='.$percentwidth' height='12'>";
        if ($param['simple']) {
            $output .= $percent . "%";
        } else {
            $output .= '&nbsp;';
        }
        $output .= "</td></tr></table></td>";
        $output .= "</tr></table>";
        $output .= "</div>";
        return $output;
    }
}
