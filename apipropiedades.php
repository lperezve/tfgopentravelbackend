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
/* Insertar una nueva petición en la tabla propiedades pero no validada, la validación la hace el admin */
$app->get('/nueva-peticion/:id_usuario/:id_restaurante', function($id_usuario, $id_restaurante) use ($app, $db){

	$sql = "INSERT INTO propiedades (id, id_usuario, id_restaurante, validado) VALUES (NULL, ".$id_usuario.", ".$id_restaurante.", 0);";
	$insert = $db->query($sql);

	if (!$insert) {
		$result = array(
			'status' => 'error',
			'code' => 404,
			'message' => 'Petición rechazada'
		);
	}
	else {
		$result = array(
			'status' => 'success',
			'code' => 200,
			'message' => 'Petición realizada al administrador'
		);
	}
	echo json_encode($result);
});

/* OBTENER TODAS LAS PETICIONES QUE NO HAN SIDO VALIDADAS, EL NOMBRE DEL USUARIO QUE LO PIDE Y EL RESTAURANTE */
$app->get('/peticiones', function() use ($app, $db){
	$sql = 'SELECT p.*, u.alias, u.email, r.nombre
			FROM propiedades p join usuarios u on (p.id_usuario = u.id) join restaurantes r on (p.id_restaurante = r.id)
			WHERE p.validado = 0
			ORDER BY id ASC;';
	$query = $db->query($sql);
	while ($peticion = ($query->fetch_assoc())) {
		$peticiones[] = $peticion;
	}

	if (empty($peticiones)){
		$result = array(
			'status' => 'success',
			'code' => 200,
			'message' => 'No hay peticiones para validar'
		);
	}
	elseif (!empty($peticiones)) {
		$result = array(
			'status' => 'success',
			'code' => 200,
			'data' => $peticiones
		);
	}
	echo json_encode($result);

});

$app->get('/validar/:id', function($id) use ($app, $db){
	$sql = 'UPDATE propiedades 
			SET validado = 1 
			WHERE id = '.$id.';';
	$query = $db->query($sql);
	if ($query){
		$result = array(
			'status' => 'success',
			'code' => 200,
			'message' => 'Petición '.$id.' validada'
		);
	}
	else {
		$result = array(
			'status' => 'error',
			'code' => 404,
			'message' => 'La petición '.$id.' no ha podido ser validada'
		);
	}

	echo json_encode($result);
});

$app->get('/denegar/:id', function($id) use ($app, $db){
	$sql = 'DELETE 
			FROM propiedades 
			WHERE id ='.$id.';';

	$query = $db->query($sql);
	if ($query){
		$result = array(
			'status' => 'success',
			'code' => 200,
			'message' => 'Petición '.$id.' denegada'
		);
	}
	else {
		$result = array(
			'status' => 'error',
			'code' => 404,
			'message' => 'La petición '.$id.' no ha podido ser denegada'
		);
	}

	echo json_encode($result);
});


$app->run();
?>

