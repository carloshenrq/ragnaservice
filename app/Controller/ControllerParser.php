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

use \CHZApp\Controller as CHZApp_Controller;
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;

use \Model\TokenProfile as Model_TokenProfile;
use \App as Application;

abstract class ControllerParser extends CHZApp_Controller
{
    /**
     * @see \App::getConfig()
     */
    public function getConfig()
    {
        return $this->getApplication()->getConfig();
    }

    public function __router(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        try
        {
            // Verifica se a requisição foi realizada utilizando um token
            // de acesso.
            $tokenInfo = $request->getHeaderLine('HTTP_X_REQUEST_APPTOKEN');
            
            if (!empty($tokenInfo)) {
                // Obtém o token que o usuário está usando em sua conexão...
                $token = Model_TokenProfile::where([
                    ['token', '=', $tokenInfo],
                    ['expires_at', '>=', (new \DateTime())->format('Y-m-d H:i:s')]
                ])->first();

                // Caso não exista o token, será retornado uma exception.
                if ($token === null)
                    throw new \Exception(__t('Token informado não é válido para esta requisição.'));

                // Caso o token não esteja autorizado a fazer o login
                // então exclui e manda a mensagem
                if  (!($token->permission&1)) {
                    $token->delete();
                    throw new \Exception(__t('O Token informado não está autorizado.'));
                }

                if ($token->mainToken->enabled) {
                    // Atualiza o tempo de expire do token para
                    // atual + 10 minutos
                    $token->expires_at = (new \DateTime())->add(date_interval_create_from_date_string('10 minutes'));
                    $token->save();

                    // Obtém o perfil que está vinculado a este token
                    // para poder definir na aplicação...
                    $this->getApplication()->setProfile($token->profile);
                }
            }
        }
        catch(\Exception $ex)
        {
            return $response->withJson([
                'error' => true,
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);
        }

        return parent::__router($request, $response, $args);
    }

    /**
     * Envia o e-mail com as informações passadas.
     */
    public static function sendMail($subject, $to, $template, $data = array(), $type = 'text/html', $attach = array())
    {
        $app = Application::getInstance();
        $app->getMailer()->sendFromTemplate($subject, $to, $template, array_merge($data, [
            'config' => $app->getConfig()
        ]), $type, $attach);

        //@codingStandardsIgnoreStart
        if (getenv('TRAVIS_CI_DEBUG') !== false && getenv('TRAVIS_CI_DEBUG') == 1)
            sleep(1);
        //@codingStandardsIgnoreEnd
    }

}
