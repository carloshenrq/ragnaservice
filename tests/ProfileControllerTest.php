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

class ProfileControllerTest extends PHPUnit\Framework\TestCase
{
	private $appObj;

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

		$this->appObj = $this->getMockBuilder('App')
								->enableOriginalConstructor()
								->setConstructorArgs([])
								->enableProxyingToOriginalMethods()
								->setMethods(['getException'])
								->getMock();

        $container = $this->appObj->getContainer();
        $container['settings']['displayErrorDetails'] = false;
        $container['settings']['outputBuffering'] = false;
        $container['notFoundHandler'] = function($c) {
            return function($request, $response) {
                return $response->write('Page not found');
            };
        };

        $eloquent = $this->appObj->getEloquent();
        $manager = $eloquent->getManager();
        $schema = $manager->schema('default');

        $this->appObj->installSchemaDefault($schema);
	}

	public function testLoginPost0()
	{
        $container = $this->appObj->getContainer();
		$environment = $container['environment'];

        // Ambiente padrão para execução do URI.
		$environment['REQUEST_METHOD'] = 'POST';
		$environment['REQUEST_URI'] = '/profile/login';
		$environment['CONTENT_TYPE'] = 'multipart/form-data';
		$environment['SERVER_NAME'] = 'app-travis-debug';

		$_POST['email'] = 'a@a.com';
		$_POST['password'] = 'admin';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
		$jsonToken = json_decode($body);

		$this->assertFalse(isset($jsonToken->error));
        $this->assertInstanceOf('stdClass', $jsonToken);
	}

	public function testLoginPost1()
	{
        $container = $this->appObj->getContainer();
		$environment = $container['environment'];

        // Ambiente padrão para execução do URI.
		$environment['REQUEST_METHOD'] = 'POST';
		$environment['REQUEST_URI'] = '/profile/login';
		$environment['CONTENT_TYPE'] = 'multipart/form-data';
		$environment['SERVER_NAME'] = 'app-travis-debug';

		$_POST['email'] = 'a@a.com';
		$_POST['password'] = 'error-password';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
		$jsonToken = json_decode($body);

		$this->assertTrue(isset($jsonToken->error));
        $this->assertInstanceOf('stdClass', $jsonToken);
	}

	public function testLoginPost2()
	{
        $container = $this->appObj->getContainer();
		$environment = $container['environment'];

        // Ambiente padrão para execução do URI.
		$environment['REQUEST_METHOD'] = 'POST';
		$environment['REQUEST_URI'] = '/profile/login';
		$environment['CONTENT_TYPE'] = 'multipart/form-data';
		$environment['SERVER_NAME'] = 'app-travis-debug';

		$_POST['email'] = 'a@a.com';
		$_POST['password'] = 'admin';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
		$jsonToken = json_decode($body);

		$this->assertFalse(isset($jsonToken->error));
		$this->assertInstanceOf('stdClass', $jsonToken);

        // Ambiente padrão para execução do URI.
		$environment['REQUEST_METHOD'] = 'GET';
		$environment['REQUEST_URI'] = '/home/index';
		$environment['SERVER_NAME'] = 'app-travis-debug';
		unset($environment['CONTENT_TYPE']);
		unset($_POST);

		$userToken = $jsonToken->token;
		$environment['HTTP_X_REQUEST_APPTOKEN'] = $userToken;

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
		$jsonToken = json_decode($body);
		$this->assertNotEquals($jsonToken->token, $userToken);
	}

	public function testLoginPost3()
	{
        $container = $this->appObj->getContainer();
		$environment = $container['environment'];

        // Ambiente padrão para execução do URI.
		$environment['REQUEST_METHOD'] = 'POST';
		$environment['REQUEST_URI'] = '/profile/login';
		$environment['CONTENT_TYPE'] = 'multipart/form-data';
		$environment['SERVER_NAME'] = 'app-travis-debug';

		$_POST['email'] = 'a@a.com';
		$_POST['password'] = 'admin';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
		$jsonToken = json_decode($body);

		$this->assertFalse(isset($jsonToken->error));
		$this->assertInstanceOf('stdClass', $jsonToken);

		$this->appObj = $this->getMockBuilder('App')
								->enableOriginalConstructor()
								->setConstructorArgs([])
								->enableProxyingToOriginalMethods()
								->setMethods(['getException'])
								->getMock();

        $container = $this->appObj->getContainer();
        $container['settings']['displayErrorDetails'] = false;
        $container['settings']['outputBuffering'] = false;
        $container['notFoundHandler'] = function($c) {
            return function($request, $response) {
                return $response->write('Page not found');
            };
        };

        $eloquent = $this->appObj->getEloquent();
        $manager = $eloquent->getManager();
        $schema = $manager->schema('default');

		$this->appObj->installSchemaDefault($schema);

        $container = $this->appObj->getContainer();
		$environment = $container['environment'];

        // Ambiente padrão para execução do URI.
		$environment['REQUEST_METHOD'] = 'GET';
		$environment['REQUEST_URI'] = '/home/index';
		$environment['SERVER_NAME'] = 'app-travis-debug';

		$userToken = md5($jsonToken->token);
		$environment['HTTP_X_REQUEST_APPTOKEN'] = $userToken;

		unset($jsonToken);
        $response = $this->appObj->run();

		$body = $this->appObj->getBodyContent();
		$json = json_decode($body);
		
		$this->assertTrue(isset($json->error));
	}

	public function testLoginPost4()
	{
        $container = $this->appObj->getContainer();
		$environment = $container['environment'];

        // Ambiente padrão para execução do URI.
		$environment['REQUEST_METHOD'] = 'POST';
		$environment['REQUEST_URI'] = '/profile/login';
		$environment['CONTENT_TYPE'] = 'multipart/form-data';
		$environment['SERVER_NAME'] = 'app-travis-debug';

		$_POST['email'] = 'a@a.com';
		$_POST['password'] = 'admin';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
		$jsonToken = json_decode($body);

		$this->assertFalse(isset($jsonToken->error));
		$this->assertInstanceOf('stdClass', $jsonToken);

		$this->appObj = $this->getMockBuilder('App')
								->enableOriginalConstructor()
								->setConstructorArgs([])
								->enableProxyingToOriginalMethods()
								->setMethods(['getException'])
								->getMock();

        $container = $this->appObj->getContainer();
        $container['settings']['displayErrorDetails'] = false;
        $container['settings']['outputBuffering'] = false;
        $container['notFoundHandler'] = function($c) {
            return function($request, $response) {
                return $response->write('Page not found');
            };
        };

        $eloquent = $this->appObj->getEloquent();
        $manager = $eloquent->getManager();
        $schema = $manager->schema('default');

		$this->appObj->installSchemaDefault($schema);

        $container = $this->appObj->getContainer();
		$environment = $container['environment'];

        // Ambiente padrão para execução do URI.
		$environment['REQUEST_METHOD'] = 'GET';
		$environment['REQUEST_URI'] = '/home/index';
		$environment['SERVER_NAME'] = 'app-travis-debug';

		$userToken = $jsonToken->token;
		$environment['HTTP_X_REQUEST_APPTOKEN'] = $userToken;

		$objToken = Model\TokenProfile::where([
			'token' => $userToken,
		])->first();

		$profile = $objToken->profile;
		$profile->blocked = true;
		$profile->save();

        $response = $this->appObj->run();

		$body = $this->appObj->getBodyContent();
		$json = json_decode($body);

		$this->assertFalse(isset($json->error));

		$profile->blocked = false;
		$profile->save();
	}

	public function testLoginPost5()
	{
        $container = $this->appObj->getContainer();
		$environment = $container['environment'];

        // Ambiente padrão para execução do URI.
		$environment['REQUEST_METHOD'] = 'POST';
		$environment['REQUEST_URI'] = '/profile/login';
		$environment['CONTENT_TYPE'] = 'multipart/form-data';
		$environment['SERVER_NAME'] = 'app-travis-debug';

		$_POST['email'] = 'a@a.com';
		$_POST['password'] = 'admin';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
		$jsonToken = json_decode($body);

		$this->assertFalse(isset($jsonToken->error));
		$this->assertInstanceOf('stdClass', $jsonToken);

		$this->appObj = $this->getMockBuilder('App')
								->enableOriginalConstructor()
								->setConstructorArgs([])
								->enableProxyingToOriginalMethods()
								->setMethods(['getException'])
								->getMock();

        $container = $this->appObj->getContainer();
        $container['settings']['displayErrorDetails'] = false;
        $container['settings']['outputBuffering'] = false;
        $container['notFoundHandler'] = function($c) {
            return function($request, $response) {
                return $response->write('Page not found');
            };
        };

        $eloquent = $this->appObj->getEloquent();
        $manager = $eloquent->getManager();
        $schema = $manager->schema('default');

		$this->appObj->installSchemaDefault($schema);

        $container = $this->appObj->getContainer();
		$environment = $container['environment'];

        // Ambiente padrão para execução do URI.
		$environment['REQUEST_METHOD'] = 'GET';
		$environment['REQUEST_URI'] = '/home/index';
		$environment['SERVER_NAME'] = 'app-travis-debug';

		$userToken = $jsonToken->token;
		$environment['HTTP_X_REQUEST_APPTOKEN'] = $userToken;

		$objToken = Model\TokenProfile::where([
			'token' => $userToken,
		])->first();

		$objToken->permission -= 1;
		$objToken->save();

        $response = $this->appObj->run();

		$body = $this->appObj->getBodyContent();
		$json = json_decode($body);

		$this->assertTrue(isset($json->error));

		$objToken->permission += 1;
		$objToken->save();
	}
}
