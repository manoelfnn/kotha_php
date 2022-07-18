<?php
namespace Core\Libs;

class Validation
{

    // https://gist.github.com/rafael-neri/ab3e58803a08cb4def059fce4e3c0e40
    public static function cpf($_cpf) {
     
        // Extrai somente os números
        $_cpf = preg_replace( '/[^0-9]/is', '', $_cpf );
         
        // Verifica se foi informado todos os digitos corretamente
        if (strlen($_cpf) != 11) {
            return false;
        }
    
        // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
        if (preg_match('/(\d)\1{10}/', $_cpf)) {
            return false;
        }
    
        // Faz o calculo para validar o _CPF
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $_cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($_cpf[$c] != $d) {
                return false;
            }
        }
        return true;
    
    }
}