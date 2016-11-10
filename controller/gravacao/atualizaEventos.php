<?php

// Incluir as classes do pacote Modelo e Controller
include ('../../model/bd.php');
include ('../../model/distancia.php');
include ('../../model/site.php');
include ('../../model/local.php');
include ('../../model/endereco.php');
include ('../../model/eventos.php');
include ('../../model/constantes.php');
include ('../../model/log.php'); 
include ('../../model/data.php'); 
include ('../../model/participante.php');
include ('../padroniza.php');
include('sites/facebook.php');


// Parametros
$gb_flg_rodar  = isset($_GET['rodar'])  ? $_GET['rodar']  : 0;


// Obter o nome do programa atual
$vr_arr_programa = explode("/",$_SERVER["PHP_SELF"]);
$vr_ds_programa  = $vr_arr_programa[ count($vr_arr_programa) - 1];

// Instancia a classe de data
$data = new data();

// Se não tem que rodar, abortar aqui
if (!$data->verificaExecucao($vr_ds_programa) && $gb_flg_rodar != 1) {
    die();
}

// Instanciar classe do facebook
$gb_facebook = new facebook ();

// Mostrar o retorno do site
echo $gb_facebook->atualizaEventos();

?>