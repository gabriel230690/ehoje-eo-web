<?php

// Incluir as classes do pacote Modelo
include ('../model/bd.php');
include ('../model/distancia.php');
include ('../model/site.php');
include ('../model/local.php');
include ('../model/endereco.php');
include ('../model/eventos.php');
include ('../model/constantes.php');
include ('../model/log.php');

// Obter os parametros necessarios para carregar o evento
$gb_id_evento = $_POST["idEvento"];

// Conectar-se ao banco
$gb_bd = new bd(false);

$gb_bd -> conecta();

// Instanciar a classe eventos
$gb_eventos =  new eventos($gb_bd);

// Carregar os eventos num JSON
$gb_resultado = $gb_eventos -> buscaEvento($gb_id_evento);

//  Desconectar do banco
$gb_bd -> desconecta();

// Retornar JSON dos eventos
echo $gb_resultado;

?>


