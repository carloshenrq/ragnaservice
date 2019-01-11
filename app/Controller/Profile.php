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

namespace Controller;

use \Model\Token as Model_Token;
use \Model\TokenProfile as Model_TokenProfile;
use \Model\Profile as Model_Profile;
use \Model\ProfileVerify as Model_ProfileVerify;
use \Model\ProfileReset as Model_ProfileReset;

class Profile extends ControllerParser
{
    public function init()
    {
        // Rota de verificação depende da configuração.
        $this->addRouteRestriction('verify_POST', function() {
            return ($this->getConfig()->profile->verification == true);
        });

        // Adicionado restrição para as rotas de não ser necessário realização de login.
        $this->applyRestrictionOnAllRoutes(function() {
            return (($this->getApplication()->getPermission()&2) == 0);
        }, ['verify_POST']);

        // Aplica em todas as outras rotas, necessidade para
        // ser realizado o login.
        $this->applyRestrictionOnAllRoutes(function() {
            return (($this->getApplication()->getPermission()&2) != 0);
        }, ['verify_POST', 'create_POST', 'login_POST', 'reset_POST', 'reset_confirm_POST']);
    }

    /**
     * Informações para troca de dados de servidor padrão do perfil
     */
    public function change_server_POST($response, $args)
    {
        $loginServer = $charServer = null;

        if (!empty($this->post['loginServer'])) $loginServer = $this->post['loginServer'];
        if (!empty($this->post['charServer'])) $charServer = $this->post['charServer'];

        // Informações de perfil para servidor
        if (!$this->getApplication()->getProfile()->changeServer($loginServer, $charServer))
            return $response->withJson([
                'success' => false,
                'message' => __t('Verifique os servidores informados e tente novamente.')
            ]);

        // Retorna informações de update para a tela.
        return $response->withJson([
            'success' => true,
            'message' => __t('Informação de servidores alteradas com sucesso')
        ]);
    }

    /**
     * Faz a chamada para alterações de informações do perfil.
     */
    public function change_settings_POST($response, $args)
    {
        // Configurações que serão alteradas
        $name = $this->post['name'];
        $gender = $this->post['gender'];
        $birthdate = $this->post['birthdate'];

        if (empty($birthdate))
            $birthdate = null;

        // Grava as alterações no perfil do usuário
        $this->getApplication()->getProfile()->changeSettings($name, $gender, $birthdate);

        // Retorna informações de update para a tela.
        return $response->withJson([
            'success' => true,
            'message' => __t('Informações de Perfil foram atualizadas com sucesso')
        ]);
    }

    /**
     * Faz a alteração de senha para o perfil que está fazendo
     * a chamada.
     */
    public function change_password_POST($response, $args)
    {
        $old_pass = $this->post['old_pass'];
        $new_pass = $this->post['new_pass'];
        $cnf_pass = $this->post['cnf_pass'];

        $profile = $this->getApplication()->getProfile();

        if ($profile->password !== hash('sha512', $old_pass))
            return $response->withJson([
                'error' => true,
                'message' => __t('A Senha atual informada não confere com a senha atual.')
            ]);
        
        if (hash('sha512', $new_pass) !== hash('sha512', $cnf_pass))
            return $response->withJson([
                'error' => true,
                'message' => __t('As novas senhas digitadas não conferem.')
            ]);
        
        if (hash('sha512', $old_pass) === hash('sha512', $new_pass))
            return $response->withJson([
                'error' => true,
                'message' => __t('Sua nova senha, não pode ser igual antiga.')
            ]);
    
        $profile->changePassword($new_pass);

        return $response->withJson([
            'success' => true,
            'message' => __t('Senha foi alterada com sucesso.')
        ]);
    }

    /**
     * Realiza a criação de uma nova conta.
     */
    public function create_POST($response, $args)
    {
        // Dados e informações que vieram por post para criação da conta.
        $name = $this->post['name'];
        $gender = $this->post['gender'];
        $birthdate = $this->post['birthdate'];
        $email = $this->post['email'];
        $password = $this->post['password'];
        $loginServer = $this->getApplication()->getFirstLoginServer();

        // Verifica a existencia do e-mail informado...
        // Caso já exista, não irá permitir criar novamente...
        $existing = Model_Profile::where([
            ['email', '=', $email]
        ])->get()->count();

        if ($existing)
            return $response->withJson([
                'error' => true,
                'message' => __t('O Endereço de e-mail informado já foi utilizado.')
            ]);

        // Tentativa de criação da conta.
        $profile = Model_Profile::create([
            'name' => $name,
            'gender' => $gender,
            'birthdate' => new \DateTime($birthdate),
            'email' => $email,
            'password' => hash('sha512', $password),
            'permission' => $this->getConfig()->profile->permission,
            'blocked' => false,
            'blocked_reason' => null,
            'blocked_until' => null,
            'verified' => true,
            'register_date' => new \DateTime(),
            'facebook_id' => null,
            'ga_enabled' => false,
            'ga_secret' => null,
            'loginServer' => $loginServer,
            'charServer' => null
        ])->refresh();

        // Retorna informações para a tela...
        return $response->withJson([
            'success' => true,
            'verified' => $profile->verified,
            'message' => __t('Perfil criado com sucesso.'),
        ]);
    }

    /**
     * Realiza a verificação de um login que não está verificado.
     * 
     * @param object $response
     * @param array $args
     * 
     * @return object
     */
    public function verify_POST($response, $args)
    {
        $code = $this->post['code'];

        // Obtém o código de verificação
        // informado pelo usuário para saber se está tudo de acordo e o perfil
        // pode ser verificado com sucesso.
        $verify = Model_ProfileVerify::where([
            ['code', '=', $code],
            ['used', '=', false],
            ['expires_at', '>=', (new \DateTime())->format('Y-m-d H:i:s')]
        ])->first();

        if (is_null($verify))
            return $response->withJson([
                'error' => true,
                'message' => __t('O Código de verificação informado já foi usado ou não existe.'),
            ]);

        $verify->used = true;
        $verify->used_at = new \DateTime();
        $verify->save();

        return $response->withJson([
            'success' => true,
            'message' => __t('Seu perfil foi verificado com sucesso.')
        ]);
    }

    /**
     * Faz o pedido de reenvio das validações para o usuário.
     */
    public function verify_resend_POST($response, $args)
    {
        // Obtém o perfil logado.
        $profile = $this->getApplication()->getProfile();

        // Caso já esteja verificado, não necessita enviar novamente.
        if ($profile->verified)
            return $response->withJson([
                'error' => true,
                'message' => __t('Este perfil já está verificado.')
            ]);

        // Informações sobre o perfil verificado...
        $verify = $profile->verifications->first(function($v) {
            return !$v->used &&
                    $v->expires_at->format('Y-m-d') >= (new \DateTime())->format('Y-m-d');
        });

        if (is_null($verify)) {
            $expires_after = sprintf('%d minutes', $this->getConfig()->profile->expires_after);
            Model_ProfileVerify::create([
                'profile_id' => $profile->id,
                'code' => hash('md5', uniqid().microtime(true)),
                'used' => false,
                'used_at' => null,
                'expires_at' => (new \DateTime())->add(date_interval_create_from_date_string($expires_after))
            ]);
        } else {
            self::sendVerifyCode($verify);
        }

        return $response->withJson([
            'success' => true,
            'message' => __t('E-mail de confirmação foi enviado para o e-mail do perfil')
        ]);
    }

    /**
     * Responde a confirmação de reset do usuário.
     */
    public function reset_confirm_POST($response, $args)
    {
        $code = $this->post['code'];

        $reset = Model_ProfileReset::where([
            ['code', '=', $code]
        ])->first();

        if (is_null($reset))
            return $response->withJson([
                'error' => true,
                'message' => __t('Código de reset de senha não é valido ou já foi usado.')
            ]);

        $reset->used = true;
        $reset->used_at = new \DateTime();
        $reset->save();

        return $response->withJson([
            'success' => true,
            'message' => __t('Senha resetada com sucesso. Verifique seu e-mail.')
        ]);
    }

    /**
     * Faz o pedido de reset 
     */
    public function reset_POST($response, $args)
    {
        // Email para enviar uma possível recuperação de senha.
        $email = $this->post['email'];

        // Obtém o perfil para ser verificado...
        $profile = Model_Profile::where([
            ['email', '=', $email]
        ])->first();

        if (is_null($profile))
            return $response->withJson([
                'error' => true,
                'message' => __t('Endereço de e-mail não pertence a nenhum perfil.'),
            ]);

        // Obtém o reset que está em memória para enviar o proprio pedido novamente...
        $reset = $profile->resets->first(function($r) {
            return (!$r->used && $r->expires_at->format('Y-m-d H:i:s') >= (new \DateTime())->format('Y-m-d H:i:s'));
        });

        // Se não houver resets em aberto, então irá criar um novo
        if (is_null($reset)) {
            $reset = Model_ProfileReset::create([
                'profile_id' => $profile->id,
                'code' => hash('md5', uniqid().microtime(true)),
                'used' => false,
                'used_at' => null,
                'expires_at' => (new \DateTime())->add(date_interval_create_from_date_string(sprintf('%d minutes',
                                    $this->getConfig()->profile->expires_after
                                )))
            ]);
        }

        // Faz o envio do pedido de reset
        self::sendResetCode($reset);        

        return $response->withJson([
            'success' => true,
            'message' => __t('Reset de senha foi enviado para o e-mail do perfil')
        ]);
    }

    /**
     * Realiza o login do usuário de acordo com os dados e senha informados.
     * 
     * @param object $response
     * @param array $args
     * 
     * @return object
     */
    public function login_POST($response, $args)
    {
        $email = $this->post['email'];
        $password = $this->post['password'];

        $token = Model_Profile::login($email, $password);

        if (!($token instanceof Model_TokenProfile))
            return $response->withJson([
                'error' => true,
                'message' => __t('Nome de usuário e senha inválidos ou cadastro bloqueado.')
            ]);

        return $response->withJson([
            'token' => $token->token,
            'expires_at' => $token->expires_at->format('Y-m-d H:i:s'),
            'profile' => [
                'name' => $token->profile->name,
                'gender' => $token->profile->gender,
                'birthdate' => ((is_null($token->profile->birthdate)) ? null : $token->profile->birthdate->format('Y-m-d'))
            ]
        ]);
    }

    /**
     * Envia o e-mail contendo a nova senha do usuário
     */
    public static function sendResetPass(Model_ProfileReset $reset, $password)
    {
        // Faz o envio do e-mail
        ControllerParser::sendMail(
            __t('Senha resetada com sucesso'),
            [$reset->profile->email => $reset->profile->name],
            'mail-reset-pass.html',
            [
                'profile' => $reset->profile,
                'new_password' => $password,
                'now' => new \DateTime(),
            ]);
    }

    /**
     * Envia o e-mail de reset referente ao perfil solicitado.
     */
    public static function sendResetCode(Model_ProfileReset $reset)
    {
        // Faz o envio do e-mail
        ControllerParser::sendMail(
            __t('Resetar Senha'),
            [$reset->profile->email => $reset->profile->name],
            'mail-reset-code.html',
            [
                'profile' => $reset->profile,
                'reset' => $reset,
                'now' => new \DateTime(),
            ]);
    }

    /**
     * Envia o e-mail verificação para o perfil que estiver esperando os dados de verificação.
     *
     * @param Model_ProfileVerify $verify Informações de verificação
     */
    public static function sendVerifyCode(Model_ProfileVerify $verify)
    {
        // Faz o envio do e-mail
        ControllerParser::sendMail(
            __t('Verificação de Contas'),
            [$verify->profile->email => $verify->profile->name],
            'mail-verify-code.html',
            [
                'profile' => $verify->profile,
                'verify' => $verify,
                'now' => new \DateTime(),
           ]);
    }
}
