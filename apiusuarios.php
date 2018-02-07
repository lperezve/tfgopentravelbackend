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

/* LISTAR TODOS LOS USUARIOS */
$app->get('/usuarios', function() use ($app, $db){
	$sql = 'SELECT * FROM usuarios ORDER BY id DESC';
	$query = $db->query($sql);
	while ($usuario = ($query->fetch_assoc())) {
		$usuarios[] = $usuario;
	}
	if (empty($usuarios)) {
		$result = array(
			'status' => 'error',
			'code' => 404,
			'message' => 'No hay usuarios para mostrar'
		);
	}
	else {
		$result = array(
			'status' => 'success',
			'code' => 200,
			'data' => $usuarios
		);
	}
	echo json_encode($result);
});

/* LISTAR UN USUARIO (ID) */
$app->get('/usuarios/:id', function($id) use ($app, $db){
	$sql = 'SELECT * FROM usuarios WHERE id = '.$id.';';
	$query = $db->query($sql);
	$result = array(
		'status' => 'error',
		'code' => 404,
		'message' => 'usuario no encontrado'
	);
	if ($query->num_rows == 1) {
		$usuario = $query->fetch_assoc();
		$result = array(
			'status' => 'success',
			'code' => 200,
			'data' => $usuario
		);
	} 
	echo json_encode($result);
});

/* Comprobar si existe un usuario */
$app->get('/existeusuario/:email/:password', function($email, $password) use ($app, $db){
	$cifrado = md5($password);
	$sql = "SELECT * FROM usuarios WHERE email = '$email' AND password = '$cifrado';";
	$query = $db->query($sql);
	$result = array(
		'status' => 'error',
		'code' => 404,
		'message' => 'usuario no encontrado'
	);
	if ($query->num_rows == 1) {
		$usuario = $query->fetch_assoc();
		$result = array(
			'status' => 'success',
			'code' => 200,
			'data' => $usuario
		);
	} 
	echo json_encode($result);
});

/* Comprobar que el usuario está registrado */
$app->get('/login/:email/:password', function($email, $password) use ($app, $db){
	$cifrado = md5($password);
	$sql = "SELECT * FROM usuarios WHERE email = '$email' AND password = '$cifrado';";
	$query = $db->query($sql);
	$result = array(
		'status' => 'error',
		'code' => 404,
		'message' => 'usuario no encontrado'
	);
	if ($query->num_rows == 1) {
		$usuario = $query->fetch_assoc();
		$result = array(
			'status' => 'success',
			'code' => 200,
			'message' => 'Usuario Registrado',
			'data' => $usuario

		);
	} 
	echo json_encode($result);
});

/* INSERTAR UN USUARIO */
$app->post('/usuarios', function() use ($app, $db){
	$json = $app->request->post('json');
	$data = json_decode($json, true);

	//apellido1
	if(!isset($data['apellido1'])){
		$data['apellido1']=null;
	}
	//apellido2
	if(!isset($data['apellido2'])){
		$data['apellido2']=null;
	}
	$data['admin']=false;
	$cifrado = md5($data['password']);

	$query = "INSERT INTO usuarios (id, nombre, apellido1, apellido2, alias, email, password, admin) 
		VALUES (null,
				'{$data['nombre']}',
				'{$data['apellido1']}',
				'{$data['apellido2']}',
				'{$data['alias']}',
				'{$data['email']}',
				'$cifrado',
				0);";
	$insert = $db->query($query);
	
	$result = array(
		'status' => 'error',
		'code' => 404,
		'message' => 'Usuario no creado',
		'query' => $query
	);
		
	if ($insert){
		$result = array(
			'status' => 'success',
			'code' => 200,
			'message' => 'Usuario creado'
		);
	}

	echo json_encode($result);

});

/* BORRAR UN USUARIO */
$app->get('/delete-usuarios/:id', function($id) use ($app, $db){
	$sql = 'DELETE FROM usuarios WHERE id = '.$id.';';
	$query = $db->query($sql);

	if ($query){
		$result = array(
			'status' => 'success',
			'code' => 200,
			'message' => 'Usuario '.$id.' borrado'
		);
	} else {
		$result = array(
		'status' => 'error',
		'code' => 404,
		'message' => 'Usuario'.$id.' no eliminado'
		);
	}
	echo json_encode($result);
});

/* ACTUALIZAR UN USUARIO */

$app->post('/update-usuarios/:id', function($id) use ($app, $db){
	$json = $app->request->post('json');
	$data = json_decode($json, true);
	$cifrado = md5($data['password']);

	$sql = "UPDATE usuarios SET ".
			"nombre = '{$data["nombre"]}', ".
			"apellido1 = '{$data["apellido1"]}', ".
			"apellido2 = '{$data["apellido2"]}', ".
			"alias = '{$data["alias"]}', ".
			"email = '{$data["email"]}', ".
			"password = '$cifrado' ".
			"WHERE id = {$id};";
	$query = $db->query($sql);

	if ($query) {
		$result = array(
			'status' => 'success',
			'code' => 200,
			'message' => 'Usuario '.$id.' actualizado'
		);
	} else {
		$result = array(
		'status' => 'error',
		'code' => 404,
		'message' => 'Usuario'.$id.' no actualizado'
		);
	}
	echo json_encode($result);
});

/* OBTENER LOS ATRIBUTOS DE LA TABLA USUARIOS*/
$app->get('/atributos', function() use ($app, $db) {
	$sql = 'DESCRIBE usuarios';
	$query = $db->query($sql);
	while($row = $query->fetch_assoc()){
		$campos[] = $row['Field'];
	}

	if (empty($campos)){
		$result = array(
			'status' => 'error',
			'code' => 404,
			'message' => 'Error al recuperar los atributos de usuarios'
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


$app->run();
?>