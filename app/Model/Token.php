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
 * Classe para verificação dos tokens no banco de dados
 */
class Token extends Eloquent_Model
{
    /**
     * Obtém o token padrão que está ativo
     * e pode ser usado para requisições normais.
     * 
     * @param object $query
     */
    public function scopeDefaultActive($query)
    {
        $now = new \DateTime();

        return $query->where([
            ['enabled', '=', true],
            ['permission', '=', 1]
        ])->get()
        ->filter(function($t) use ($now) {
            return (is_null($t->use_limit) ||
                $t->use_limit->format('Y-m-d H:i:s') >= $now->format('Y-m-d H:i:s'));
        })->map(function($t) {
            return [
                'token' => $t->token,
                'expires_at' => ((is_null($t->use_limit)) ? null : $t->use_limit->format('Y-m-d H:i:s'))
            ];
        })->first();
    }

    protected $table = 'rs_token';

    protected $fillable = [
        'token', 'permission', 'enabled', 'use_count', 'use_limit'
    ];

    protected $dates = [
        'use_limit', 'created_at', 'updated_at'
    ];

    protected $casts = [
        'permission' => 'integer',
        'enabled' => 'boolean',
        'use_count' => 'integer'
    ];
}
