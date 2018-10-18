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

class App extends CHZApp\Application
{
    /**
     * Verifica se a aplicação está em modo de instalação
     * @var boolean
     */
    private $installMode;

    public function init()
    {
        // Define a aplicação em modo de instalação.
        $this->setInstallMode(true);

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

            // Identifica que não está em modo de instalação
            $this->setInstallMode(false);

            // Define os dados de conexão com o BD.
            $this->setEloquentConfigs($config->connections);

            // Obtém informações do manager.
            $manager = $this->getEloquent()->getManager();
            $pdo = $manager->getConnection('default')->getPdo();
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
        
        if (!$schema->hasTable('rs_token')) {
            $schema->create('rs_token', function($table) {
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
}
