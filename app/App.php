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

use Model\Token as Model_Token;
use Model\Profile as Model_Profile;

class App extends CHZApp\Application
{
    /**
     * Verifica se a aplicação está em modo de instalação
     * @var boolean
     */
    private $installMode;

    /**
     * Perfil que está logado na aplicação.
     * @var Model_Profile
     */
    private $profile;

    /**
     * Dados de configuração da aplicação.
     * @var stdObject
     */
    private $config;

    public function init()
    {
        // Define a aplicação em modo de instalação.
        $this->setInstallMode(true);
        $this->profile = null;

        // Arquivo de configuração
        $configFile = realpath(join(DIRECTORY_SEPARATOR, [
            __DIR__,
            '..',
            'config.json'
        ]));

        // Se conseguiu fazer a leitura dos dados
        // então estará tudo OK para não permitir
        // o módo de instalação.
        if ($configFile !== false && file_exists($configFile)) {
            $configContent = file_get_contents($configFile);
            $config = json_decode($configContent);

            if (is_null($config))
                throw new Exception(_("Falha na leitura do arquivo de configuração."));

            // Define as configurações da aplicação.
            $this->setConfig($config);

            // Identifica que não está em modo de instalação
            $this->setInstallMode(false);

            // Define os dados de conexão com o BD.
            $this->setEloquentConfigs($config->connections);
            $this->registerModels();
        }
    }

    /**
     * Faz a instalação do schema padrão de conexão.
     * 
     * @param object $schema
     */
    public function installSchemaDefault($schema)
    {
        // Não realiza a instalação se estiver em modo install...
        if ($this->getInstallMode())
            return;
        
        $profileCreated = true;

        if (!$schema->hasTable('token')) {
            $schema->create('token', function($table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->string('token', 128)->unique();
                $table->integer('permission')->unsigned();
                $table->boolean('enabled')->default(false);
                $table->integer('use_count')->unsigned()->default(0);
                $table->dateTime('use_limit')->nullable();
                $table->timestamps();
           });

            // Cria o primeiro token de acesso ao sistema.
            Model_Token::create([
                'token' => hash('sha512', uniqid() . microtime(true)),
                'permission' => 1,
                'enabled' => true,
                'use_count' => 0,
                'use_limit' => null
            ]);
        }

        if (!($profileCreated = $schema->hasTable('profile'))) {
            $schema->create('profile', function($table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->string('name', 256)->default('');
                $table->enum('gender', ['M', 'F', 'O'])->nullable()->default('M');
                $table->date('birthdate')->nullable();
                $table->string('email', 60)->unique()->nullable();
                $table->string('password', 128)->nullable();
                $table->integer('permission')->unsigned()->default(0);
                $table->boolean('blocked')->default(false);
                $table->string('blocked_reason', 2048)->nullable();
                $table->dateTime('blocked_until')->nullable();
                $table->boolean('verified')->default(false);
                $table->date('register_date');
                $table->string('facebook_id', 30)->unique()->nullable();
                $table->boolean('ga_enabled')->default(false);
                $table->string('ga_secret', 16)->nullable();
                $table->timestamps();

                $table->index(['email', 'password']);
            });
        }

        if (!$schema->hasTable('token_profile')) {
            $schema->create('token_profile', function($table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->integer('token_id')->unsigned();
                $table->integer('profile_id')->unsigned();
                $table->string('token', 128)->unique();
                $table->integer('permission')->unsigned()->default(0);
                $table->dateTime('expires_at');
                $table->timestamps();

                $table->foreign('token_id')->references('id')->on('token');
                $table->foreign('profile_id')->references('id')->on('profile');
            });
        }

        if (!$schema->hasTable('profile_verify')) {
            $schema->create('profile_verify', function($table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->integer('profile_id')->unsigned();
                $table->string('code', 32)->unique();
                $table->boolean('used')->default(false);
                $table->dateTime('used_at')->nullable();
                $table->dateTime('expires_at');
                $table->timestamps();

                $table->foreign('profile_id')->references('id')->on('profile');
            });
        }

        if (!$schema->hasTable('profile_reset')) {
            $schema->create('profile_reset', function($table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->integer('profile_id')->unsigned();
                $table->string('code', 32)->unique();
                $table->boolean('used')->default(false);
                $table->dateTime('used_at')->nullable();
                $table->dateTime('expires_at');
                $table->timestamps();

                $table->foreign('profile_id')->references('id')->on('profile');
            });
        }

        if (!$profileCreated) {
            Model_Profile::create([
                'name' => 'Administrador',
                'gender' => 'O',
                'birthdate' => null,
                'email' => 'a@a.com',
                'password' => hash('sha512', 'admin'),
                'permission' => 3,
                'blocked' => false,
                'blocked_reason' => null,
                'blocked_until' => null,
                'verified' => true,
                'register_date' => new DateTime(),
                'facebook_id' => null,
                'ga_enabled' => false,
                'ga_secret' => null
            ]);
        }
    }

    /**
     * Define o modo de instalação para o service.
     * 
     * @param boolean $installMode
     */
    public function setInstallMode($installMode)
    {
        $this->installMode = $installMode;
    }

    /**
     * Verifica se o modo de instalação está definido na aplicação.
     * 
     * @return boolean
     */
    public function getInstallMode()
    {
        return $this->installMode;
    }

    /**
     * Define o perfil que está logado na aplicação para
     * esta sessão.
     * 
     * @param Model_Profile $profile
     */
    public function setProfile(Model_Profile $profile)
    {
        $this->profile = $profile;
    }

    /**
     * Obtém o perfil que está logado na aplicação para esta sessão.
     * 
     * @return Model_Profile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * Obtém a permissão para o token que está sendo usado.
     * Caso o usuário não esteja logado, será usado o padrão.
     * 
     * @return int
     */
    public function getPermission()
    {
        $profile = $this->getProfile();

        // Informação de token para o usuário logado.
        if (!is_null($profile))
            return $profile->token->permission;
        
        // Dados do token por ID
        $token = Model_Token::find(Model_Token::defaultActive()->id);

        // Permissão do token para acesso de rota.
        return $token->permission;
    }

    /**
     * Define as configurações para a aplicação.
     * 
     * @param object $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * Obtém as configurações de aplicação.
     * 
     * @return object
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Método garante a chamada de todos os models a serem registrados...
     */
    private function registerModels()
    {
        $modelDir = join(DIRECTORY_SEPARATOR, [
            __DIR__, 'Model'
        ]);
        $dirModel = new \DirectoryIterator($modelDir);
        $modelClasses = [];

        foreach($dirModel as $fileModel) {
            if ($fileModel->isDot() || $fileModel->isDir())
                continue;

            // Retira a extensão '.php' do final do arquivo
            // e atribui ao vetor de models...
            $modelClass = '\\Model\\' . substr($fileModel->getFilename(), 0, -4);
            call_user_func([$modelClass, 'flushEventListeners']);
            call_user_func([$modelClass, 'boot']);
        }
    }
}
