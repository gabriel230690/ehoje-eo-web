<?php

// Incluir as classes do pacote Modelo e Controller
include ('gravacao/sites/facebook.php');
include ('../model/bd.php');
include ('../model/endereco.php');
include ('../model/distancia.php');
include ('../model/local.php');
include ('../model/eventos.php'); 

// Obter os valores enviados via POST
$gb_ds_cidade = $_POST["nmCidade"];

// Instanciar a classe do Banco
$gb_bd = new bd(false);

// Conectar-se ao banco 
$gb_bd -> conecta();

// Instanciar a classe Local
$gb_local = new local($gb_bd);

// Carregar os locais
echo $gb_local->trazLocaisAbertos( $gb_ds_cidade );

// Desconectar-se do banco
$gb_bd -> desconecta();
		
?>