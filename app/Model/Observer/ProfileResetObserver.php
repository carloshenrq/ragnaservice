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

class ProfileResetObserver
{
    public function updating(Model_ProfileReset $reset)
    {
        if ($reset->isDirty('used') && $reset->used) {
            $config = Application::getInstance()->getConfig();

            $reset_chars = str_split($config->profile->reset_chars);
            $reset_length = $config->profile->reset_length;
            $reset_password = '';

            while (strlen($reset_password) < $reset_length)
                $reset_password .= $reset_chars[rand(1, count($reset_chars)) - 1];

            $reset->profile->changePassword($reset_password);

            Controller_Profile::sendResetPass($reset, $reset_password);
        }
    }
}
