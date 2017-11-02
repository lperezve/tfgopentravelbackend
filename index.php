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
/* -------------------- */

$app->get("/pruebas", function() use ($app, $db){
	echo "Hola mundo";
});

$app->get("/probando", function() use($app){
	echo "Otro texto cualquiera";
});

//LISTAR TODOS LOS RESTAURANTES
$app->get('/restaurantes', function() use ($app, $db){
	//hacemos una consulta sql para sacar todos los restaurantes de la BD
	$sql = 'SELECT * FROM restaurantes ORDER BY id DESC;';
	//ejecutamos la consulta en la base de datos
	$query = $db->query($sql);
	//fetch all saca todos los restaurantes sin necesidad de tener que hacer un bucle para recorrer los elementos
	//var_dump($query->fetch_all());
	//vamos a usar un bucle while para poder tener los restaurantes en un array de objetos
	while ($restaurante = ($query->fetch_assoc())) {
		$restaurantes[] = $restaurante;
	}

	$result = array(
		'status' => 'success',
		'code' => 200,
		//con esto al devolver la variable result, devolvemos tb el array de objetos ($restaurantes)
		'data' => $restaurantes
	);
	echo json_encode($result);

});

//LISTAR UN RESTAURANTE (ID)
$app->get('/restaurantes/:id', function($id) use ($app, $db){
	$sql = 'SELECT * FROM restaurantes WHERE id = '.$id.';';
	$query = $db->query($sql);
	
	$result = array(
		'status' => 'error',
		'code' => 404,
		'message' => 'restaurante no encontrado'
	);

	//si la query nos devuelve una columna es que el resultado está correcto
	if ($query->num_rows == 1) {
		//conseguimos el producto de la base de datos
		$restaurante = $query->fetch_assoc();
		$result = array(
			'status' => 'success',
			'code' => 200,
			'data' => $restaurante
		);
	} 
	echo json_encode($result);
});


//INSERTAR UN RESTAURANTE
$app->post('/restaurantes', function() use ($app, $db){
	//creamos una variable que se llame Json en la que vamos a recoger el valor de la variable request. La variable que nos llega por post se va a llamar json.
	$json = $app->request->post('json');
	//una vez tenemos el json, vamos a decodificar el valor de la variable.
	//el parámetro true, hace que nos convierta ese objeto en un array
	$data = json_decode($json, true);
	
	//vamos a hacer un if para todos los parámetros que no son obligatorios
	//dirección
	if(!isset($data['direccion'])){
		$data['direccion']=null;
	}
	//latitud
	if(!isset($data['latitud'])){
		$data['latitud']=null;
	}
	//longitud
	if(!isset($data['longitud'])){
		$data['longitud']=null;
	}
	//url
	if(!isset($data['url'])){
		$data['url']=null;
	}
	if(!isset($data['imagen'])){
		$data['imagen']=null;
	}

	//hacer una query a la base de datos
	$query = "INSERT INTO restaurantes VALUES(NULL,".
			"'{$data['nombre']}',".
			"'{$data['direccion']}',".
			"'{$data['latitud']}',".
			"'{$data['longitud']}',".
			"'{$data['url']}',".
			"'{$data['imagen']}'".
			");";

	//vamos a insertar la query en la bd
	$insert = $db->query($query);

	//ponemos un result por defecto de error, en caso de que se haga correctamente cambiará el valor
	$result = array(
		'status' => 'error',
		'code' => 404,
		'message' => 'Restaurante no creado'
	);
	
	//comprobación de la inseción
	if ($insert){
		$result = array(
		'status' => 'success',
		'code' => 200,
		'message' => 'Restaurante creado'
	);
	}

	//mostramos el rsultado de la consulta
	echo json_encode($result);
});

//BORRAR UN RESTAURANTE
$app->get('/delete-restaurantes/:id', function($id) use ($app, $db){
	$sql = 'DELETE FROM restaurantes WHERE id = '.$id.';';
	$query = $db->query($sql);

	if ($query){
		$result = array(
			'status' => 'success',
			'code' => 200,
			'message' => 'Restaurante '.$id.' borrado'
		);
	} else {
		$result = array(
		'status' => 'error',
		'code' => 404,
		'message' => 'Restaurante'.$id.' no eliminado'
		);
	}
	echo json_encode($result);
});

//ACTUALIZAR UN RESTAURANTE
$app->post('/update-restaurantes/:id', function($id) use ($app, $db){
	$json = $app->request->post('json');
	$data = json_decode($json, true);

	$sql = "UPDATE restaurantes SET ".
			"nombre = '{$data["nombre"]}', ".
			"direccion = '{$data["direccion"]}', ".
			"latitud = '{$data["latitud"]}', ".
			"longitud = '{$data["longitud"]}', ";

	if (isset($data['imagen'])){
		$sql .="imagen = '{$data["imagen"]}', ";
	}

	$sql .= "url = '{$data["url"]}' WHERE id = {$id};";
	//var_dump($sql);
	$query = $db->query($sql);

	if ($query) {
		$result = array(
			'status' => 'success',
			'code' => 200,
			'message' => 'Restaurante '.$id.' actualizado'
		);
	} else {
		$result = array(
		'status' => 'error',
		'code' => 404,
		'message' => 'Restaurante'.$id.' no actualizado'
		);
	}
	echo json_encode($result);
});

//SUBIR UNA IMAGEN A UN RESTAURANTE
$app->post('/upload-file', function() use($db, $app){
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

$app -> run();