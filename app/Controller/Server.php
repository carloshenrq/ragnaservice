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

use \Model\ServerLogin as Model_ServerLogin;
use \Model\ServerChar as Model_ServerChar;

/**
 * Controlador de requisições referentes a dados
 * do servidores
 */
class Server extends ControllerParser
{
    /**
     * @see ControllerParser::init()
     * 
     * @return void
     */
    public function init()
    {
        $this->addRouteRegexp('/^\/server\/status\/char\/([a-zA-Z0-9]+)$/i', '/server/status/char/{name}');
    }

    /**
     * Status de servidor de personagem solicitado.
     * 
     * @param object $response Objeto de resposta da requisição
     * @param array  $args     Parametros para a requisição
     * 
     * @return object
     */
    public function status_char_GET($response, $args)
    {
        // Dados de retorno
        $data = [
            'name' => $args['name'],
            'login' => false,
            'char' => false,
            'map' => false
        ];

        $charServer = Model_ServerChar::where([
            'name' => $data['name']
        ])->first();


        if ($charServer !== null) {
            $data = array_merge($data, [
                'login' => $charServer->loginServer->status,
                'char' => $charServer->char_status,
                'map' => $charServer->map_status,
            ]);
        }

        return $response->withJson($data);
    }

    /**
     * Status de servidor retorna a rota padrão.
     * 
     * @param object $response Objeto de resposta da requisição
     * @param array  $args     Parametros para a requisição
     * 
     * @return object
     */
    public function status_GET($response, $args)
    {
        return $this->index_GET($response, $args);
    }

    /**
     * Verifica os dados de status dos servidores caso necessário.
     * 
     * @param object $response Objeto de resposta da requisição
     * @param array  $args     Parametros para a requisição
     * 
     * @return object
     */
    public function index_GET($response, $args)
    {
        // Faz teste de verificação para próxima verificação.
        $nextCheck = time() + $this->getConfig()->server->checkDelay;

        // Obtém os status dos servidores para fazer a exibição dos status.
        $status = Model_ServerLogin::all()->each(function($login) use ($nextCheck) {
            // Tenta efetuar o ping na porta de login...
            $errno = $errstr = '';

            if ($login->next_check < time()) {
                $login->status = self::isPortOpen($login->address, $login->port);
                $login->next_check = $nextCheck;
                $login->save();
            }

            // Varre os char-severs para verificar o status de conexão
            $login->charServers->each(function($char) use ($nextCheck) {
                $errno = $errstr = '';
                if ($char->next_check < time()) {
                    $char->char_status = self::isPortOpen($char->char_address, $char->char_port);
                    $char->map_status = self::isPortOpen($char->map_address, $char->map_port);
                    $char->next_check = $nextCheck;
                    $char->save();
                }
            });

        })->map(function($login) {
            $login->refresh();
            return [
                'name' => $login->name,
                'next_check' => $login->next_check,
                'status' => [
                    'login' => $login->status,
                    'chars' => $login->charServers->map(function($char) {
                        return [
                            'name' => $char->name,
                            'char' => $char->char_status,
                            'map' => $char->map_status,
                        ];
                    })
                ]
            ];
        });

        // Responde com o status do servidor...
        return $response->withJson($status);
    }

    /**
     * Verifica se a porta informada está aberta para receber conexões.
     * 
     * @param string $address Endereço para conexão
     * @param string $port    Porta para conexão
     * 
     * @return boolean
     */
    public static function isPortOpen($address, $port)
    {
        $bIsPortOpen = false;

        //@codingStandardsIgnoreStart
        $errno = $errstr = '';
        $ptrFile = @fsockopen($address, $port, $errno, $errstr, 10);
        $bIsPortOpen = is_resource($ptrFile);
        if ($bIsPortOpen) fclose($ptrFile);
        unset($errno);
        unset($errstr);
        //@codingStandardsIgnoreEnd

        return $bIsPortOpen;
    }
}
