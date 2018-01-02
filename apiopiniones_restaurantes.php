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

/* mostrar todos los comentarios de un restaurante */
$app->get('/opiniones/:id_rest', function($id_rest) use ($app, $db) {
	$sql = 'SELECT * FROM opiniones_restaurantes WHERE id_restaurante = '.$id_rest.' ORDER BY fecha DESC;';
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

$app->post('/opinion', function() use ($app, $db){
	$json = $app->request->post('json');
	$data = json_decode($json, true);
	
	
	if(!isset($data['puntuacion'])){
		$data['puntuacion']=null;
	}

	if(!isset($data['mensaje'])){
		$data['mensaje']=null;
	}

	$id_restaurante = $data['id_restaurante'];
	$id_usuario = $data['id_usuario'];
	$puntuacion = $data['puntuacion'];
	$mensaje = $data['mensaje'];

	$query = "INSERT INTO opiniones_restaurantes (id, id_restaurante, id_usuario, puntuacion, mensaje) VALUES (null, $id_restaurante, $id_usuario, $puntuacion, '$mensaje')";

	$insert = $db->query($query);
	$result = array(
		'status' => 'error',
		'code' => 404,
		'message' => 'Opinion no creada'
	);
	if ($insert){
		$result = array(
		'status' => 'success',
		'code' => 200,
		'message' => 'Opinion creado'
		);
	}
	echo json_encode($result);

});

/* Calcular la media de opiniones de un restaurante */
$app->get('/avg-opiniones/:id_rest', function($id_rest) use ($app, $db) {
	$sql = 'SELECT round(avg(puntuacion),0) as media FROM opiniones_restaurantes WHERE id_restaurante = '.$id_rest.';';
	$query = $db->query($sql);

	$result = array(
		'status' => 'error',
		'code' => 404,
		'message' => 'Error de opiniones'
	);
	if ($query->num_rows == 1) {
		$mediaPuntuacion = $query->fetch_assoc();
		$result = array(
			'status' => 'success',
			'code' => 200,
			'data' => $mediaPuntuacion['media']
		);
	} 
	echo json_encode($result);
});

/* obtener la opinion con el id del parámetro */
$app->get('/opinion/:id', function($id) use ($app, $db) {
	$sql = 'SELECT * FROM opiniones_restaurantes WHERE id = '.$id.';';
	$query = $db->query($sql);

	$result = array(
		'status' => 'error',
		'code' => 404,
		'message' => 'No existe esa opinion'
	);
	if ($query->num_rows == 1) {
		//conseguimos el producto de la base de datos
		$opinion = $query->fetch_assoc();
		$result = array(
			'status' => 'success',
			'code' => 200,
			'data' => $opinion
		);
	} 
	echo json_encode($result);
});

/* OBTENER LAS OPINIONES SEGÚN LA FECHA: DE LA MÁS RECIENTE A LA MÁS ANTIGUA */
$app->get('/opiniones-recientes', function() use ($app, $db){
	$sql = 'SELECT * FROM opiniones_restaurantes ORDER BY fecha DESC';
	$query = $db->query($sql);

	while ($opinionFecha = ($query->fetch_assoc())) {
		$opinionesFecha[] = $opinionFecha;
	}

	if (empty($opinionesFecha)){
		$result = array(
			'status' => 'error',
			'code' => 404,
			'message' => 'No hay opiniones'
		);
	} else {
		$result = array(
			'status' => 'success',
			'code' => 200,
			'data' => $opinionesFecha
		);
	}
	echo json_encode($result);
});


$app->run();