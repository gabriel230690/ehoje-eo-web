<?php

// Incluir as classes do pacote Modelo
include ('../model/bd.php');
include ('../model/distancia.php');
include ('../model/site.php');
include ('../model/local.php');
include ('../model/endereco.php');
include ('../model/eventos.php');
include ('../model/participante.php');
include ('../model/constantes.php');
include ('../model/log.php');
include ('../controller/gravacao/sites/facebook.php');

// Obter os parametros necessarios para carregar os participantes
$gb_id_evento   = $_POST["idEvento"];
$gb_ds_sexo     = $_POST['dsSexo'];

// Conectar-se ao banco
$gb_bd = new bd(false);

$gb_bd -> conecta();

// Instanciar a classe eventos
$gb_participante =  new participante($gb_bd);

// Carregar os participantes num JSON
$gb_resultado = $gb_participante -> carregaParticipantes($gb_id_evento, $gb_ds_sexo );

//  Desconectar do banco
$gb_bd -> desconecta();

// Retornar JSON dos participantes
echo $gb_resultado;

?>


