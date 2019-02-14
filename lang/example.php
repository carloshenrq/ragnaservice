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

return [
    // App.php
    'Falha na leitura do arquivo de configuração.' => '',

    // Controller/ControllerParser.php
    'Token informado não é válido para esta requisição.' => '',
    'O Token informado não está autorizado.' => '',

    // Controller/Profile.php
    'Informações de Perfil foram atualizadas com sucesso' => '',
    'A Senha atual informada não confere com a senha atual.' => '',
    'As novas senhas digitadas não conferem.' => '',
    'Sua nova senha, não pode ser igual antiga.' => '',
    'Senha foi alterada com sucesso.' => '',
    'O Endereço de e-mail informado já foi utilizado.' => '',
    'Perfil criado com sucesso.' => '',
    'O Código de verificação informado já foi usado ou não existe.' => '',
    'Seu perfil foi verificado com sucesso.' => '',
    'Este perfil já está verificado.' => '',
    'E-mail de confirmação foi enviado para o e-mail do perfil' => '',
    'Código de reset de senha não é valido ou já foi usado.' => '',
    'Senha resetada com sucesso. Verifique seu e-mail.' => '',
    'Endereço de e-mail não pertence a nenhum perfil.' => '',
    'Reset de senha foi enviado para o e-mail do perfil' => '',
    'Nome de usuário e senha inválidos ou cadastro bloqueado.' => '',
    'O Endereço de e-mail informado é inválido' => '',
    'Data de nascimento informada é inválida ou idade inferior a 5 anos.' => '',
    'Informação de servidores alteradas com sucesso' => '',

    // app/View/mail.html
    'Olá <strong>%s</strong>!' => '',
    'Este e-mail foi enviado para <u><i>%s</i></u> às <u><i>%s</i></u> por <u><i>%s</i></u>.' => '',
    'Caso este e-mail tenha sido enviado de forma incorreta, por favor, desconsidere.' => '',

    // app/View/mail-verify-code.html
    'Verificação de Contas' => '',
    'Nós agradecemos seu registro, mas temos mais um passo antes de você possuir acesso completo!' => '',
    'É necessário que você use o código abaixo na tela de confirmação para concluir seu cadastro.' => '',
    'Após inserir corretamente este código na tela de confirmação, sua conta será validada com sucesso.' => '',
    'Este código possui validade até <u>%s</u>.' => '',

    // app/View/mail-reset-code.html
    'Resetar Senha' => '',
    'Parece que você perdeu sua senha! Não se preocupe, estamos aqui para ajudar.' => '',
    'Para concluir a recuperação de senha, precisamos que você insira o código abaixo na tela de confirmação.' => '',
    'Logo após inserir este código, você receberá uma nova senha.' => '',

    // app/View/mail-reset-pass.html
    'Senha resetada com sucesso' => '',
    'Sua senha foi resetada com sucesso.' => '',
    'Agora você já pode fazer login usando sua nova senha.' => '',
    'Estamos aqui sempre para ajudar. Tenha um bom dia.' => '',
];
