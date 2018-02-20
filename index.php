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

/* VARIABLES GLOBALES */
$conjunto = array();
$elemInsertJson = array();
$contador = 0;
$consultas = array();
/* ----------------------------------------------------------*/


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
		elseif (($extension == 'json') || ($extension == "geojson")) {
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
			        if (count($fields) <=1){
			        	$result = array(
						'status' 	=> 'error',
						'code'		=> 404,
						'message' 		=> "El carácter de separación es incorrecto"
						);
			        }
			        else {
				        $result = array(
							'status' 	=> 'success',
							'code'		=> 200,
							'data' 		=> $fields
						);
			    	}
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
	}
	elseif($extension == "json"){
		$result = array(
			'status' 	=> 'error',
			'code'		=> 404,
			'message' 	=> 'La extensión no es correcta',
		);
	}

    echo json_encode($result);
});

//sep: separacion del csv
$app->post('/up-csv/:filename/:sep', function($filename, $sep) use ($app, $db) {
	$json = $app->request->post('json');
	$data = json_decode($json, true); //aquí tengo el objeto restaurante con los campos que debo coger del fichero csv
	
	$result = array(
		'status' 	=> 'error',
		'code'		=> 404,
		'message' 	=> 'No ha sido posible la inserción',
	);

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
		        // 4. Una vez tengo todos los datos, tengo que hacer un for que recorra tantas veces como elementos tenga en info 
			    $sql = 'DESCRIBE restaurantes';
				$query = $db->query($sql);
				while($row = $query->fetch_assoc()){
					$campos[] = $row['Field'];
				}
		        

			    $encFields = false;
			    $item = 0;
			    // Por cada elemento de array, tengo que recorrer los atributos de la bd
			    for ($a = 0; $a<count($array); $a++){
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
			    		$result = array(
							'status' 	=> 'success',
							'code'		=> 200,
							'message' => 'Se ha insertado correctamente'
						);
			    	}
			    	else {
			    		$result = array(
							'status' 	=> 'error',
							'code'		=> 404,
							'message' => 'Error en la inserción',
							'queryIns'	=> $queryIns,
						);
			    	}
				} 
			    /*$result = array(
					'status' 	=> 'success',
					'code'		=> 200,
					'campos'	=> $campos,
					'sql'		=> $sql,
					//'longCampos' => $longCampos,
					//'nameData' => $nameData,
					//'nameAtr' 	=> $nameAtr,
					//'longFields' => $longFields,
					//'fields'	=> $fields,
					//'nameFields' => $nameFields,
					//'jsonArray'	=> $jsonArray,
					//'item'		=> $item,
					//'a'			=> $a,
					//'info'		=> $info,
					//'data'		=> $data,
					//'longInfoItem' => $longInfoItem,
					'insertado' => $insertado,
					//'valor'		=> $valor,
					'queryIns'	=> $queryIns,
					//'separacion' => $sep
				);*/
		    }
		    else {
		         $result = array (
		            'status' => 'error',
		            'code' => 404,
		            'message' => 'No es posible la insercción, el archivo csv no está bien formado, el número de claves es distinto al número de datos a insertar.',
		            //'count($fields)' => count($fields),
		            //'count($array[0])' => count($array[0]),
					//'separacion' => $sep
		        );
		    }
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


//OBTENER LOS KEYS DEL JSON($nombre) QUE SE HA IMPORTADO 
$app->get('/json-fields/:nombre', function($nombre) use($app){
	global $conjunto;
	$namefichero = explode(".", $nombre);
	$extension = $namefichero[sizeof($namefichero)-1];

	$result = array(
			'status' 	=> 'error',
			'code'		=> 404,
			'data' 		=> "no va bien"
	);
	if(($extension == "json") || ($extension == "geojson")){
		$data = file_get_contents("uploads/datasets/json/".$nombre);
		$file = json_decode($data, true);
	
		recorrido($file, "");
		if (!empty($conjunto)){
			$result = array(
				'status' 	=> 'success',
				'code'		=> 200,
				'data' 		=> $conjunto
			);
			$conjunto = array();
		}
	}
	else {
		$result = array(
			'status' 	=> 'error',
			'code'		=> 404,
			'message' 		=> "La extensión no es correcta"
		);
	}
	echo json_encode($result);
});



$app->post('/up-json/:filename', function($filename) use ($app, $db) {
	// VARIABLES GLOBALES QUE NECESITO EN AMBOS MÉTODOS 
	global $elemInsertJson;
	global $consultas;

	//aquí tengo el objeto restaurante con los campos que debo coger del fichero json
	$json = $app->request->post('json');
	$data = json_decode($json, true); 

	
	//1. OBTENER EL NÚMERO DE CAMPOS QUE TIENE DATA
	$elementosData = 0;
	foreach ($data as $datakey => $datavalue) {
		if (!empty($datavalue)){
			$elementosData++;
		}
	}
	$result = array(
			'status' 	=> 'error',
			'code'		=> 404,
			'message' 		=> "no va bien"
	);

	//PARA OBTENER EL FICHERO JSON Y PODER RECORRERLO 
	$datos = file_get_contents("uploads/datasets/json/".$filename);
	$file = json_decode($datos, true);
	// LLAMAMOS AL MÉTODO RECURSIVO PARA QUE EMPIECE A RECORRER EL JSON E INSERTAR CUANDO ES DEBIDO, ES NECESARIO TAMBIÉN DATA, Y EL NÚMERO DE ELEMENTOS DE ESTA.
	findAndInsert($file, "", $data, $elementosData);
	if (!empty($consultas)){
		for ($j=0; $j < count($consultas); $j++) { 
			$insert = $db->query($consultas[$j]);
		}
	}

	if (!empty($elemInsertJson)){
		$result = array(
			'status' 	=> 'success',
			'code'		=> 200,
			'elementosData' => $elementosData,
			'elemInsertJson'	=> $elemInsertJson,
			'consultas'	=> $consultas,
			'insert'	=> $insert
		);
		$elemInsertJson = array();
	}
	echo json_encode($result);
});


function recorrido($filevalue, $hoja){
	global $conjunto;
	global $elemInsertJson;
	$prueba = $hoja;
	foreach ($filevalue as $key => $value) {
		$hojanueva = $prueba;
		if (is_object($value) || is_array($value)){
			if (!is_int($key)){
				$hoja .= $key;
				$hoja .= ".";
				recorrido($value, $hoja);
				$hoja = $hojanueva;
			}
			else {
				recorrido($value, $hojanueva);
				$hoja = "";
			}
		}
		else {
			$hojanueva .= $key;
			if (!in_array($hojanueva, $conjunto)){
				array_push($conjunto, $hojanueva);
			}
		}
	}
}

function findAndInsert($filevalue, $hoja, $parejas, $elemParejas){
	global $elemInsertJson;
	global $contador;
	global $consultas;
	$prueba = $hoja;	
	foreach ($filevalue as $key => $value) {
		$hojanueva = $prueba;
		if (is_object($value) || is_array($value)){
			if (!is_int($key)){
				$hoja .= $key;
				$hoja .= ".";
				findAndInsert($value, $hoja, $parejas, $elemParejas);
				$hoja = $hojanueva;
			}
			else {
				findAndInsert($value, $hojanueva, $parejas, $elemParejas);
				$hoja = "";
			}
		}
		else {
			$hojanueva .= $key;
			//array_push($elemInsertJson, $elemParejas);
			//2. Por cada hoja nueva a la que llego, necesito buscar si ese valor existe en data (en $parejas). Para ello, recorremos data con un foreach 
			foreach ($parejas as $keypar => $valuepar) {
				// Si el valor de $hojanueva, está en alguno de los valores de data (parejas)
				if ($valuepar == $hojanueva) {
					//3. Tenemos que guardar el valor que tiene ésta hoja, es decir, el value del foreach y tenemos que conseguir que atributo lo contiene (nombre, dirección, etc).
					$valueInsert = $value;
					// 4. Se hace un switch para cada caso del atributo de la base de datos, la variable será la keypar
					switch ($keypar) {
						case 'nombre':
							$elemInsertJson[0] = $valueInsert;
							$contador++;
							break;
						case 'direccion':
							$elemInsertJson[1] = $valueInsert;
							$contador++;
							break;
						case 'latitud':
							$elemInsertJson[2] = $valueInsert;
							$contador++;
							break;
						case 'longitud':
							$elemInsertJson[3] = $valueInsert;
							$contador++;
							break;
						case 'url':
							$elemInsertJson[4] = $valueInsert;
							$contador++;
							break;
						case 'imagen':
							$elemInsertJson[5] = $valueInsert;
							$contador++;
							break;
						default:
							break;
					}
					// 5. Cuando el contador sea igual al número de de elementos ya tendremos todos los valores necesarios y por tanto, toca insertar en la base de datos.
					if ($contador == $elemParejas){
						$contador = 0;
						//Para asignar un valor nulo si el atributo x no ha sido seleccionado
						for ($i=0; $i < count($parejas)-1; $i++) { 
							if(!isset($elemInsertJson[$i])){
								$elemInsertJson[$i]=null;
							}
						}

						$query = "INSERT INTO restaurantes VALUES(NULL,".
							"'$elemInsertJson[0]', ".
							"'$elemInsertJson[1]', ".
							"'$elemInsertJson[2]', ".
							"'$elemInsertJson[3]', ".
							"'$elemInsertJson[4]', ".
							"'$elemInsertJson[5]');";
						//$insert = $db->query($query);
						array_push($consultas, $query);		
					}			
				}
			}	
		}
	}
}

$app -> run();