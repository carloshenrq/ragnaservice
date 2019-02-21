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

/**
 * Classe para realizar o autoloading dos dados.
 */
final class Autoload
{
    /**
     * Método para registrar o autoload.
     * 
     * @return void
     */
    public static function register()
    {
        // Registra as funções de autoload para que se tenha
        // o funcionamento das classes do framework.
        spl_autoload_register([
            'Autoload',
            'loader'
        ], true, false);
    }

    /**
     * Método para carregar as classes do framework.
     *
     * @param string $className Nome da classe que irá ser carregada
     * 
     * @return void
     */
    public static function loader($className)
    {
        // Monta o nome do arquivo da classe e logo após
        // Tenta fazer a inclusão do arquivo
        $classFile = join(DIRECTORY_SEPARATOR, [
            __DIR__,
            $className . '.php'
        ]);
        $classFile = str_replace('\\', DIRECTORY_SEPARATOR, $classFile);

        // Verifica se o arquivo existe se existir inclui o arquivo no código
        //@codingStandardsIgnoreStart
        if(file_exists($classFile))
            require_once $classFile;
        //@codingStandardsIgnoreEnd
    }
}

/**
 * Realiza a tradução das strings que forem solicitadas.
 * 
 * @param string $message Mensagem a ser traduzida
 *
 * @return string Mensagem traduzida.
 */
function __t($message)
{
    return App::getInstance()->getTranslate($message);
}
