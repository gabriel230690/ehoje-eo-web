<?php

// Incluir as classes do pacote Modelo
include ('../model/bd.php');
include ('../model/endereco.php');
include ('../model/constantes.php');
include ('../model/log.php');

// Instanciar a classe do Banco
$gb_bd = new bd(false);

// Conectar-se ao banco 
$gb_bd -> conecta();

// Instanciar a classe Endereco
$gb_endereco = new endereco($gb_bd);

// Carregar as cidades num JSON e retornar o mesmo
echo $gb_endereco->carregaCidades( false );

// Desconectar-se do banco
$gb_bd -> desconecta();
		
?>