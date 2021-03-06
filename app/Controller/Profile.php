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

/**
 * Classe controladora das requisições de perfil
 */
class Profile extends ControllerParser
{
    /**
     * @see ControllerParser::init()
     * 
     * @return void
     */
    public function init()
    {
        // Rota de verificação depende da configuração.
        $this->addRouteRestriction('verify_POST', function() {
            return ($this->getConfig()->profile->verification == true);
        });

        // Adicionado restrição para as rotas de não ser necessário realização de login.
        $this->applyRestrictionOnAllRoutes(function() {
            return (($this->getApplication()->getPermission() & 2) == 0);
        }, ['verify_POST']);

        // Aplica em todas as outras rotas, necessidade para
        // ser realizado o login.
        $this->applyRestrictionOnAllRoutes(function() {
            return (($this->getApplication()->getPermission() & 2) != 0);
        }, ['verify_POST', 'create_POST', 'login_POST', 'reset_POST', 'reset_confirm_POST']);
    }

    /**
     * Faz a chamada para alterações de informações do perfil.
     * 
     * @param object $response Objeto de resposta da requisição
     * @param array  $args     Parametros para a requisição
     * 
     * @return void
     */
    public function change_settings_POST($response, $args)
    {
        // Configurações que serão alteradas
        $name = $this->post['name'];
        $gender = $this->post['gender'];
        $birthdate = $this->post['birthdate'];

        if (empty($birthdate))
            $birthdate = null;

        if (!empty($birthdate) && self::verifyBirthDate($birthdate) === false)
            return $response->withJson([
                'error' => true,
                'message' => __t('Data de nascimento informada é inválida ou idade inferior a 5 anos.')
            ]);

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
     * 
     * @param object $response Objeto de resposta da requisição
     * @param array  $args     Parametros para a requisição
     * 
     * @return void
     */
    public function change_password_POST($response, $args)
    {
        $oldPass = $this->post['old_pass'];
        $newPass = $this->post['new_pass'];
        $cnfPass = $this->post['cnf_pass'];

        $profile = $this->getApplication()->getProfile();

        if ($profile->password !== hash('sha512', $oldPass))
            return $response->withJson([
                'error' => true,
                'message' => __t('A Senha atual informada não confere com a senha atual.')
            ]);
        
        if (hash('sha512', $newPass) !== hash('sha512', $cnfPass))
            return $response->withJson([
                'error' => true,
                'message' => __t('As novas senhas digitadas não conferem.')
            ]);
        
        if (hash('sha512', $oldPass) === hash('sha512', $newPass))
            return $response->withJson([
                'error' => true,
                'message' => __t('Sua nova senha, não pode ser igual antiga.')
            ]);
    
        $profile->changePassword($newPass);

        return $response->withJson([
            'success' => true,
            'message' => __t('Senha foi alterada com sucesso.')
        ]);
    }

    /**
     * Realiza a criação de uma nova conta.
     * 
     * @param object $response Objeto de resposta da requisição
     * @param array  $args     Parametros para a requisição
     * 
     * @return void
     */
    public function create_POST($response, $args)
    {
        // Dados e informações que vieram por post para criação da conta.
        $name = $this->post['name'];
        $gender = $this->post['gender'];
        $birthdate = $this->post['birthdate'];
        $email = $this->post['email'];
        $password = $this->post['password'];

        // Informações de data de nascimento
        $dtBirth = null;

        // Verifica se o endereço de e-mail informado é valido
        if (self::verifyMail($email) === false)
            return $response->withJson([
                'error' => true,
                'message' => __t('O Endereço de e-mail informado é inválido.')
            ]);

        // Verifica a data de nascimento para cadastrar no perfil
        if (!empty($birthdate)) {
            if (self::verifyBirthDate($birthdate) === false)
                return $response->withJson([
                    'error' => true,
                    'message' => __t('Data de nascimento informada é inválida ou idade inferior a 5 anos.')
                ]);
            $dtBirth = new \DateTime($birthdate);
        }

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
            'birthdate' => $dtBirth,
            'email' => $email,
            'password' => $password,
            'permission' => $this->getConfig()->profile->permission,
            'blocked' => false,
            'blocked_reason' => null,
            'blocked_until' => null,
            'verified' => true,
            'register_date' => new \DateTime(),
            'facebook_id' => null,
            'ga_enabled' => false,
            'ga_secret' => null
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
     * @param object $response Objeto de resposta da requisição
     * @param array  $args     Parametros para a requisição
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

        if ($verify === null)
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
     * 
     * @param object $response Objeto de resposta da requisição
     * @param array  $args     Parametros para a requisição
     * 
     * @return void
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

        if ($verify === null) {
            $expiresAfter = sprintf('%d minutes', $this->getConfig()->profile->expires_after);
            $verify = new Model_ProfileVerify;
            $verify->profile_id = $profile->id;
            $verify->code = hash('md5', uniqid() . microtime(true));
            $verify->used = false;
            $verify->used_at = null;
            $verify->expires_at = (new \DateTime())->add(date_interval_create_from_date_string($expiresAfter));
        }

        $verify->updated_at = new \DateTime();
        $verify->save();

        return $response->withJson([
            'success' => true,
            'message' => __t('E-mail de confirmação foi enviado para o e-mail do perfil')
        ]);
    }

    /**
     * Responde a confirmação de reset do usuário.
     * 
     * @param object $response Objeto de resposta da requisição
     * @param array  $args     Parametros para a requisição
     * 
     * @return void
     */
    public function reset_confirm_POST($response, $args)
    {
        $code = $this->post['code'];

        $reset = Model_ProfileReset::where([
            ['code', '=', $code],
            ['used', '=', false],
            ['expires_at', '>=', (new \DateTime())->format('Y-m-d H:i:s')]
        ])->first();

        if ($reset === null)
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
     * 
     * @param object $response Objeto de resposta da requisição
     * @param array  $args     Parametros para a requisição
     * 
     * @return void
     */
    public function reset_POST($response, $args)
    {
        // Email para enviar uma possível recuperação de senha.
        $email = $this->post['email'];

        if (self::verifyMail($email) === false)
            return $response->withJson([
                'error' => true,
                'message' => __t('O Endereço de e-mail informado é inválido.')
            ]);

        // Obtém o perfil para ser verificado...
        $profile = Model_Profile::where([
            ['email', '=', $email]
        ])->first();

        if ($profile === null)
            return $response->withJson([
                'error' => true,
                'message' => __t('Endereço de e-mail não pertence a nenhum perfil.'),
            ]);

        // Obtém o reset que está em memória para enviar o proprio pedido novamente...
        $reset = $profile->resets->first(function($r) {
            return (!$r->used && $r->expires_at->format('Y-m-d H:i:s') >= (new \DateTime())->format('Y-m-d H:i:s'));
        });

        // Se não houver resets em aberto, então irá criar um novo
        if ($reset === null) {
            $reset = Model_ProfileReset::create([
                'profile_id' => $profile->id,
                'code' => hash('md5', uniqid() . microtime(true)),
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
     * @param object $response Objeto de resposta da requisição
     * @param array  $args     Parametros para a requisição
     * 
     * @return object
     */
    public function login_POST($response, $args)
    {
        $email = $this->post['email'];
        $password = $this->post['password'];

        if (self::verifyMail($email) === false)
            return $response->withJson([
                'error' => true,
                'message' => __t('O Endereço de e-mail informado é inválido.')
            ]);

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
                'birthdate' => (($token->profile->birthdate === null) ? null : $token->profile->birthdate->format('Y-m-d'))
            ]
        ]);
    }

    /**
     * Envia o e-mail contendo a nova senha do usuário.
     * 
     * @param Model_ProfileReset $reset    Dados de senha resetada do perfil
     * @param string             $password Nova senha do perfil
     * 
     * @return void
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
     * 
     * @param Model_ProfileReset $reset Dados de reset para o perfil
     * 
     * @return void
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
     * 
     * @return void
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

    /**
     * Verifica um endereço de e-mail.
     * 
     * @param string $email Endereço de e-mail a ser validado
     * 
     * @return bool
     */
    public static function verifyMail($email)
    {
        return (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
    }

    /**
     * Faz a validação de uma data de nascimento.
     * Retorna verdadeiro em caso da data ser válida.
     * 
     * @param string $date Data de nascimento em formato 'YYYY-MM-DD'
     * 
     * @return bool
     */
    public static function verifyBirthDate($date)
    {
        // A data deve vir no formato: YYYY-MM-DD
        if (!preg_match('/^([0-9]{4})\-(0[1-9]|1[0-2])\-(0[1-9]|[1-2][0-9]|3[0-1])/i', $date))
            return false;
        
        // Define o objeto de data...
        $dtTest = new \DateTime($date);

        // Se a data for diferente da informada, o formato é inválido
        // Pois avançou no tempo...
        if ($dtTest->format('Y-m-d') !== $date)
            return false;

        // Data atual do servidor...
        $dtResult = $dtTest->diff(new \DateTime());

        // Se houve inversão, a data informada é maior que a data
        // atual, ninguém nasce no futuro...
        // Crianças com menos de 5 anos não jogam isso...
        if ($dtResult->invert || $dtResult->y < 5)
            return false;

        // Retorna verdadeiro caso a validação de datas seja ok.
        return true;
    }
}
