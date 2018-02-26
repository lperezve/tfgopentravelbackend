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
	if(!isset($data['imagen'])){
		$data['imagen']=null;
	}

	$id_restaurante = $data['id_restaurante'];
	$id_usuario = $data['id_usuario'];
	$puntuacion = $data['puntuacion'];
	$mensaje = $data['mensaje'];
	$imagen = $data['imagen'];

	$query = "INSERT INTO opiniones_restaurantes (id, id_restaurante, id_usuario, puntuacion, mensaje, imagen) VALUES (null, $id_restaurante, $id_usuario, $puntuacion, '$mensaje', '$imagen')";

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
	$sql = 'SELECT * FROM opiniones_restaurantes ORDER BY fecha DESC LIMIT 10';
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

$app->get('/opiniones-usuario/:id', function($id) use ($app, $db){	
	$sql = 'SELECT r.nombre, op.id, op.puntuacion, op.fecha, op.mensaje, op.imagen, op.id_restaurante FROM opiniones_restaurantes op JOIN restaurantes r ON (op.id_restaurante = r.id) WHERE op.id_usuario = '.$id.';';
	$query = $db->query($sql);

	while ($opinionUsuario = ($query->fetch_assoc())) {
		$opinionesUsuario[] = $opinionUsuario;
	}

	if (empty($opinionesUsuario)){
		$result = array(
			'status' => 'error',
			'code' => 404,
			'message' => 'No hay opiniones'
		);
	} else {
		$result = array(
			'status' => 'success',
			'code' => 200,
			'data' => $opinionesUsuario
		);
	}
	echo json_encode($result);
});

//método para subir imagenes en los comentarios
$app->post('/upload-images', function() use ($db, $app){
	//resultado no valido, para que sea el que se va a devolver por defecto
	$result = array(
		'status' => 'error',
		'code' => 404,
		'message' => 'La imagen no ha podido subirse'
	);

	if(isset($_FILES['uploads'])){
		$piramideUploader = new PiramideUploader();
		//upload('prefijo que lleva el nombre', 'el nombre que nos llega por $_FILES', 'directorio donde se va a guardar', 'tipo de ficheros permitidos')
		//con esto ya se está subiendo la imagen
		$upload = $piramideUploader->upload('image', "uploads", "uploads/images_comment", array('image/jpeg', 'image/png', 'image/gif'));
		//conseguir todos los datos del fichero que acabamos de subir
		$file = $piramideUploader->getInfoFile();
		$file_name = $file['complete_name'];

		if (isset($upload) && $upload["uploaded"] == false){
			$result = array(
				'status' => 'error',
				'code' => 404,
				'message' => 'Error en la carga del archivo'
			);
		}
		else {
			$result = array(
				'status' => 'success',
				'code' => 404,
				'message' => 'Hay imagen',
				'filename' => $file_name
			);
		}
	}
	echo json_encode($result);
});

//borrar una opinión
$app->get('/delete-opinion/:id', function($id) use ($app, $db){
	$sql = 'DELETE FROM opiniones_restaurantes WHERE id = '.$id.';';
	$query = $db->query($sql);

	if ($query){
		$result = array(
			'status' => 'success',
			'code' => 200,
			'message' => 'Opinion '.$id.' borrado'
		);
	} else {
		$result = array(
		'status' => 'error',
		'code' => 404,
		'message' => 'Opinion'.$id.' no eliminado'
		);
	}
	echo json_encode($result);
});

//actualizar una opinión
$app->post('/update-opinion/:id', function($id) use ($app, $db) {
	$json = $app->request->post('json');
	$data = json_decode($json, true);

	$sql = "UPDATE opiniones_restaurantes SET ".
			"id_restaurante = '{$data["id_restaurante"]}', ".
			"id_usuario = '{$data["id_usuario"]}', ".
			"puntuacion = '{$data["puntuacion"]}', ".
			"mensaje = '{$data["mensaje"]}', ".
			"imagen = '{$data["imagen"]}' ".
			"WHERE id = {$id};";

	$query = $db->query($sql);
	if ($query) {
		$result = array(
			'status' => 'success',
			'code' => 200,
			'message' => 'Opinion '.$id.' actualizada',
			'sql'	=> $sql
		);
	} else {
		$result = array(
			'status' => 'error',
			'code' => 404,
			'message' => 'Opinion'.$id.' no actualizada',
			'sql'	=> $sql
		);
	}
	echo json_encode($result);

});

/* DEVOLVER LAS MEJORES OPINIONES (LAS QUE TIENEN MAYOR PUNTUACIÓN) */
$app->get('/mejores-opiniones', function() use ($app, $db){
	$sql = 'SELECT op.*, r.nombre FROM opiniones_restaurantes op JOIN restaurantes r ON (op.id_restaurante = r.id) WHERE op.imagen != "" ORDER BY op.puntuacion DESC LIMIT 5;';
	$query = $db->query($sql);

	while ($mejorOp = ($query->fetch_assoc())) {
		$mejorOps[] = $mejorOp;
	}

	if (empty($mejorOps)){
		$result = array(
			'status' => 'error',
			'code' => 404,
			'message' => 'No hay opiniones'
		);
	} else {
		$result = array(
			'status' => 'success',
			'code' => 200,
			'data' => $mejorOps
		);
	}
	echo json_encode($result);
});

$app->run();

?>