/*CREATE DATABASE IF NOT EXISTS opentravel;
USE opentravel;*/

CREATE TABLE restaurantes (
	id 			int(255) auto_increment not null,
	nombre 		varchar (255),
	direccion 	varchar(255),
	latitud 	varchar(15),
	longitud 	varchar(15),
	url 		varchar(255),
	imagen 		varchar(255),
	CONSTRAINT pk_restaurantes PRIMARY KEY (id)
)ENGINE=InnoDb;

CREATE TABLE usuarios (
	id 			int(255) auto_increment not null,
	nombre		varchar (255),
	apellido1	varchar (255),
	apellido2	varchar (255),
	alias		varchar (255),
	email		varchar (255),
	password	varchar (255),
	rol_publicador	boolean not null default 0,
	CONSTRAINT pk_usuarios PRIMARY KEY (id),
	UNIQUE (alias, email)
)ENGINE=InnoDb;

CREATE TABLE opiniones_restaurantes (
	id 				int(255) auto_increment not null,
	id_restaurante	int(255) not null,
	id_usuario		int(255) not null,
	puntuacion		int(10) not null,
	fecha 			TIMESTAMP DEFAULT CURRENT_TIMESTAMP  ON UPDATE CURRENT_TIMESTAMP,
	mensaje			varchar(420),
	CONSTRAINT pk_opiniones_restaurantes PRIMARY KEY (id),
	CONSTRAINT fk_idrestaurante_opiniones FOREIGN KEY (id_restaurante) REFERENCES restaurantes(id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_idusuario_opiniones FOREIGN KEY (id_usuario) REFERENCES usuarios (id) ON DELETE CASCADE ON UPDATE CASCADE
)ENGINE=InnoDb;

CREATE TABLE propiedades (
	id 	int(255) auto_increment NOT NULL,
	id_usuario 	int(255) NOT NULL,
	id_restaurante int(255) NOT NULL UNIQUE,
	validado int(1) NOT NULL DEFAULT 0,
	CONSTRAINT pk_idpropiedades PRIMARY KEY (id),
	CONSTRAINT fk_id_usuario_propiedades FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fl_id_restaurante_propiedades FOREIGN KEY (id_restaurante) REFERENCES restaurantes(id) ON DELETE CASCADE ON UPDATE CASCADE
)ENGINE=InnoDb;

CREATE TABLE provincias (
	id 				int(255) auto_increment not null,
	nombre 			varchar (255) not null,
	CONSTRAINT 		pk_provincias PRIMARY KEY (id)
)ENGINE=InnoDb;

CREATE TABLE historial_rest_prov (
	id 				int(255) auto_increment not null,
	id_restaurante	int(255) not null UNIQUE,
	id_provincia	int(255) not null,
	CONSTRAINT pk_idrestprovincia PRIMARY KEY (id),
	CONSTRAINT fk_idrestaurante_historial FOREIGN KEY (id_restaurante) REFERENCES restaurantes(id) ON DELETE CASCADE ON UPDATE CASCADE
)ENGINE=InnoDb;