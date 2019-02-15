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
		// Faz teste de verificação para próxima verificação.
		$next_check = time() + $this->getConfig()->server->checkDelay;

		// Obtém os status dos servidores para fazer a exibição dos status.
		$status = Model_ServerLogin::all()->each(function($login) use ($next_check) {
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
}
