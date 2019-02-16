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

class Server extends ControllerParser
{
    public function init()
    {
        $this->addRouteRegexp('/^\/server\/status\/char\/([a-zA-Z0-9]+)$/i', '/server/status/char/{name}');
    }

    /**
     * Status de servidor de personagem solicitado.
     */
    public function status_char_GET($response, $args)
    {
        $name = strtolower($args['name']);

        return $response->withJson(self::fullStatus()->filter(function($v) use ($name) {
            return ($v->status->chars->first(function($char) use ($name) {
                return strtolower($char->name) == $name;
            })) !== null;
        })->map(function($v) use ($name) {
            return $v->status->chars->filter(function($c) use ($name) {
                return strtolower($c->name) == $name;
            })->map(function($char) use ($v) {
                return (object)[
                    'name' => $char->name,
                    'next_check' => $v->next_check,
                    'login' => $v->status->login,
                    'char' => $char->char,
                    'map' => $char->map,
                ];
            })->first();
        })->first());
    }

    /**
     * Status de servidor retorna a rota padrão.
     */
    public function status_GET($response, $args)
    {
        return $this->index_GET($response, $args);
    }

    /**
     * Verifica os dados de status dos servidores caso necessário.
     */
    public function index_GET($response, $args)
    {
        // Responde com o status do servidor...
        return $response->withJson(self::fullStatus());
    }

    /**
     * Obtém os status do servidor para resposta.
     *
     * @return object
     */
    public static function fullStatus()
    {
        // Faz teste de verificação para próxima verificação.
        $nextCheck = time() + $this->getConfig()->server->checkDelay;

        return Model_ServerLogin::all()->each(function($login) use ($nextCheck) {
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
            return (object)[
                'name' => $login->name,
                'next_check' => $login->next_check,
                'account_count' => $login->accounts->count(),
                'status' => (object)[
                    'login' => $login->status,
                    'chars' => $login->charServers->map(function($char) {
                        return (object)[
                            'name' => $char->name,
                            'char' => $char->char_status,
                            'map' => $char->map_status,
                        ];
                    })
                ]
            ];
        });
    }

    /**
     * Verifica se a porta informada está aberta para receber conexões
     * 
     * @param string $address Endereço para conexão
     * @param string $port Porta para conexão
     * 
     * @return boolean Verdadeiro caso esteja aberta
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
