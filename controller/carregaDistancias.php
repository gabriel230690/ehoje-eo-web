<?php

// Incluir as classes do pacote Modelo
include ('../model/bd.php');
include ('../model/distancia.php');
include ('../model/constantes.php');


// Obter os parametros necessarios para carregar as cidades vizinhas
$gb_id_cidade    = $_POST["idCidOrigem"];
$gb_nr_distancia = $_POST["nr_distancia"];
$gb_json         = $_POST["prGeraJson"];

// Conectar-se ao banco
$gb_bd = new bd(false);

$gb_bd -> conecta();

// Instanciar a classe distancia
$gb_distancia = new distancia ($gb_bd);

// Carregar os distancias num JSON

$gb_resultado = $gb_distancia -> cidadeVizinhas($gb_id_cidade, $gb_nr_distancia , $gb_json);

//  Desconectar do banco
$gb_bd -> desconecta();

// Retornar JSON dos distancias
echo $gb_resultado;

?>


