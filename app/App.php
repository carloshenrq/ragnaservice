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
use Model\ServerLogin as Model_ServerLogin;
use Model\ServerChar as Model_ServerChar;

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

    /**
     * Define o idioma de uso
     * @var string
     */
    private $lang;

    /**
     * Define informações de tradução
     * @var boolean
     */
    private $langLoaded;

    /**
     * Dados de tradução para o painel de controle.
     * @var array
     */
    private $langTranslate;

    public function init()
    {
        // Define a aplicação em modo de instalação.
        $this->setInstallMode(true);
        $this->profile = null;

        // Arquivo de configuração
        $configFile = join(DIRECTORY_SEPARATOR, [
            __DIR__,
            '..',
            'config.json'
        ]);

        // Inicializa informações do slim com opções padrões de diretório...
        $this->setSmartyConfigs([
            'templateDir'   => join(DIRECTORY_SEPARATOR, [__DIR__, 'View']),
        ]);


        // Se conseguiu fazer a leitura dos dados
        // então estará tudo OK para não permitir
        // o módo de instalação.
        //@codingStandardsIgnoreStart
        if (file_exists($configFile)) {
            $configContent = file_get_contents($configFile);
            //@codingStandardsIgnoreEnd
            $config = json_decode($configContent);

            if ($config === null)
                throw new Exception(__t('Falha na leitura do arquivo de configuração.'));

            // Define as configurações da aplicação.
            $this->setConfig($config);

            // Identifica que não está em modo de instalação
            $this->setInstallMode(false);

            // Define informações de envio de email...
            if (isset($config->mailer) && $config->mailer !== null)
                $this->setMailerConfigs((array)$config->mailer);

            // Define os dados de conexão com o BD.
            $this->setEloquentConfigs($config->connections);
            $this->registerModels();
            $this->loadLanguage();
            $this->parseServers();
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
        if ($this->isInstallMode())
            return;
        
        $profileCreated = $schema->hasTable('profile');

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

        if (!$profileCreated) {
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
                $table->string('language', 30)->nullable();
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

        if (!$schema->hasTable('server_login')) {
            $schema->create('server_login', function($table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->string('name', 30)->nullable();
                $table->string('address', 20)->default('127.0.0.1');
                $table->integer('port')->default(6900);
                $table->boolean('status')->default(false);
                $table->integer('next_check')->unsigned();
                $table->timestamps();

                $table->unique(['address', 'port']);
            });
        }

        if (!$schema->hasTable('server_char')) {
            $schema->create('server_char', function($table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->integer('login_id')->unsigned();
                $table->string('name', 30);
                $table->string('char_address', 20)->default('127.0.0.1');
                $table->integer('char_port')->default(6121);
                $table->boolean('char_status')->default(false);
                $table->string('map_address', 20)->default('127.0.0.1');
                $table->integer('map_port')->default(5121);
                $table->boolean('map_status')->default(false);
                $table->integer('next_check')->unsigned();
                $table->timestamps();

                $table->unique(['login_id', 'name']);
                $table->foreign('login_id')->references('id')->on('server_login');
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
                'ga_secret' => null,
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
    public function isInstallMode()
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

        if (empty($this->profile->language))
            $this->profile->update([
                'language' => $this->getConfig()->language,
            ]);

        $this->setLang($this->profile->language);
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
        if ($profile !== null)
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
        $this->setLang($this->config->language);
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
     * Define a linguagem de uso do sistema.
     *
     * @param string $lang
     */
    public function setLang($lang)
    {
        $this->langLoaded = ($this->lang === $lang);
        $this->lang = $lang;
    }

    /**
     * Obtem a linguagem de uso do sistema.
     *
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
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

        foreach($dirModel as $fileModel) {
            if ($fileModel->isDot() || $fileModel->isDir())
                continue;

            // Retira a extensão '.php' do final do arquivo
            // e atribui ao vetor de models...
            $modelClass = '\\Model\\' . substr($fileModel->getFilename(), 0, -4);

            //@codingStandardsIgnoreStart
            call_user_func([$modelClass, 'flushEventListeners']);
            call_user_func([$modelClass, 'boot']);
            //@codingStandardsIgnoreEnd
        }
    }

    /**
     * Realiza o tratamento de gravar os dados de servidor direto no banco de dados.
     */
    public function parseServers()
    {
        foreach ($this->getConfig()->server->login as $login) {
            $l = Model_ServerLogin::where([
                'address' => $login->address,
                'port' => $login->port,
            ])->first();

            if ($l === null) {
                // Cria a entrada do servidor...
                $l = Model_ServerLogin::create([
                    'name' => $login->name,
                    'address' => $login->address,
                    'port' => $login->port,
                    'status' => false,
                    'next_check' => 0
                ]);
            }

            foreach ($login->charServer as $char) {
                $c = $l->charServers->first(function($charServer) use ($char) {
                    return $charServer->name == $char->name;
                });

                if ($c === null) {
                    Model_ServerChar::create([
                        'login_id' => $l->id,
                        'name' => $char->name,
                        'char_address' => $char->charAddress,
                        'char_port' => $char->charPort,
                        'char_status' => false,
                        'map_address' => $char->mapAddress,
                        'map_port' => $char->mapPort,
                        'map_status' => false,
                        'next_check' => 0
                    ]);
                }
            }
        }
    }

    /**
     * Carrega o arquivo de traduções para o sistema
     */
    public function loadLanguage()
    {
        if ($this->langLoaded)
            return;

        // Zera informações de tradução ao carregar o método. Just-in-case
        $this->langTranslate = [];

        // Arquivo de linguagem solicitado...
        $langFile = join(DIRECTORY_SEPARATOR, [
            __DIR__, '..', 'lang', $this->getLang() . '.php'
        ]);
        $langCacheFile = join(DIRECTORY_SEPARATOR, [
            __DIR__, '..', 'lang', $this->getLang() . '.cache.php'
        ]);

        // Se não existir o arquivo de linguagem, então
        // será usado o idioma padrão.
        //@codingStandardsIgnoreStart
        if (file_exists($langFile)) {
            // Caso exista informações de linguagem e não exista o arquivo
            // de cache, irá criar o arquivo de cache
            if (!file_exists($langCacheFile)) {
                $aTmpLanguage = [];
                $langTranslate = include($langFile);
                //@codingStandardsIgnoreEnd
                foreach ($langTranslate as $orig => $trans) {
                    $aTmpLanguage[$this->getHash($orig)] = $trans;
                }
                $aTmpLanguage[$this->getHash('__hash')] = hash_file('sha512', $langFile);
                // Arrays com chaves indexadas com inteiro
                // em ordem, são mais rápidas para acessar.
                /*
                If you use only integers as array keys in PHP 7 and make sure that you insert them into the array in ascending order, you can see improvements of 10x faster array operations.
                => https://blog.blackfire.io/php-7-performance-improvements-packed-arrays.html
                */
                ksort($aTmpLanguage);
                //@codingStandardsIgnoreStart
                file_put_contents($langCacheFile, serialize($aTmpLanguage));
                //@codingStandardsIgnoreEnd
                $this->loadLanguage();
                return;
            }

            //@codingStandardsIgnoreStart
            $cacheLang = unserialize(file_get_contents($langCacheFile));
            //@codingStandardsIgnoreEnd

            if ($cacheLang[$this->getHash('__hash')] !== hash_file('sha512', $langFile)) {
                //@codingStandardsIgnoreStart
                unlink($langCacheFile);
                //@codingStandardsIgnoreEnd
                $this->loadLanguage();
                return;
            }

            // Carrega os dados de linguagem informados.
            $this->langTranslate = $cacheLang;
        }

        $this->langLoaded = true;
    }

    /**
     * Obtém uma tradução para a mensagem enviada e para o arquivo informado.
     *
     * @param string $message
     *
     * @return string Mensagem traduzida...
     */
    public function getTranslate($message)
    {
        $mhash = $this->getHash($message);

        if (isset($this->langTranslate[$mhash]))
            return $this->langTranslate[$mhash];

        return $message;
    }

    /** 
     * Obtém o hashing para uma string informada.
     * 
     * @param string $str String a ser usada para o hashing
     *
     * @return integer Inteiro do hashing
     */
    public function getHash($str)
    {
        $hashCount = 0;
        $size = strlen($str);

        for($i = 0; $i < $size; $i++) {
            $hashCount += ord($str[$i]);
            $hashCount += $hashCount << 10;
            $hashCount ^= $hashCount >> 6;
        }

        $hashCount += $hashCount << 3;
        $hashCount ^= $hashCount >> 11;
        $hashCount += $hashCount << 15;

        return $hashCount;
    }
}
