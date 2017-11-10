CREATE DATABASE IF NOT EXISTS tfgdb;
USE opentravel;

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