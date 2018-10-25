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

class ProfileAccount extends Eloquent_Model
{
    public function profile()
    {
        return $this->belongsTo('\Model\Profile', 'profile_id', 'id');
    }

    public function loginServer()
    {
        return $this->belongsTo('\Model\ServerLogin', 'login_id', 'id');
    }

    protected $table = 'profile_account';
    protected $connection = 'default';

    protected $fillable = [
        'login_id', 'profile_id', 'account_id', 'userid', 'group_id', 'state'
    ];

    protected $dates = [
        'created_at', 'updated_at'
    ];

    protected $casts = [
        'login_id' => 'integer',
        'profile_id' => 'integer',
        'account_id' => 'integer',
        'group_id' => 'integer',
        'state' => 'integer',
    ];
}

