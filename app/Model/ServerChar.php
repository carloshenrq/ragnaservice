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

namespace Model;

use \Illuminate\Database\Eloquent\Model as Eloquent_Model;

/**
 * Modelo para obter os dados do servidor de personagens do banco de dados.
 */
class ServerChar extends Eloquent_Model
{
    /**
     * Obtém o login-server relacionado a este char-server.
     * 
     * @return \Model\ServerLogin
     */
    public function loginServer()
    {
        return $this->belongsTo('\Model\ServerLogin', 'login_id', 'id');
    }

    protected $table = 'server_char';
    protected $connection = 'default';

    protected $fillable = [
        'login_id', 'name', 'char_address', 'char_port', 'char_status',
        'map_address', 'map_port', 'map_status', 'next_check'
    ];

    protected $dates = [
        'created_at', 'updated_at'
    ];

    protected $casts = [
        'login_id' => 'integer',
        'char_port' => 'integer',
        'char_status' => 'boolean',
        'map_port' => 'integer',
        'map_status' => 'boolean',
        'next_check' => 'integer'
    ];
}
