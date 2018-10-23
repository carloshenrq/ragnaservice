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

use Model\Profile as Model_Profile;
use Model\ProfileVerify as Model_ProfileVerify;
use Model\ProfileReset as Model_ProfileReset;

class ProfileTest extends PHPUnit\Framework\TestCase
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

    public function testProfileSettings0()
    {
        // Dados para validação do perfil
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

        // Código do token que será usado para as próximas requisições...
        $token = $jsonToken->token;

        unset($response, $body, $jsonToken);

        $this->prepareApp();
        $container = $this->appObj->getContainer();

        $environment = $container['environment'];
        $environment['REQUEST_METHOD'] = 'POST';
        $environment['REQUEST_URI'] = '/profile/change/settings';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['HTTP_X_REQUEST_APPTOKEN'] = $token;
        $environment['SERVER_NAME'] = 'app-travis-debug';

        $_POST['name'] = 'Brazil hue test';
        $_POST['gender'] = 'M';
        $_POST['birthdate'] = sprintf('%d-01-01', rand(1800, 2018));

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $jsonToken = json_decode($body);

        $this->assertTrue(isset($jsonToken->success));
        $this->assertTrue($jsonToken->success);
        $this->assertEquals('Informações de Perfil foram atualizadas com sucesso', $jsonToken->message);
    }

    public function testProfileSettings1()
    {
        // Dados para validação do perfil
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

        // Código do token que será usado para as próximas requisições...
        $token = $jsonToken->token;

        unset($response, $body, $jsonToken);

        $this->prepareApp();
        $container = $this->appObj->getContainer();

        $environment = $container['environment'];
        $environment['REQUEST_METHOD'] = 'POST';
        $environment['REQUEST_URI'] = '/profile/change/settings';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['HTTP_X_REQUEST_APPTOKEN'] = $token;
        $environment['SERVER_NAME'] = 'app-travis-debug';

        $_POST['name'] = 'Brazil hue test2';
        $_POST['gender'] = 'F';
        $_POST['birthdate'] = '';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $jsonToken = json_decode($body);

        $this->assertTrue(isset($jsonToken->success));
        $this->assertTrue($jsonToken->success);
        $this->assertEquals('Informações de Perfil foram atualizadas com sucesso', $jsonToken->message);
    }

    public function testProfilePass3()
    {
        // Dados para validação do perfil
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

        // Código do token que será usado para as próximas requisições...
        $token = $jsonToken->token;

        unset($response, $body, $jsonToken);

        $this->prepareApp();
        $container = $this->appObj->getContainer();

		$environment = $container['environment'];
		$environment['REQUEST_METHOD'] = 'POST';
		$environment['REQUEST_URI'] = '/profile/change/password';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['HTTP_X_REQUEST_APPTOKEN'] = $token;
        $environment['SERVER_NAME'] = 'app-travis-debug';

		$_POST['old_pass'] = 'admin';
		$_POST['new_pass'] = 'admin12';
		$_POST['cnf_pass'] = 'admin12';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $jsonToken = json_decode($body);
        
        $this->assertTrue(isset($jsonToken->success));
        $this->assertTrue($jsonToken->success);
        $this->assertEquals('Senha foi alterada com sucesso.', $jsonToken->message);

        $this->prepareApp();
        $container = $this->appObj->getContainer();

		$environment = $container['environment'];
		$environment['REQUEST_METHOD'] = 'POST';
		$environment['REQUEST_URI'] = '/profile/change/password';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['HTTP_X_REQUEST_APPTOKEN'] = $token;
        $environment['SERVER_NAME'] = 'app-travis-debug';

		$_POST['old_pass'] = 'admin12';
		$_POST['new_pass'] = 'admin';
		$_POST['cnf_pass'] = 'admin';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $jsonToken = json_decode($body);
        
        $this->assertTrue(isset($jsonToken->success));
        $this->assertTrue($jsonToken->success);
        $this->assertEquals('Senha foi alterada com sucesso.', $jsonToken->message);
    }

    public function testProfilePass2()
    {
        // Dados para validação do perfil
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

        // Código do token que será usado para as próximas requisições...
        $token = $jsonToken->token;

        unset($response, $body, $jsonToken);

        $this->prepareApp();
        $container = $this->appObj->getContainer();

		$environment = $container['environment'];
		$environment['REQUEST_METHOD'] = 'POST';
		$environment['REQUEST_URI'] = '/profile/change/password';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['HTTP_X_REQUEST_APPTOKEN'] = $token;
        $environment['SERVER_NAME'] = 'app-travis-debug';

		$_POST['old_pass'] = 'admin';
		$_POST['new_pass'] = 'admin';
		$_POST['cnf_pass'] = 'admin';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $jsonToken = json_decode($body);
        
        $this->assertTrue(isset($jsonToken->error));
        $this->assertTrue($jsonToken->error);
        $this->assertEquals('Sua nova senha, não pode ser igual antiga.', $jsonToken->message);
    }

    public function testProfilePass1()
    {
        // Dados para validação do perfil
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

        // Código do token que será usado para as próximas requisições...
        $token = $jsonToken->token;

        unset($response, $body, $jsonToken);

        $this->prepareApp();
        $container = $this->appObj->getContainer();

		$environment = $container['environment'];
		$environment['REQUEST_METHOD'] = 'POST';
		$environment['REQUEST_URI'] = '/profile/change/password';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['HTTP_X_REQUEST_APPTOKEN'] = $token;
        $environment['SERVER_NAME'] = 'app-travis-debug';

		$_POST['old_pass'] = 'admin';
		$_POST['new_pass'] = 'admin12';
		$_POST['cnf_pass'] = 'admin123';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $jsonToken = json_decode($body);
        
        $this->assertTrue(isset($jsonToken->error));
        $this->assertTrue($jsonToken->error);
        $this->assertEquals('As novas senhas digitadas não conferem.', $jsonToken->message);
    }

    public function testProfilePass0()
    {
        // Dados para validação do perfil
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

       // Código do token que será usado para as próximas requisições...
        $token = $jsonToken->token;

        unset($response, $body, $jsonToken);

        $this->prepareApp();
        $container = $this->appObj->getContainer();

		$environment = $container['environment'];
		$environment['REQUEST_METHOD'] = 'POST';
		$environment['REQUEST_URI'] = '/profile/change/password';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['HTTP_X_REQUEST_APPTOKEN'] = $token;
        $environment['SERVER_NAME'] = 'app-travis-debug';

		$_POST['old_pass'] = 'admin12';
		$_POST['new_pass'] = 'admin';
		$_POST['cnf_pass'] = 'admin';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $jsonToken = json_decode($body);
        
        $this->assertTrue(isset($jsonToken->error));
        $this->assertTrue($jsonToken->error);
        $this->assertEquals('A Senha atual informada não confere com a senha atual.', $jsonToken->message);
    }

    public function testProfileVerify()
    {
        $profile = Model_Profile::find(1);
        $verifications = $profile->verifications;

        $verify = $verifications->first(function($v) {
            return $v->id == 1;
        });
        $code = $verify->code;

        // Dados para validação do perfil
        $container = $this->appObj->getContainer();
		$environment = $container['environment'];

        // Ambiente padrão para execução do URI.
		$environment['REQUEST_METHOD'] = 'POST';
		$environment['REQUEST_URI'] = '/profile/verify';
		$environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['SERVER_NAME'] = 'app-travis-debug';

        $_POST['code'] = $code;
        
        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $jsonToken = json_decode($body);

        $this->assertTrue(isset($jsonToken->success));
        $this->assertFalse(isset($jsonToken->error));

        unset($response, $body, $jsonToken, $_POST);
        $this->prepareApp();
        $container = $this->appObj->getContainer();

        $environment = $container['environment'];
        $environment['REQUEST_METHOD'] = 'POST';
        $environment['REQUEST_URI'] = '/profile/login';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['SERVER_NAME'] = 'app-travis-debug';

        $_POST['email'] = 'a@a.com';
        $_POST['password'] = 'admin';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $json = json_decode($body);

        $token = $json->token;

        unset($response, $body, $json, $_POST);
        $this->prepareApp();
        $container = $this->appObj->getContainer();

        $environment = $container['environment'];
        $environment['REQUEST_METHOD'] = 'POST';
        $environment['REQUEST_URI'] = '/profile/verify/resend';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['SERVER_NAME'] = 'app-travis-debug';
        $environment['HTTP_X_REQUEST_APPTOKEN'] = $token;

        $_POST = [];

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $json = json_decode($body);

        $this->assertTrue(isset($json->error));
        $this->assertFalse(isset($json->success));
        $this->assertEquals('Este perfil já está verificado.', $json->message);
    }

    public function testProfileVerifyError()
    {
        // Dados para validação do perfil
        $container = $this->appObj->getContainer();
        $environment = $container['environment'];

        // Ambiente padrão para execução do URI.
        $environment['REQUEST_METHOD'] = 'POST';
        $environment['REQUEST_URI'] = '/profile/verify';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['SERVER_NAME'] = 'app-travis-debug';

        $_POST['code'] = 'not-found-code';
        
        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $jsonToken = json_decode($body);
        
        $this->assertFalse(isset($jsonToken->success));
        $this->assertTrue(isset($jsonToken->error));
    }

    public function testProfileCreateError()
    {
        // Dados para validação do perfil
        $container = $this->appObj->getContainer();
        $environment = $container['environment'];

        // Ambiente padrão para execução do URI.
        $environment['REQUEST_METHOD'] = 'POST';
        $environment['REQUEST_URI'] = '/profile/create';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['SERVER_NAME'] = 'app-travis-debug';

        $_POST['name'] = 'Administrador';
        $_POST['gender'] = 'O';
        $_POST['birthdate'] = (new DateTime())->format('Y-m-d');
        $_POST['email'] = 'a@a.com';
        $_POST['password'] = 'admin';
        
        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $jsonToken = json_decode($body);
        
        $this->assertFalse(isset($jsonToken->success));
        $this->assertTrue(isset($jsonToken->error));
    }

    public function testProfileCreateSuccess()
    {
        // Dados para validação do perfil
        $container = $this->appObj->getContainer();
        $environment = $container['environment'];

        // Ambiente padrão para execução do URI.
        $environment['REQUEST_METHOD'] = 'POST';
        $environment['REQUEST_URI'] = '/profile/create';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['SERVER_NAME'] = 'app-travis-debug';

        $_POST['name'] = 'Administrador';
        $_POST['gender'] = 'O';
        $_POST['birthdate'] = (new DateTime())->format('Y-m-d');
        $_POST['email'] = 'b@b.com';
        $_POST['password'] = 'admin';
        
        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $jsonToken = json_decode($body);
        
        $this->assertTrue(isset($jsonToken->success));
        $this->assertFalse(isset($jsonToken->error));

        unset($response, $body, $jsonToken, $_POST);
        sleep(1);
        $this->prepareApp();
        $container = $this->appObj->getContainer();

        $environment = $container['environment'];
        $environment['REQUEST_METHOD'] = 'POST';
        $environment['REQUEST_URI'] = '/profile/login';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['SERVER_NAME'] = 'app-travis-debug';

        $_POST['email'] = 'b@b.com';
        $_POST['password'] = 'admin';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $json = json_decode($body);

        $this->assertTrue(isset($json->token));
        $token = $json->token;

        unset($response, $body, $json, $_POST);

        $this->prepareApp();
        $container = $this->appObj->getContainer();

        $environment = $container['environment'];
        $environment['REQUEST_METHOD'] = 'POST';
        $environment['REQUEST_URI'] = '/profile/verify/resend';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['SERVER_NAME'] = 'app-travis-debug';

        $environment['HTTP_X_REQUEST_APPTOKEN'] = $token;

        $_POST = [];

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $json = json_decode($body);
        
        $this->assertTrue(isset($json->success));
        $this->assertFalse(isset($json->error));
        $this->assertTrue($json->success);
        $this->assertEquals('E-mail de confirmação foi enviado para o e-mail do perfil', $json->message);

        // Apaga todas as verificações para o perfil...
        Model_Profile::where([
            'email' => 'b@b.com'
        ])->first()->verifications->each(function($verify) {
            $verify->delete();
        });

        unset($response, $body, $json, $_POST);
        $this->prepareApp();
        $container = $this->appObj->getContainer();

        $environment = $container['environment'];
        $environment['REQUEST_METHOD'] = 'POST';
        $environment['REQUEST_URI'] = '/profile/verify/resend';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['SERVER_NAME'] = 'app-travis-debug';
        $environment['HTTP_X_REQUEST_APPTOKEN'] = $token;

        $_POST = [];

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $json = json_decode($body);
        
        $this->assertTrue(isset($json->success));
        $this->assertFalse(isset($json->error));
        $this->assertTrue($json->success);
        $this->assertEquals('E-mail de confirmação foi enviado para o e-mail do perfil', $json->message);
    }

    public function testProfileResetSuccess()
    {
        // Dados para validação do perfil
        $container = $this->appObj->getContainer();
        $environment = $container['environment'];

        // Ambiente padrão para execução do URI.
        $environment['REQUEST_METHOD'] = 'POST';
        $environment['REQUEST_URI'] = '/profile/create';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['SERVER_NAME'] = 'app-travis-debug';

        $_POST['name'] = 'Administrador';
        $_POST['gender'] = 'F';
        $_POST['birthdate'] = (new DateTime())->format('Y-m-d');
        $_POST['email'] = 'c@c.com';
        $_POST['password'] = 'admin';
        
        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $jsonToken = json_decode($body);
        
        $this->assertTrue(isset($jsonToken->success));
        $this->assertFalse(isset($jsonToken->error));
        unset($response, $body, $jsonToken, $_POST);

        $this->prepareApp();
        $container = $this->appObj->getContainer();

        $environment = $container['environment'];
        $environment['REQUEST_METHOD'] = 'POST';
        $environment['REQUEST_URI'] = '/profile/reset';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['SERVER_NAME'] = 'app-travis-debug';

        $_POST['email'] = 'c@c.com';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $json = json_decode($body);

        $this->assertTrue(isset($json->success));
        $this->assertFalse(isset($json->error));
        $this->assertEquals('Reset de senha foi enviado para o e-mail do perfil', $json->message);
        unset($response, $body, $json, $_POST);

        $this->prepareApp();
        $container = $this->appObj->getContainer();

        $environment = $container['environment'];
        $environment['REQUEST_METHOD'] = 'POST';
        $environment['REQUEST_URI'] = '/profile/reset';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['SERVER_NAME'] = 'app-travis-debug';

        $_POST['email'] = 'c@c.com';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $json = json_decode($body);

        $this->assertTrue(isset($json->success));
        $this->assertFalse(isset($json->error));
        $this->assertEquals('Reset de senha foi enviado para o e-mail do perfil', $json->message);

        // Obtém o código correto no banco de dados para fazer
        // a validação e troca de senha...
        $profile = Model_Profile::where([
        	['email', '=', 'c@c.com']
        ])->first();
        $reset = $profile->resets->first();
        $code = $reset->code;

        unset($response, $body, $json, $_POST);

        $this->prepareApp();
        $container = $this->appObj->getContainer();

        $environment = $container['environment'];
        $environment['REQUEST_METHOD'] = 'POST';
        $environment['REQUEST_URI'] = '/profile/reset/confirm';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['SERVER_NAME'] = 'app-travis-debug';

        $_POST['code'] = $code;

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $json = json_decode($body);
        
        $this->assertTrue(isset($json->success));
        $this->assertFalse(isset($json->error));
        $this->assertEquals('Senha resetada com sucesso. Verifique seu e-mail.', $json->message);
    }

    /**
     * Dados de reset inválidos...
     */
    public function testProfileResetError()
    {
        // Dados para validação do perfil
        $container = $this->appObj->getContainer();
        $environment = $container['environment'];
        $environment['REQUEST_METHOD'] = 'POST';
        $environment['REQUEST_URI'] = '/profile/reset';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['SERVER_NAME'] = 'app-travis-debug';

        $_POST['email'] = 'x@x.com';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $json = json_decode($body);

        $this->assertTrue(isset($json->error));
        $this->assertFalse(isset($json->success));
        $this->assertEquals('Endereço de e-mail não pertence a nenhum perfil.', $json->message);
	}

    /**
     * Dados de reset inválidos...
     */
    public function testProfileResetConfirmError()
    {
        // Dados para validação do perfil
        $container = $this->appObj->getContainer();
        $environment = $container['environment'];
        $environment['REQUEST_METHOD'] = 'POST';
        $environment['REQUEST_URI'] = '/profile/reset/confirm';
        $environment['CONTENT_TYPE'] = 'multipart/form-data';
        $environment['SERVER_NAME'] = 'app-travis-debug';

        $_POST['code'] = 'this-is-not-a-code';

        $response = $this->appObj->run();

        $body = $this->appObj->getBodyContent();
        $json = json_decode($body);

        $this->assertTrue(isset($json->error));
        $this->assertFalse(isset($json->success));
        $this->assertEquals('Código de reset de senha não é valido ou já foi usado.', $json->message);
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
