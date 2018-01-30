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

/** ---------------------------------- RESTAURANTES --------------------------------- **/
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

	if (empty($restaurantes)){
		$result = array(
			'status' => 'error',
			'code' => 404,
			'message' => 'No hay restaurantes para mostrar'
		);
	} else {
		$result = array(
			'status' => 'success',
			'code' => 200,
			//con esto al devolver la variable result, devolvemos tb el array de objetos ($restaurantes)
			'data' => $restaurantes
		);
	}
	echo json_encode($result);

});

//LISTAR LOS RESTAURANTES DE MEJOR A PEOR VALORADOS
$app->get('/valorados', function() use ($app, $db){
	$sql = "SELECT r.*, AVG(op.puntuacion) AS media FROM restaurantes r JOIN opiniones_restaurantes op ON (r.id = op.id_restaurante) GROUP BY r.id ORDER BY media ASC";
	$query = $db->query($sql);
	while ($restauranteVal = ($query->fetch_assoc())) {
		$restaurantesVal[] = $restauranteVal;
	}
	if (empty($restaurantesVal)){
		$result = array(
			'status' => 'error',
			'code' => 404,
			'message' => 'No hay restaurantes para mostrar'
		);
	} else {
		$result = array(
			'status' => 'success',
			'code' => 200,
			//con esto al devolver la variable result, devolvemos tb el array de objetos ($restaurantes)
			'data' => $restaurantesVal
		);
	}
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

/* OBTENEMOS LAS OPINIONES DEL RESTAURANTE (ID) DESEADO */
$app->get('/opiniones/:id_rest', function($id_rest) use ($app, $db) {
	/*$sql = 'SELECT * FROM opiniones_restaurantes WHERE id_restaurante = '.$id_rest.' ORDER BY fecha DESC;';
	$query = $db->query($sql);

	while ($opinion = ($query->fetch_assoc())) {
		$opiniones[] = $opinion;
	}
	for ($i=0; $i<count($opiniones); $i++){
		$sqlusuario = "SELECT nombre FROM usuarios JOIN opiniones_restaurantes ON (usuarios.id = opiniones_restaurantes.id_usuario) WHERE opiniones_restaurantes.id_restaurante = ".$id_rest." AND opiniones_restaurantes.id = ".$opiniones[$i]['id'].";";
		$query2 = $db->query($sqlusuario);
		if ($query2->num_rows == 1){
			$usuarios[] = $query2->fetch_assoc();
		}
	}*/
	$sql = 'SELECT * FROM usuarios JOIN opiniones_restaurantes ON (usuarios.id = opiniones_restaurantes.id_usuario) WHERE opiniones_restaurantes.id_restaurante = '.$id_rest.' ORDER BY fecha DESC;';
	$query = $db->query($sql);
	while ($opinion = ($query->fetch_assoc())) {
		$opiniones[] = $opinion;
	}


	if (empty($opiniones)){
		$result = array(
			'status' => 'error',
			'code' => 404,
			'message' => 'No hay opiniones para el restaurante '.$id_rest
		);
	} else {
		$result = array(
			'status' => 'success',
			'code' => 200,
			'data' => $opiniones
		);
	}
	echo json_encode($result);
});

/* obtener los atributos de la bd */
$app->get('/atributos', function() use ($app, $db) {
	$sql = 'DESCRIBE restaurantes';
	$query = $db->query($sql);
	while($row = $query->fetch_assoc()){
		$campos[] = $row['Field'];
	}

	if (empty($campos)){
		$result = array(
			'status' => 'error',
			'code' => 404,
			'message' => 'Error al recuperar los atributos de restaurantes'
		);
	} else {
		$result = array(
			'status' => 'success',
			'code' => 200,
			'data' => $campos
		);
	}
	echo json_encode($result);

});

/* obtenemos el array con los campos correctos del dataset, a la vez que tenemos obtenemos el array con los fields de la bd */

$app->post('/upload-datarest', function() use ($app, $db){
	$json = $app->request->post('json');
	$data = json_decode($json, true);
	
	if(!isset($data['direccion'])){
		$data['direccion']=null;
	}
	if(!isset($data['latitud'])){
		$data['latitud']=null;
	}
	if(!isset($data['longitud'])){
		$data['longitud']=null;
	}
	if(!isset($data['url'])){
		$data['url']=null;
	}
	if(!isset($data['imagen'])){
		$data['imagen']=null;
	}

	$sql = 'DESCRIBE restaurantes';
	$query = $db->query($sql);
	while($row = $query->fetch_assoc()){
		$campos[] = $row['Field'];
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

$app->get('/python', function() use ($app) {
	$salida = array();
	$filename = 'probando.py';
	//exec("python scripts/probando.py");
	exec("python scripts/probando.py", $salida);
	print_r ($salida);
});


$app->run();