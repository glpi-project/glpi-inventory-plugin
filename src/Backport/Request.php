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

declare(strict_types=1);

namespace GlpiPlugin\Glpiinventory\Backport;

use Config;
use Glpi\Error\ErrorHandler;
use Glpi\Exception\OAuth2KeyException;
use Glpi\Inventory\Conf;
use Glpi\Inventory\Request as GlpiRequest;
use Glpi\Http\Request as GlpiHttpRequest;
use Glpi\OAuth\Server;
use GLPIKey;
use League\OAuth2\Server\Exception\OAuthServerException;

class Request extends GlpiRequest
{
    /**
     * Authenticate request if required by configuration
     * Verbatim copy of \Glpi\Agent\Communication\AbstractRequest::authenticateRequest() add in GLPI 11.0.8)
     *
     * TODO: remove v12
     */
    public function authenticateRequest(): bool
    {
        // Delegate to core as soon as it provides the method (GLPI >= 11.0.8)
        if (method_exists(get_parent_class($this), 'authenticateRequest')) {
            /** @phpstan-ignore-next-line (method only exists on GLPI >= 11.0.8) */
            return parent::authenticateRequest();
        }

        $auth_required = false;
        if (!$this->isLocal()) {
            $auth_required = Config::getConfigurationValue('inventory', 'auth_required');
        }
        if ($auth_required === Conf::CLIENT_CREDENTIALS) {
            $request = new GlpiHttpRequest('POST', $_SERVER['REQUEST_URI'], $this->headers->getHeaders());
            try {
                $client = Server::validateAccessToken($request);
                if (!in_array('inventory', $client['scopes'], true)) {
                    $this->addError('Access denied. Agent must authenticate using client credentials and have the "inventory" OAuth scope', 401);
                    return false;
                }
            } catch (OAuth2KeyException $e) {
                ErrorHandler::logCaughtException($e);
                $this->addError($e->getMessage());
                return false;
            } catch (OAuthServerException) {
                $this->addError('Authorization header required to send an inventory', 401);
                return false;
            }
        }

        if ($auth_required === Conf::BASIC_AUTH) {
            $authorization_header = $this->headers->getHeader('Authorization');
            if (is_null($authorization_header)) {
                $this->headers->setHeader("www-authenticate", 'Basic realm="basic"');
                $this->addError('Authorization header required to send an inventory', 401);
                return false;
            } else {
                $allowed = false;
                // if Authorization start with 'Basic'
                $matches = [];
                if (preg_match('/^Basic\s+(.*)$/i', $authorization_header, $matches)) {
                    $agent_credentials = explode(':', base64_decode($matches[1]), 2);
                    if (
                        count($agent_credentials) !== 2
                        || $agent_credentials[0] === ''
                        || $agent_credentials[1] === ''
                    ) {
                        // Login and/or password is missing or empty
                        $allowed = false;
                    } else {
                        $expected_login = Config::getConfigurationValue('inventory', 'basic_auth_login');
                        $expected_password = (new GLPIKey())
                            ->decrypt(Config::getConfigurationValue('inventory', 'basic_auth_password'));

                        $allowed = $agent_credentials[0] === $expected_login
                            && $agent_credentials[1] === $expected_password;
                    }
                }
                if (!$allowed) {
                    $this->headers->setHeader("www-authenticate", 'Basic realm="basic"');
                    $this->addError('Access denied. Wrong login or password for basic authentication.', 401);
                    return false;
                }
            }
        }

        return true;
    }
}
