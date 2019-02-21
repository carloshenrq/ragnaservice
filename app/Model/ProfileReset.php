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
use \Model\Observer\ProfileResetObserver as Observer_ProfileReset;

/**
 * Modelo para obter os dados referentes ao reset de perfil
 */
class ProfileReset extends Eloquent_Model
{
    /**
     * Obtém o perfil do objeto de reset
     * 
     * @return \Model\Profile
     */
    public function profile()
    {
        return $this->belongsTo('\Model\Profile', 'profile_id', 'id');
    }

    protected $table = 'profile_reset';
    protected $connection = 'default';

    protected $fillable = [
        'profile_id', 'code', 'used', 'used_at', 'expires_at'
    ];

    protected $dates = [
        'used_at', 'expires_at', 'created_at', 'updated_at'
    ];

    protected $casts = [
        'profile_id' => 'integer',
        'used' => 'boolean'
    ];

    /**
     * @see Eloquent_Model::boot()
     * 
     * @return void
     */
    public static function boot()
    {
        parent::boot();
        self::observe(new Observer_ProfileReset());
    }
}
