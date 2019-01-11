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

require_once 'app/autoload.php';

use \Model\Profile as Model_Profile;
use \Model\ProfileAccount as Model_ProfileAccount;

class AppTest extends PHPUnit\Framework\TestCase
{
	public function setUp()
	{
		// Registra o autoload para o site.
		Autoload::register();
	}

	public function testAppInstance2()
	{
		$configFile = join(DIRECTORY_SEPARATOR, [
			__DIR__,
			'..',
			'config.json'
		]);

		if (file_exists($configFile))
			unlink($configFile);

		$configFileFrom = realpath(join(DIRECTORY_SEPARATOR, [
			__DIR__,
			'..',
			'build',
			'config.json'
		]));

		$copyResult = copy($configFileFrom, $configFile);
		$this->assertTrue($copyResult);

		$app = $this->getMockBuilder('App')
					->enableOriginalConstructor()
					->setConstructorArgs([])
					->enableProxyingToOriginalMethods()
					->setMethods(['getException'])
					->getMock();

		$langEx = join(DIRECTORY_SEPARATOR, [
			__DIR__,
			'..',
			'lang',
			'example.php'
		]);
		$langEn = join(DIRECTORY_SEPARATOR, [
			__DIR__,
			'..',
			'lang',
			'en_US.php'
		]);

		// copia o arquivo de linguagem...
		$this->assertTrue(copy($langEx, $langEn));

		// Arquivo de linguagem em inglês existe!
		$this->assertTrue(file_exists($langEn));

		// Define a linguagem como a estrangeira.
		$app->setLang('en_US');
		$app->loadLanguage();

		// Verifica informações da linguagem carregada
		$this->assertEquals($app->getLang(), 'en_US');
		$this->assertEquals($app->getTranslate('Falha na leitura do arquivo de configuração.'), '');

		// Retorna ao pt_BR
		$app->setLang('pt_BR');
		$app->loadLanguage();

		// Verifica se o texto não está sendo traduzido.
		$this->assertEquals($app->getLang(), 'pt_BR');
		$this->assertEquals($app->getTranslate('Falha na leitura do arquivo de configuração.'), 'Falha na leitura do arquivo de configuração.');
		
		// Altera o arquivo para saber se o cache está igual.
		// Se não estiver, irá atualizar o teste e refazer o arquivo
		file_put_contents($langEn, ' ', FILE_APPEND);

		// Define a linguagem como a estrangeira.
		$app->setLang('en_US');
		$app->loadLanguage();

		// Verifica informações da linguagem carregada
		$this->assertEquals($app->getLang(), 'en_US');
		$this->assertEquals($app->getTranslate('Falha na leitura do arquivo de configuração.'), '');

		// Retorna ao pt_BR
		$app->setLang('pt_BR');
		$app->loadLanguage();

		// Verifica se o texto não está sendo traduzido.
		$this->assertEquals($app->getLang(), 'pt_BR');
		$this->assertEquals($app->getTranslate('Falha na leitura do arquivo de configuração.'), 'Falha na leitura do arquivo de configuração.');

		/** TESTES DE MÉTODOS DE OBTER A CONEXÃO REFERENTE AO LOGIN/CHAR QUE IRÃO SE CONECTAR **/
		$loginServer = $app->getFirstLoginServer();
		$this->assertEquals('ragnaservice', $loginServer);

		$loginConnection = $app->getFirstLoginConnection();
		$this->assertEquals('login-ragnaservice', $loginConnection);

		$loginIdNull = $app->getLoginId('null-login');
		$this->assertNull($loginIdNull);

		$loginId = $app->getLoginId($loginServer);
		$this->assertEquals(1, $loginId);

		$loginMd5Null = $app->getLoginMd5('null-login');
		$this->assertNull($loginMd5Null);

		$loginMd5 = $app->getLoginMd5($loginServer);
		$this->assertFalse($loginMd5);

		$charNull = $app->getAllCharServersFromLogin('null-login');
		$this->assertNull($charNull);

		$charServers = $app->getAllCharServersFromLogin($loginServer);
		$this->assertEquals(1, count($charServers));

		$charConnNull0 = $app->getCharServerConnection('null-login', 'null-char');
		$this->assertNull($charConnNull0);

		$charConnNull1 = $app->getCharServerConnection($loginServer, 'null-char');
		$this->assertNull($charConnNull1);

		$charConn = $app->getCharServerConnection($loginServer, 'ragnarok');
		$this->assertEquals('char-ragnaservice-ragnarok', $charConn);

		/** Testes para vinculo entre contas do jogo e de perfil **/
		$account = Model_ProfileAccount::create([
			'login_id' => 1,
			'profile_id' => 1,
			'account_id' => 2000000,
			'userid' => 'admin',
			'group_id' => 99,
			'state' => 0
		]);

		$this->assertNotNull($account->loginServer);
		$this->assertEquals('RagnaService', $account->loginServer->name);
		$this->assertNotNull($account->profile);
		$this->assertEquals(1, $account->profile->id);

		$profile = Model_Profile::find(1);
		$this->assertEquals(1, $profile->accounts->count());
	}

	/**
	 * @expectedException Exception
	 */
	public function testAppInstance1()
	{
		$configFile = realpath(join(DIRECTORY_SEPARATOR, [
			__DIR__,
			'..',
			'build',
			'config-error.json'
		]));

		$configCopy = join(DIRECTORY_SEPARATOR, [
			__DIR__,
			'..',
			'config.json'
		]);

		$copyResult = copy($configFile, $configCopy);
		$this->assertTrue($copyResult);

		$app = $this->getMockBuilder('App')
					->enableOriginalConstructor()
					->setConstructorArgs([])
					->enableProxyingToOriginalMethods()
					->setMethods(['getException'])
					->getMock();
	}

	public function testAppInstance0()
	{
		$configFile = realpath(join(DIRECTORY_SEPARATOR, [
			__DIR__,
			'..',
			'config.json'
		]));

		if ($configFile !== false)
			unlink($configFile);

		$app = $this->getMockBuilder('App')
					->enableOriginalConstructor()
					->setConstructorArgs([])
					->enableProxyingToOriginalMethods()
					->setMethods(['getException'])
					->getMock();

		$this->assertInstanceOf('CHZApp\Application', $app);

		$app->installSchemaDefault(null);
	}
}
