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

namespace Model\Observer;

use \Model\Profile as Model_Profile;
use \Model\ProfileVerify as Model_ProfileVerify;
use \App as Application;

class ProfileObserver
{
    /**
     * Pouco antes de atualizar informações da conta do jogador...
     */
    public function updating(Model_Profile $profile)
    {
        if ($profile->isDirty('verified') && $profile->verified) {
            // @todo Enviar e-mail informando que o usuário foi verificado com sucesso.
        }
    }

    /**
     * Criação de contas faz a verificação se há necessidade
     * da criação de um código de verificação
     */
    public function created(Model_Profile $profile)
    {
        $config = Application::getInstance()->getConfig();

        // Se a verificação estiver ativa, então
        // irá marcar o perfil como não verificado e logo após
        // irá criar o código de ativação para o mesmo.
        if (!is_null($config) && $config->profile->verification) {
            // String para a diferença de tempo.
            $expires_after = sprintf('%d minutes', $config->profile->expires_after);

            Model_ProfileVerify::create([
                'profile_id' => $profile->id,
                'code' => hash('md5', uniqid().microtime(true)),
                'used' => false,
                'used_at' => null,
                'expires_at' => (new \DateTime())->add(date_interval_create_from_date_string($expires_after))
            ]);
        }
    }
}
