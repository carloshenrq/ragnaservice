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

use \Model\ProfileReset as Model_ProfileReset;
use \Controller\Profile as Controller_Profile;
use \App as Application;

/**
 * Classe observadora dos eventos para reset de perfil.
 */
class ProfileResetObserver
{
    /**
     * Executa antes de gravar os dados no banco de dados para
     * o reset de perfil.
     * 
     * @param \Model\ProfileReset $reset Modelo que contém os dados de reset
     * 
     * @return void
     */
    public function updating(Model_ProfileReset $reset)
    {
        if ($reset->isDirty('used') && $reset->used) {
            $config = Application::getInstance()->getConfig();

            $resetChars = str_split($config->profile->reset_chars);
            $resetLength = $config->profile->reset_length;
            $resetPassword = '';

            while (strlen($resetPassword) < $resetLength)
                $resetPassword .= $resetChars[rand(1, count($resetChars)) - 1];

            $reset->profile->changePassword($resetPassword);

            Controller_Profile::sendResetPass($reset, $resetPassword);
        }
    }
}
