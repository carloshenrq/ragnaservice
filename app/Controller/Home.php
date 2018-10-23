<?php
/**
 *  RagnaService, RESTful api for ragnarok emulators
 *  Copyright (C) 2018 carloshernq
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Controller;

use \Model\Token as Model_Token;

class Home extends ControllerParser
{
    public function init()
    {
        $this->addRouteRestriction('index_GET', function() {
            return ($this->getApplication()->getPermission()&1) == 1;
        });
    }

    public function index_GET($response, $args)
    {
        return $response->withJson(Model_Token::defaultActive());
    }
}