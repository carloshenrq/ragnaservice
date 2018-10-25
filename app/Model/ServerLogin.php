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

class ServerLogin extends Eloquent_Model
{
    /**
     * Obtém todos os char-servers referentes ao login
     */
    public function charServers()
    {
        return $this->hasMany('\Model\ServerChar', 'login_id', 'id');
    }

    public function accounts()
    {
        return $this->hasMany('\Model\ProfileAccount', 'login_id', 'id');
    }

    protected $table = 'server_login';
    protected $connection = 'default';

    protected $fillable = [
        'name', 'address', 'port', 'status', 'next_check'
    ];

    protected $dates = [
        'created_at', 'updated_at'
    ];

    protected $casts = [
        'port' => 'integer',
        'status' => 'boolean',
        'next_check' => 'integer'
    ];
}
