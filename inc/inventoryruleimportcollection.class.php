<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Manage import rule collection for inventory.
 */
class PluginFusioninventoryInventoryRuleImportCollection extends RuleCollection {


   /**
    * Set stop play rules when have the first rule of list match
    *
    * @var boolean
    */
   public $stop_on_first_match = true;

   /**
    * Define the name of menu option
    *
    * @var string
    */
   public $menu_option         = 'fusionlinkcomputer';

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname           = "plugin_fusioninventory_ruleimport";


   /**
    * Get name of this type by language of the user connected
    *
    * @return string name of this type
    */
   function getTitle() {
      return __('Equipment import and link rules', 'glpiinventory');
   }


   /**
    * Prepare input data for process the rule
    *
    * @param array $input
    * @param array $params
    * @return array
    */
   function prepareInputDataForProcess($input, $params) {
      return array_merge($input, $params);
   }


   /**
    * Make some changes before process review result
    *
    * @param array $output
    * @return array
    */
   function preProcessPreviewResults($output) {
      if (isset($output["action"])) {
         echo "<tr class='tab_bg_2'>";
         echo "<td>".__('Action type')."</td>";
         echo "<td>";

         switch ($output["action"]) {
            case PluginFusioninventoryInventoryRuleImport::LINK_RESULT_LINK :
               echo __('Link');

               break;

            case PluginFusioninventoryInventoryRuleImport::LINK_RESULT_CREATE:
               echo __('Device created', 'glpiinventory');

               break;

            case PluginFusioninventoryInventoryRuleImport::LINK_RESULT_DENIED:
               echo __('Import denied', 'glpiinventory');

               break;

         }

         echo "</td>";
         echo "</tr>";
         if ($output["action"] != PluginFusioninventoryInventoryRuleImport::LINK_RESULT_DENIED
             && isset($output["found_equipment"])) {
            echo "<tr class='tab_bg_2'>";
            $className = $output["found_equipment"][1];
            $class     = new $className();
            if ($class->getFromDB($output["found_equipment"][0])) {
               echo "<td>".__('Link')."</td>";
               echo "<td>".$class->getLink(true)."</td>";
            }
            echo "</tr>";
         }
      }
      return $output;
   }


   /**
    * Get collection datas: retrieve descriptions and rules
    *
    * @global object $DB
    * @param integer $retrieve_criteria
    * @param integer $retrieve_action
    * @param integer $condition
    */
   function getCollectionDatas($retrieve_criteria = 0, $retrieve_action = 0, $condition = 0) {
      global $DB;

      if ($this->RuleList === null) {
         $this->RuleList = SingletonRuleList::getInstance($this->getRuleClassName(),
                                                          $this->entity);
      }
      $need = 1+($retrieve_criteria?2:0)+($retrieve_action?4:0);

      //Select all the rules of a different type
      $criteria = $this->getRuleListCriteria();
      $iterator = $DB->request($criteria);

      $this->RuleList->list = [];
      foreach ($iterator as $rule) {
         //For each rule, get a Rule object with all the criterias and actions
         $tempRule = $this->getRuleClass();

         if ($tempRule->getRuleWithCriteriasAndActions($rule["id"], $retrieve_criteria,
                                                       $retrieve_action)) {
            //Add the object to the list of rules
            $this->RuleList->list[] = $tempRule;
         }
      }
      $this->RuleList->load = $need;
   }

   function getRuleClassName() {

      if (preg_match('/(.*)Collection/', get_class($this), $rule_class)) {
         if (debug_backtrace()[1]['function'] == 'getRuleListCriteria') {
            $rule_class[1] = str_replace('\\', '\\\\\\', $rule_class[1]);
         }
         return $rule_class[1];
      }
      return "";
   }
}
