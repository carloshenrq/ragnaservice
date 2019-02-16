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
use \Model\Observer\ProfileObserver as Observer_Profile;

class Profile extends Eloquent_Model
{
    public function token()
    {
        return $this->hasOne('\Model\TokenProfile');
    }

    public function verifications()
    {
        return $this->hasMany('\Model\ProfileVerify', 'profile_id', 'id');
    }

    public function resets()
    {
        return $this->hasMany('\Model\ProfileReset', 'profile_id', 'id');
    }

    public function accounts()
    {
        return $this->hasMany('\Model\ProfileAccount', 'login_id', 'id');
    }

    /**
     * Faz a mudança de servidores para o usuário.
     *
     * @param string $loginServer
     * @param string $charServer
     */
    public function changeServer($loginServer = null, $charServer = null)
    {
        $app = \App::getInstance();

        if (!is_null($loginServer) && is_null($app->getLoginConnection($loginServer)))
            return false;

        // Grava o login-server do usuário.
        $this->loginServer = $loginServer;
        $this->save();

        if (!is_null($charServer) && is_null($app->getCharServerConnection($loginServer, $charServer)))
            return false;

        $this->charServer = $charServer;
        $this->save();

        return true;
    }

    /**
     * Faz alterações de informações padrões do perfil
     * do usuários
     */
    public function changeSettings($name, $gender, $birthdate)
    {
        $this->name = $name;
        $this->gender = $gender;
        $this->birthdate = $birthdate;
        $this->save();
    }

    /**
     * Define a nova senha para o profile.
     * 
     * @param string $newPassword Nova senha
     */
    public function changePassword($newPassword)
    {
        $this->password = $newPassword;
        $this->save();
    }

    /**
     * Define a senha como sendo sha512 para o profile...
     * @param string $value
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = hash('sha512', $value);
    }

    /**
     * Verifica os dados de login que foram informados...
     * 
     * @param string $email
     * @param string $password
     * 
     * @return mixed Retornará false caso não seja encontrado o login.
     */
    public function scopeLogin($query, $email, $password)
    {
        // Verifica o profile no banco de dados...
        $profile = $query->where([
            ['email', '=', $email],
            ['password', '=', hash('sha512', $password)]
        ])->get()->first();

        // Se o cadastro não existir ou estiver bloqueado
        // o login de usuário é negado!
        // Caso contrario, um novo token é criado e o usuário pode fazer login corretamente
        if ($profile === null || $profile->blocked)
            return false;

        // Deleta todos os tokens existentes para o usuário
        // logo após o login...
        TokenProfile::where([
            'profile_id' => $profile->id
        ])->delete();
        
        $token = Token::defaultActive();

        // Cria um novo token para o perfil informado...
        $tokenProfile = TokenProfile::create([
            'token_id' => $token->id,
            'profile_id' => $profile->id,
            'token' => hash('sha512', uniqid() . microtime(true)),
            'permission' => $profile->permission,
            'expires_at' => (new \DateTime())->add(date_interval_create_from_date_string('10 minutes')),
        ]);

        // Retorna os dados de token para o perfil.
        return $tokenProfile;
    }

    protected $table = 'profile';
    protected $connection = 'default';

    protected $fillable = [
        'name', 'gender', 'birthdate', 'email', 'password',
        'permission', 'blocked', 'blocked_reason', 'blocked_until',
        'verified', 'register_date', 'facebook_id', 'ga_enabled', 'ga_secret'
    ];

    protected $dates = [
        'birthdate', 'blocked_until', 'register_date',
        'created_at', 'updated_at'
    ];

    protected $casts = [
        'blocked' => 'boolean',
        'verified' => 'boolean',
        'ga_enabled' => 'boolean'
    ];

    /**
     * @see Eloquent_Model::boot()
     */
    public static function boot()
    {
        parent::boot();
        self::observe(new Observer_Profile());
    }
}
