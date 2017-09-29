<?php

ini_set('display_errors', 1);
ini_set('display_startup_erros', 1);


// Incluir as classes do pacote Modelo
include('gravacao/sites/facebook.php');
include('../model/bd.php');
include('../model/distancia.php');
include('../model/site.php');
include('../model/local.php');
include('../model/endereco.php');
include('../model/eventos.php');
include('../model/constantes.php');
include('../model/log.php');

// Obter os parametros necessarios para carregar os eventos
$gb_nm_cidade = $_POST["nmCidade"];
$gb_id_local = $_POST["idLocal"];
$gb_dt_inicio = $_POST["data_ini"];
$gb_dt_fim = $_POST["data_fim"];
$gb_nr_distancia = $_POST["nr_distancia"];

// Conectar-se ao banco
$gb_bd = new bd(false);

$gb_bd->conecta();

// Instanciar a classe eventos
$gb_eventos = new eventos($gb_bd);

// Carregar os eventos num JSON
$gb_resultado = $gb_eventos->carregaEventos($gb_nm_cidade, $gb_id_local, $gb_dt_inicio, $gb_dt_fim, $gb_nr_distancia, false);

//  Desconectar do banco
$gb_bd->desconecta();

// Retornar JSON dos eventos
echo $gb_resultado;

?>


