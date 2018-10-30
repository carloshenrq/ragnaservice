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
            return $v->status->chars->count(function($char) use ($name) {
                return $char->name == $name;
            });
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
        $next_check = time() + \App::getInstance()->getConfig()->server->checkDelay;

        return Model_ServerLogin::all()->each(function($login) use ($next_check) {
            // Tenta efetuar o ping na porta de login...
            $errno = $errstr = '';

            if ($login->next_check < time()) {
                $status = @fsockopen($login->address, $login->port, $errno, $errstr, 5);
                $login->status = is_resource($status);
                $login->next_check = $next_check;
                $login->save();
                if ($status) fclose($status);
            }

            // Varre os char-severs para verificar o status de conexão
            $login->charServers->each(function($char) use ($next_check) {
                $errno = $errstr = '';
                if ($char->next_check < time()) {
                    $c_status = @fsockopen($char->char_address, $char->char_port, $errno, $errstr, 5);
                    $m_status = @fsockopen($char->map_address, $char->map_port, $errno, $errstr, 5);
                    $char->char_status = is_resource($c_status);
                    $char->map_status = is_resource($m_status);
                    $char->next_check = $next_check;
                    $char->save();
                    if ($c_status) fclose($c_status);
                    if ($m_status) fclose($m_status);
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
}
