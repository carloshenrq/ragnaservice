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

/**
 * Controlador abstrato herdado pelos demais controladores
 * da aplicação. Tem como função garantir os métodos
 * básicos e essenciais que a aplicação mais usa.
 */
abstract class ControllerParser extends CHZApp_Controller
{
    /**
     * @see \App::getConfig()
     * 
     * @return object
     */
    public function getConfig()
    {
        return $this->getApplication()->getConfig();
    }

    /**
     * Método que realiza o roteamento da requisição e tratamento
     * do token para os métodos
     * 
     * @param \Psr\Http\Message\ServerRequestInterface $request  Objeto de requisição
     * @param \Psr\Http\Message\ResponseInterface      $response Objeto de resposta
     * @param array                                    $args     Argumentos da requisição
     * 
     * @return object
     */
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
                if  (!($token->permission & 1)) {
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
     * Envia um e-mail usando o arquivo template que é compilado pelo
     * Smarty e envia para o endereço informado.
     * 
     * @param string $subject   Assunto do e-mail
     * @param array  $toAddress Endereço que o e-mail será enviado.
     * @param string $template  Nome do arquivo em '/app/View/' que será compilado
     * @param array  $data      Dados associativos que serão usados para a compilação.
     * @param string $type      Tipo do corpo de e-mail
     * @param array  $attach    Anexos que serão enviados por e-mail
     * 
     * @return void
     */
    public static function sendMail($subject, $toAddress, $template, $data = array(), $type = 'text/html', $attach = array())
    {
        $app = Application::getInstance();
        $app->getMailer()->sendFromTemplate($subject, $toAddress, $template, array_merge($data, [
            'config' => $app->getConfig()
        ]), $type, $attach);

        //@codingStandardsIgnoreStart
        if (getenv('TRAVIS_CI_DEBUG') !== false && getenv('TRAVIS_CI_DEBUG') == 1)
            sleep(1);
        //@codingStandardsIgnoreEnd
    }

}
