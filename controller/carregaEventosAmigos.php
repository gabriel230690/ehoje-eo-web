<?php

// Incluir as classes do pacote Modelo e Controller
include ('gravacao/sites/facebook.php');
include ('../model/bd.php');
include ('../model/endereco.php');
include ('../model/distancia.php');
include ('../model/local.php');
include ('../model/eventos.php');
include ('../model/participante.php');

// Obter os valores enviados via POST
$gb_ds_cidade = $_POST["nmCidade"];
$gb_ds_amigos = $_POST["dsAmigos"];


// Instanciar a classe Local
$gb_facebook = new facebook();

// Carregar os locais
echo $gb_facebook->buscaEventosAmigos($gb_ds_cidade, $gb_ds_amigos);

?>