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

use \App as Application;
use \Controller\Profile as Controller_Profile;
use \Model\ProfileVerify as Model_ProfileVerify;

class ProfileVerifyObserver
{
    public function updating(Model_ProfileVerify $verify)
    {
        if ($verify->isDirty('used') && $verify->used && !$verify->profile->verified)
            $verify->profile->update([
                'verified' => true
            ]);
    }

    /**
     * Envia o código de verificação para o perfil quando é criado
     */
    public function created(Model_ProfileVerify $verify)
    {
        // Atualiza o perfil informando que não está verificado.
        // Isso é executado, toda vez que um código de verificação é criado.
        $verify->profile->update([
            'verified' => false
        ]);

        // Faz o envio do código de verificação.
        Controller_Profile::sendVerifyCode($verify);
    }
}
