<?php
	/*$salida = array();
    exec("probando.py", $salida);
    print_r ($salida);*/

    /*$result = exec('python probando.py');
    print_r($result);*/
    $salida = shell_exec('probando.py');
    //$salida = shell_exec('findKeys.py');
    echo $salida;
    
?>