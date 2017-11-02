CREATE DATABASE IF NOT EXISTS opentravel;
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