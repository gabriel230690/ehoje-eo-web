<?php

// GALERA NA FESTA E VALE 1 CONVITE NAO ESTAO FUNCIONANDO !!!


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
include ('../padroniza.php');
include('sites/facebook.php');
include('sites/nightecia.php');
include('sites/blueticket.php');
include('sites/vale1convite.php');
include('sites/hlera.php');
include('sites/postosBrava.php');
include('sites/funplace.php');
include('sites/foursquare.php');

// Obter os parametros necessarios para carregar os eventos
$gb_id_site    = isset($_GET["idSite"]) ? $_GET['idSite'] : 0;
$gb_flg_gravar = isset($_GET['gravar']) ? $_GET['gravar'] : 1;
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

// Rodar o Facebook primeiro para poder pegar o ID do evento
// Eventos do Facebook ou de todos os sites
if ($gb_id_site == 8 || $gb_id_site == 0 ) { 

	$gb_facebook = new facebook ();

	// Mostrar o retorno do site
	echo $gb_facebook->obtemEventosPages($gb_flg_gravar);

}

// Rodar o Funplace na sequencia para poder pegar o ID do evento 
// Funplace

/* NAO FUNCIONA

if ($gb_id_site == 7 || $gb_id_site == 0 ) { 

	$gb_funplace = new funplace ();

	// Mostrar o retorno do site
	echo $gb_funplace->inicializaFunplace($gb_flg_gravar);

}
*/

// Eventos do Blueticket
if ($gb_id_site == 2 || $gb_id_site == 0) {

	$gb_blueticket = new blueticket();
	
	// Mostrar o retorno do site
	echo $gb_blueticket->obtemEventos($gb_flg_gravar);
	
}

// Eventos do Night e Cia ou de todos os sites
if ($gb_id_site == 3 || $gb_id_site == 0) {

	$gb_nightecia = new nightecia();
		
	// Mostrar o retorno do site 
	echo $gb_nightecia->ObtemEventosNightecia($gb_flg_gravar);

}


// Eventos do Hlera na festa ou de todos os sites

/* NAO ESTA FUNCIONANDO

if ($gb_id_site == 4 || $gb_id_site == 0) {

	$gb_hlera = new hlera();
		
	// Mostrar o retorno do site
	echo $gb_hlera->ObtemEventosHlera($gb_flg_gravar);

}
*/


// Eventos do Vale1Convite ou de todos os sites

/* NAO ESTA FUNCIONANDO

if ($gb_id_site == 5 || $gb_id_site == 0) {

	$gb_vale = new vale();
	
	// Mostrar o retorno do site
	echo $gb_vale->inicializaVale($gb_flg_gravar);
	
}
*/


// Eventos do Postos Brava ou de todos os sites
if ($gb_id_site == 6 || $gb_id_site == 0) {

	$gb_postosBrava = new postosBrava();
	
	// Mostrar o retorno do site
	echo $gb_postosBrava->inicializaPostosBrava($gb_flg_gravar);
	
}

// Conectar-se ao banco
$gb_bd = new bd(false);

$gb_bd -> conecta();

// Retirar cacteres indesejados da descrição dos eventos criados anteriormente
$eventos = new eventos($gb_bd);

$eventos->retiraCaracteres();

?>


