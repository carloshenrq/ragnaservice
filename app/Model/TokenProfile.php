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
 * Modelo para os tokens de perfil.
 * São referencias as sessões dos usuários
 */
class TokenProfile extends Eloquent_Model
{
    /**
     * Obtém o token pai para o token de perfil.
     * 
     * @return \Model\Token
     */
    public function mainToken()
    {
        return $this->belongsTo('\Model\Token', 'token_id', 'id');
    }

    /**
     * Obtem o perfil que está vinculado a este token.
     * 
     * @return \Model\Profile
     */
    public function profile()
    {
        return $this->belongsTo('\Model\Profile', 'profile_id', 'id');
    }

    protected $table = 'token_profile';
    protected $connection = 'default';

    protected $fillable = [
        'token_id', 'profile_id', 'token', 'permission', 'expires_at'
    ];

    protected $dates = [
        'expires_at', 'created_at', 'updated_at'
    ];

    protected $casts = [
        'token_id' => 'integer',
        'profile_id' => 'integer',
        'permission' => 'integer',
    ];
}
