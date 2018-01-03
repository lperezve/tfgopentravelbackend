<?php

require_once 'vendor/autoload.php';

$app = new \Slim\Slim();

//Conexión con la base de datos
$db = new mysqli('localhost', 'root', '', 'opentravel');
//Configuración de las cabeceras http.....
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Allow: GET, POST, OPTIONS, PUT, DELETE");
$method = $_SERVER['REQUEST_METHOD'];
if($method == "OPTIONS") {
    die();
}

/* ----------------------------------------------------------*/
/** MÉTODOS COMUNES **/

//SUBIR UNA IMAGEN
$app->post('/upload-image', function() use($db, $app){
	$result = array(
		'status' 	=> 'error',
		'code'		=> 404,
		'message' 	=> 'El archivo no ha podido subirse'
	);

	if(isset($_FILES['uploads'])){
		$piramideUploader = new PiramideUploader();

		$upload = $piramideUploader->upload('image', "uploads", "uploads", array('image/jpeg', 'image/png', 'image/gif'));
		$file = $piramideUploader->getInfoFile();
		$file_name = $file['complete_name'];

		if(isset($upload) && $upload["uploaded"] == false){
			$result = array(
				'status' 	=> 'error',
				'code'		=> 404,
				'message' 	=> 'El archivo no ha podido subirse'
			);
		}else{
			$result = array(
				'status' 	=> 'success',
				'code'		=> 200,
				'message' 	=> 'El archivo se ha subido',
				'filename'  => $file_name
			);
		}
	}
	echo json_encode($result);
});

//Subir un dataset: ya sea json o csv
$app->post('/upload-dataset', function() use($db, $app){
	$correcto = false;
	$result = array(
		'status' 	=> 'error',
		'code'		=> 404,
		'message' 	=> 'El archivo no ha podido subirse'
	);

	if (isset($_FILES['uploads'])){
		$piramideUploader = new PiramideUploader();
		/*
		prefijo de los ficheros - 'dataset'
		name del fichero que nos llega por $_FILES - "uploads"
		directorio donde se va a guardar - "uploads/datasets"
		tipo de ficheros permitidos - array('dataset/json')
		*/
		
		$fichero = $_FILES['uploads']['name'];
		$namefichero = explode(".", $fichero[0]);
		$extension = $namefichero[sizeof($namefichero)-1];
		if ($extension == 'csv'){
			$result = array(
				'message' 	=> 'csv',
				'fichero' => $fichero[0],
				'sizeof'	=> sizeof($namefichero),
				'extension'		=> $extension,
				'namefichero' => $namefichero
			);
			$correcto = true;
			$upload = $piramideUploader->uploadDataset('dataset', "uploads", "uploads/datasets/csv");
		}
		elseif ($extension == 'json') {
			$result = array(
				'message' 	=> 'json'
			);
			$correcto = true;
			$upload = $piramideUploader->uploadDataset('dataset', "uploads", "uploads/datasets/json");
		}
		else { //otros formatos
			$correcto = false;
		}
		if ($correcto){
			$file = $piramideUploader->getInfoFile();
			$file_name = $file['complete_name'];
			if(isset($upload) && $upload["uploaded"] == false){
				$result = array(
					'status' 	=> 'error',
					'code'		=> 404,
					'message' 	=> 'El archivo no ha podido subirse',
					'filename'  => $file_name
				);
			}else{
				$result = array(
					'status' 	=> 'success',
					'code'		=> 200,
					'message' 	=> 'El archivo se ha subido',
					'filename'  => $file_name
				);
			}
		}
		else {
			$result = array(
				'status' 	=> 'error',
				'code'		=> 404,
				'message' 	=> 'Formato no compatible'
			);
		}		
	}
	echo json_encode($result);
});	

/* GET CSV FIELDS
buscar dataset según el nombre y obtener sus fields (valido para ficheros csv)
Una vez obtenido sus fields, se le pasa al cliente para que el admin haga el emparejamiento
con los atributos de la BD */
$app->get('/csv-fields/:nombre/:sep', function($nombre, $sep) use($app){
	$namefichero = explode(".", $nombre);
	$extension = $namefichero[sizeof($namefichero)-1];
	if ($extension == "csv"){
		$directory = "uploads/datasets/csv";
	}
	elseif($extension == "json"){
		$directory = "uploads/datasets/json";
	}	
	$dirint = dir($directory);
	$fields = array();
    $fila = 1;

	while (($archivo = $dirint->read()) !== false) {
        if ($archivo == $nombre){
        	if ($extension == "csv"){
	        	$csv = file_get_contents("./uploads/datasets/csv/$nombre");
	        	if (($gestor = fopen("./uploads/datasets/csv/$nombre", "r")) !== FALSE) {
			        while ((($datos = fgetcsv($gestor, 1000, $sep)) !== FALSE) && ($fila <=2)) {
			            $fila++;
			            if ($fila <=2){
			                $fields = $datos;
			            }
			        }
			    }
		        fclose($gestor);
		        $result = array(
					'status' 	=> 'success',
					'code'		=> 200,
					'data' 		=> $fields
				);
		    }
		    else {
		    	//hacerlo para --> json <--
		    	$result = array(
					'message' 	=> 'implementar para json',
				);
		    }
		} else {
			$result = array(
				'status' 	=> 'error',
				'code'		=> 404,
				'message' 	=> 'El archivo no existe',
				'extension' => $extension,
				'archivo'	=> $archivo,
				'nombre'	=> $nombre
			);
		}
    }
    $dirint->close();
    echo json_encode($result);
});

$app->post('/up-csv2/:filename', function($filename) use ($app, $db) {
	$json = $app->request->post('json');
	$data = json_decode($json, true); //aquí tengo el objeto restaurante con los campos que debo coger del fichero csv
	
	/* 1. Tengo que obtener el fichero csv */
	$directory = "uploads/datasets/csv";
	$dirint = dir($directory);
	$fields = array();
    $info = array();
    $jsonArray = array();
    $fila = 1;
    $i = 0;
    $insertado = false;
    while (($archivo = $dirint->read()) !== false) {
        if ($archivo == $filename){
        	$json = file_get_contents("./uploads/datasets/csv/$filename");
        	if (($gestor = fopen("./uploads/datasets/csv/$filename", "r")) !== FALSE) {
        		/* 2. Una vez tengo el fichero csv, tengo que obtener todos los datos del fichero y guardarlos en $info */
        		 while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
        		 	$numero = count($datos);
		            $fila++;
		            if ($fila <=2){
		                $fields = $datos;
		            }
		            elseif ($fila > 2){
		            	$info[$i] = $datos;
		                $i++;
		            }
		        }
		        fclose($gestor);
		    }
		    /* 3. Una vez tengo todos los datos, tengo que hacer un for que recorra tantas veces como elementos tenga en info */
		    $sql = 'DESCRIBE restaurantes';
			$query = $db->query($sql);
			while($row = $query->fetch_assoc()){
				$campos[] = $row['Field'];
			}

		    $encFields = false;
		    $item = 0;
		    // Por cada elemento de info, tengo que recorrer los atributos de la bd
		    for ($a = 0; $a<count($info); $a++){
		    	//----- EMPIEZA LA CONSULTA PARA INSERTAR -------
				$queryIns = "INSERT INTO restaurantes VALUES(NULL, ";
		    	$longInfoItem = count($info[$a]);
		    	// por cada atributo de la bd, tengo que comprobar que esté en data(key), y, si está, sacar el data(value)
		    	$longCampos = count($campos);
		    	for ($c=1; $c<$longCampos; $c++) {
		    		$nameAtr = $campos[$c];
		    		$nameData = $data[strval($campos[$c])];
		    		if(empty(!$nameData)){
		    			$d = 0;
	    				$longFields = count($fields);
	    				$encFields = false;
	    				while (($d < $longFields) && ($encFields==false)){
							if ($nameData == $fields[$d]){
								$item = $d;
								$encFields = true;
								$nameFields = $fields[$d];
								$jsonArray[$c]=$info[$a][$item];
								/* en jsonArray[c] tenemos cada elemento que hay que meter en la tupla de la bd */
								$valor = strval($info[$a][$item]);
								$queryIns .="'".$valor."'";

							}
							else $d++;	
	    				}		
		    		}
		    		else {
		    			$queryIns .="NULL";
		    		}
		    		if ($c!=$longCampos-1)
		    			$queryIns .=", ";
		    	}
		    	/*INSERTAR LA TUPLA EN LA BD */ 
		    	//aqui tenemos el array con todos los atributos para insertar en la tupla, en jsonArray
		    	$queryIns .=");";
		    	$insert = $db->query($queryIns);
		    	if ($insert){
		    		$insertado = true;
		    	}
			} 
		    $result = array(
				'status' 	=> 'success',
				'code'		=> 200,
				'campos'	=> $campos,
				'longCampos' => $longCampos,
				'nameData' => $nameData,
				'nameAtr' 	=> $nameAtr,
				'longFields' => $longFields,
				'fields'	=> $fields,
				'nameFields' => $nameFields,
				'jsonArray'	=> $jsonArray,
				'item'		=> $item,
				'a'			=> $a,
				'info'		=> $info,
				'data'		=> $data,
				'longInfoItem' => $longInfoItem,
				'insertado' => $insertado,
				'valor'		=> $valor,
				'queryIns'	=> $queryIns
			);
		} else {
			$result = array(
				'status' 	=> 'error',
				'code'		=> 404,
				'message' 	=> 'El archivo no existe',
				//'extension' => $extension,
				//'archivo'	=> $archivo,
				'nombre'	=> $filename
			);
		}
	}
	$dirint->close();
    echo json_encode($result);
});



$app->post('/up-csv/:filename/:sep', function($filename, $sep) use ($app, $db) {
	$json = $app->request->post('json');
	$data = json_decode($json, true); //aquí tengo el objeto restaurante con los campos que debo coger del fichero csv
	
	/* 1. Tengo que obtener el fichero csv */
	$directory = "uploads/datasets/csv";
	$dirint = dir($directory);
	$fields = array();
    $info = array();
    $fila = 1;
    $i = 0;
    $insertado = false;
    $separacion = $sep;
    $jsonArray = array();
    while (($archivo = $dirint->read()) !== false) {
        if ($archivo == $filename){
        	$json = file_get_contents("./uploads/datasets/csv/$filename");
        	if (($gestor = fopen("./uploads/datasets/csv/$filename", "r")) !== FALSE) {
        		/* 2. Una vez tengo el fichero csv, tengo que obtener todos los datos del fichero y guardarlos en $info */
        		 while (($datos = fgetcsv($gestor, 1000, $separacion)) !== FALSE) {
        		 	$numero = count($datos);
		            $fila++;
		            if ($fila <=2){
		                $fields = $datos;
		            }
		            elseif ($fila > 2){
		            	$info[$i] = $datos;
		                $i++;
		            }
		        }
		        fclose($gestor);
		    }

		    /* 3. TENGO QUE COMPROBAR SI LA INFO VIENE TODO ENTRE COMILLAS O NO*/
		    if (count($info[0]) == 1){   //la info viene con comillas
		        for ($j=0;$j<count($info);$j++){//recorro cada elemento del array info
		            $string = $info[$j][0]; //en string toda la cadena con los elementos necesarios.
		             $pos = 0;
		            //HAY QUE RECORRER LA CADENA STRING:
		            $parcial = "";
		            $l = 0;
		            for ($k=0; $k<strlen($string)-1; $k++){
		                if ($string[$k] != ','){
		                    if ($string[$k] == '"'){
		                        $l = $k+1;
		                        while (($string[$l] != '"') && ($l<strlen($string)-1)){
		                            $parcial .= $string[$l];
		                            $l++;
		                        }
		                        $k = $l;
		                        $array[$j][$pos] = $parcial;
		                        $parcial = "";
		                        $pos++;
		                    }
		                    else{
		                        $parcial .= $string[$k];
		                    }
		                }
		                else {
		                    $array[$j][$pos] = $parcial;
		                    $parcial = "";
		                    $pos++;
		                }
		            }
		        }
		    }
		    elseif (count($info[0]) > 1){ //la info viene sin comillas
		        for ($j=0;$j<count($info);$j++){
		            $array[$j] = $info [$j];
		        }
		    }

		    //COMPROBAR QUE ESTÁ BIEN FORMADO EL CSV
		    if (count($fields) == count($array[0])){
		        $result = array (
		            'status' => 'success',
		            'code' => 200,
		            'fields' => $fields,
		            'datos' => $array,
		            'count($fields)' => count($fields),
		            'count($array[0])' => count($array[0]),
					'separacion' => $sep
		        );
		    }
		    else {
		         $result = array (
		            'status' => 'error',
		            'code' => 404,
		            'message' => 'El archivo csv no está bien formado',
		            'count($fields)' => count($fields),
		            'count($array[0])' => count($array[0]),
					'separacion' => $sep
		        );
		    }

		    // 4. Una vez tengo todos los datos, tengo que hacer un for que recorra tantas veces como elementos tenga en info 
		    $sql = 'DESCRIBE restaurantes';
			$query = $db->query($sql);
			while($row = $query->fetch_assoc()){
				$campos[] = $row['Field'];
			}

		    $encFields = false;
		    $item = 0;
		    // Por cada elemento de array, tengo que recorrer los atributos de la bd
		    for ($a = 0; $a<count($array)-1; $a++){
		    	//----- EMPIEZA LA CONSULTA PARA INSERTAR -------
				$queryIns = "INSERT INTO restaurantes VALUES(NULL, ";
		    	$longInfoItem = count($array[$a]);
		    	// por cada atributo de la bd, tengo que comprobar que esté en data(key), y, si está, sacar el data(value)
		    	$longCampos = count($campos);
		    	for ($c=1; $c<$longCampos; $c++) {
		    		$nameAtr = $campos[$c];
		    		$nameData = $data[strval($campos[$c])];
		    		if(empty(!$nameData)){
		    			$d = 0;
	    				$longFields = count($fields);
	    				$encFields = false;
	    				while (($d < $longFields) && ($encFields==false)){
							if ($nameData == $fields[$d]){
								$item = $d;
								$encFields = true;
								$nameFields = $fields[$d];
								$jsonArray[$c]=$array[$a][$item];
								// en jsonArray[c] tenemos cada elemento que hay que meter en la tupla de la bd 
								$valor = strval($array[$a][$item]);
								$queryIns .="'".$valor."'";

							}
							else $d++;	
	    				}		
		    		}
		    		else {
		    			$queryIns .="NULL";
		    		}
		    		if ($c!=$longCampos-1)
		    			$queryIns .=", ";
		    	}
		    	//INSERTAR LA TUPLA EN LA BD //
		    	//aqui tenemos el array con todos los atributos para insertar en la tupla, en jsonArray
		    	$queryIns .=");";
		    	$insert = $db->query($queryIns);
		    	if ($insert){
		    		$insertado = true;
		    	}
			} 
		    $result = array(
				'status' 	=> 'success',
				'code'		=> 200,
				'campos'	=> $campos,
				'longCampos' => $longCampos,
				'nameData' => $nameData,
				'nameAtr' 	=> $nameAtr,
				'longFields' => $longFields,
				'fields'	=> $fields,
				'nameFields' => $nameFields,
				'jsonArray'	=> $jsonArray,
				'item'		=> $item,
				'a'			=> $a,
				'info'		=> $info,
				'data'		=> $data,
				'longInfoItem' => $longInfoItem,
				'insertado' => $insertado,
				'valor'		=> $valor,
				'queryIns'	=> $queryIns,
				'separacion' => $sep
			);
		} else {
			$result = array(
				'status' 	=> 'error',
				'code'		=> 404,
				'message' 	=> 'El archivo no existe',
				//'extension' => $extension,
				//'archivo'	=> $archivo,
				'nombre'	=> $filename,
				'separacion' => $sep
			);
		}
	}
	$dirint->close();
    echo json_encode($result);
});

$app -> run();