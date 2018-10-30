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

use \Model\ServerChar as Model_ServerChar;

class ServerTest extends PHPUnit\Framework\TestCase
{
    public function setUp()
    {
		parent::setUp();
		// Registra o autoload para o site.
		Autoload::register();

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

		copy($configFileFrom, $configFile);

        $this->prepareApp();
    }

    /**
     * Faz o teste de servidor de personagens...
     */
    public function testStatusChar()
    {
        $char = Model_ServerChar::all()->first();

        $this->assertNotNull($char);
        $this->assertInstanceOf('Model\ServerChar', $char);
        $this->assertNotNull($char->loginServer);
        $this->assertEquals(1, $char->loginServer->id);
    }

    public function testStatusCharGet()
    {
        $container = $this->appObj->getContainer();
        $environment = $container['environment'];
        // Ambiente padrão para execução do URI.
        $environment['REQUEST_METHOD'] = 'GET';
        $environment['REQUEST_URI'] = '/server/status/char/Ragnarok';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $json = json_decode($body);

        $this->assertEquals('Ragnarok', $json->name);
    }

    public function testStatusGet()
    {
        $container = $this->appObj->getContainer();
        $environment = $container['environment'];
        // Ambiente padrão para execução do URI.
        $environment['REQUEST_METHOD'] = 'GET';
        $environment['REQUEST_URI'] = '/server/status';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $json = json_decode($body);

        $this->assertTrue(is_array($json));

        $server = $json[0];
        $this->assertEquals('RagnaService', $server->name);
    }

    /**
     * Prepara o App para os testes necessários de envio dos pacotes e etc...
     */
    private function prepareApp()
    {
        $this->appObj = $this->getMockBuilder('App')
                            ->enableOriginalConstructor()
                            ->setConstructorArgs([])
                            ->enableProxyingToOriginalMethods()
                            ->setMethods(['getException'])
                            ->getMock();

        $eloquent = $this->appObj->getEloquent();
        $manager = $eloquent->getManager();
        $schema = $manager->schema('default');
        $this->appObj->installSchemaDefault($schema);
    }
}
