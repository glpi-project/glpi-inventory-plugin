<?php

/**
 * ---------------------------------------------------------------------
 * GLPI Inventory Plugin
 * Copyright (C) 2021 Teclib' and contributors.
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

namespace GlpiPlugin\Glpiinventory\Controller;

use Glpi\Controller\AbstractController;
use Glpi\Error\ErrorHandler;
use Html;
use PluginGlpiinventoryCommunicationRest;
use Safe\Exceptions\InfoException;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function Safe\file_get_contents;
use function Safe\ini_set;
use function Safe\json_encode;
use function Safe\session_id;
use function Safe\session_start;

class InventoryController extends AbstractController
{
    public function __construct(private readonly UrlGeneratorInterface $router)
    {
        //empty constructor
    }

    #[Route("/", name: "glpiinventory_main", methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        global $CFG_GLPI;
        //Agent asking for orders using REST
        $action = $request->query->get('action');
        $machineid = $request->query->get('machineid');
        if (
            !empty($action)
            && !empty($machineid)
        ) {
            return $this->communication($request, $action, $machineid);
        }

        //Agent posting an inventory
        $rawdata = file_get_contents("php://input");
        if (!empty($rawdata)) {
            return (new \Glpi\Controller\InventoryController($this->router))->index($request);
        }

        //For any other request, display the menu
        Html::header(
            __('GLPI Inventory', 'glpiinventory'),
            $_SERVER['PHP_SELF'],
            "plugins",
            "glpiinventory"
        );
        Html::redirect($CFG_GLPI['root_doc'] . "/plugins/glpiinventory/front/menu.php");
    }

    #[Route("/Communication", name: "glpiinventory_communication", methods: ['GET', 'POST'])]
    #[Route("/front/communication.php", name: "glpiinventory_communication_legacy", methods: ['GET', 'POST'])]
    #[Route("/front/plugin_fusioninventory.communication.php", name: "glpiinventory_communication_fusion_legacy", methods: ['GET', 'POST'])]
    public function communication(Request $request, ?string $action = null, ?string $machine_id = null): Response
    {
        try {
            ini_set("memory_limit", "-1");
            ini_set("max_execution_time", "0");
            ini_set('display_errors', 1);
        } catch (InfoException $e) {
            //empty catch -- but keep trace of issue
            ErrorHandler::logCaughtException($e);
        }

        $headers = ['server-type' => 'glpi/glpiinventory ' . PLUGIN_GLPIINVENTORY_VERSION];

        if ($action === null) {
            $action = $request->query->get('action');
        }
        if ($machine_id === null) {
            $machine_id = $request->query->get('machineid');
        }

        if (empty($action) || empty($machine_id)) {
            return new Response(null, 400, $headers);
        }

        if (session_id() == "") {
            session_start();
        }

        $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;
        if (!isset($_SESSION['glpilanguage'])) {
            $_SESSION['glpilanguage'] = 'en_GB';
        }
        $_SESSION['glpi_glpiinventory_nolock'] = true;
        $_SESSION['glpi_use_mode'] = 0;
        $_SESSION['glpiparententities'] = '';
        $_SESSION['glpishowallentities'] = true;

        $contents = PluginGlpiinventoryCommunicationRest::communicate($request->query->all());
        if ($contents) {
            return new Response(json_encode($contents), 200, $headers);
        }

        return new Response(null, 400, $headers);
    }
}
