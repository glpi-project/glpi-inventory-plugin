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

use Glpi\Controller\GenericFormController;
use Glpi\Routing\Attribute\ItemtypeFormRoute;
use PluginGlpiinventoryCollect;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CollectController extends GenericFormController
{
    #[ItemtypeFormRoute(PluginGlpiinventoryCollect::class)]
    public function __invoke(Request $request): Response
    {
        $request->attributes->set('class', PluginGlpiinventoryCollect::class);
        return parent::__invoke($request);
    }

    #[Route("/front/collect_file.form.php", name: "glpiinventory_collect_file_legacy", methods: ['POST'])]
    #[ItemtypeFormRoute(\PluginGlpiinventoryCollect_File::class)]
    public function collectFile(Request $request): Response
    {
        $request->attributes->set('class', \PluginGlpiinventoryCollect_File::class);
        return parent::__invoke($request);
        //return $this->collectObject($request, 'file');
    }

    #[Route("/front/collect_registry.form.php", name: "glpiinventory_collect_file_legacy", methods: ['POST'])]
    public function collectRegistry(Request $request): Response
    {
        $request->attributes->set('class', \PluginGlpiinventoryCollect_Registry::class);
        return parent::__invoke($request);
        //return $this->collectObject($request, 'registry');
    }

    #[Route("/front/collect_wmi.form.php", name: "glpiinventory_collect_file_legacy", methods: ['POST'])]
    public function collectWMI(Request $request): Response
    {
        $request->attributes->set('class', \PluginGlpiinventoryCollect_Wmi::class);
        return parent::__invoke($request);
        //return $this->collectObject($request, 'wmi');
    }

    /*#[Route("/Collect/{collect_object}", name: "glpiinventory_collect_object", methods: ['POST'])]
    public function collectObject(Request $request, string $collect_object): Response
    {
        $request->attributes->set('class', $collect_object);
        return parent::__invoke($request);
    }*/
}
