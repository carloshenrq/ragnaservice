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
use \Model\TokenProfile as Model_TokenProfile;
use \Model\Profile as Model_Profile;

class Profile extends ControllerParser
{
    /**
     * Realiza o login do usuário de acordo com os dados e senha informados.
     * 
     * @param object $response
     * @param array $args
     * 
     * @return object
     */
    public function login_POST($response, $args)
    {
        $email = $this->post['email'];
        $password = $this->post['password'];

        $token = Model_Profile::login($email, $password);

        if (!($token instanceof Model_TokenProfile))
            return $response->withJson([
                'error' => true,
                'message' => _("Nome de usuário e senha inválidos ou cadastro bloqueado.")
            ]);

        return $response->withJson([
            'token' => $token->token,
            'expires_at' => $token->expires_at->format('Y-m-d H:i:s')
        ]);
    }
}
