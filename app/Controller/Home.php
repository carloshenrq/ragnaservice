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

/**
 * Controlador para as requisições realizadas ao endereço /home/*
 */
class Home extends ControllerParser
{
    /**
     * @see ControllerParser::init()
     * 
     * @return void
     */
    public function init()
    {
        $this->addRouteRestriction('index_GET', function() {
            return ($this->getApplication()->getPermission() & 1) == 1;
        });
    }

    /**
     * Rota padrão para resposta dos dados.
     * Responde com informações de token padrão para as requisições.
     * 
     * @param object $response Objeto que irá responder com a escrita em tela para o usuário
     * @param array  $args     Parametros que são enviados pelo navegador
     * 
     * @return object
     */
    public function index_GET($response, $args)
    {
        return $response->withJson(Model_Token::defaultActive());
    }
}
