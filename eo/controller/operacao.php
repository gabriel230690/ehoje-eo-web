<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 22/02/16
 * Time: 08:17
 */

include('../model/bd.php');
include('../model/conversa.php');
include('../model/conversa_lida.php');
include('../model/mensagem.php');
include('../model/usuario.php');
include('../model/emoji.php');

// Parametros para as consultas / alteracao de dados
$gb_nr_operacao    = $_POST['nrOperacao'];
$gb_id_conversa    = $_POST['idConversa'];
$gb_id_usuario     = $_POST['idUsuario'];
$gb_ds_assunto     = $_POST['dsAssunto'];
$gb_ds_mensagem    = $_POST['dsMensagem'];
$gb_flg_administra = $_POST['flgAdministra'];
$gb_ds_nome        = $_POST['dsNome'];
$gb_ds_email       = $_POST['dsEmail'];
$gb_nr_DDD         = $_POST['nrDDD'];
$gb_nr_telefone    = $_POST['nrTelefone'];
$gb_qt_mensanges   = $_POST['qtMensagens'];

// Converter para HTML para gravar na base de dados (Por causa dos emoctions)
$gb_ds_assunto  = str_replace('"','\"',emoji_unified_to_html($gb_ds_assunto));
$gb_ds_mensagem = str_replace('"','\"',emoji_unified_to_html($gb_ds_mensagem));

// Conectar ao BD
$gb_bd = new bd();
$gb_bd->conecta();

// Buscar as perguntas / conversas
if ($gb_nr_operacao == 1) {

    $conversa = new conversa($gb_bd);

    echo $conversa->buscaConversas($gb_id_usuario , $gb_flg_administra); 

} // Buscar as mensagem de determinada conversa
else if ($gb_nr_operacao == 2) {

    $mensagem = new mensagem($gb_bd);

    echo $mensagem->buscaMensagens($gb_id_conversa);

} // Deletar determinada conversa
else if ($gb_nr_operacao == 3) {

    $conversa = new conversa($gb_bd);

    echo $conversa->deletaConversa($gb_id_conversa);

} // Inclui mensagem / conversa
else if ($gb_nr_operacao == 4) {

    $mensagem = new mensagem($gb_bd);

    echo $mensagem->insereMensagem($gb_id_conversa , $gb_id_usuario, $gb_ds_assunto, $gb_ds_mensagem );

}  // Criacao de conta
else if ($gb_nr_operacao == 5) {

    $usuario = new usuario($gb_bd);

    echo $usuario->criaConta($gb_ds_nome , $gb_ds_email , $gb_nr_DDD, $gb_nr_telefone);

} // Login da conta
else if ($gb_nr_operacao == 6) {

    $usuario = new usuario($gb_bd);

    echo $usuario->verificaLogin($gb_ds_email);

} // Consulta de conversa lida
else if ($gb_nr_operacao == 7) {

    $conversa_lida = new conversa_lida($gb_bd);

    echo $conversa_lida->verificaNovaMensagem($gb_id_usuario , $gb_id_conversa, $gb_qt_mensanges);

}
// Marcar que determinada conversa já foi lida por um usuario
else if ($gb_nr_operacao == 8) {

    $conversa_lida = new conversa_lida($gb_bd);

    echo $conversa_lida->marcarConversaLida($gb_id_usuario , $gb_id_conversa, $gb_qt_mensanges);

}
// Verificar se o EO esta online
else if ($gb_nr_operacao == 9) {

    $usuario = new usuario($gb_bd);

    echo $usuario->estaAtivo( 1 , 30 );

}
// Verifica e retorna se o destinatario da conversa esta ativo
else if ($gb_nr_operacao == 10) {

    $usuario = new usuario($gb_bd);

    echo $usuario->destinatarioAtivo($gb_id_conversa , $gb_id_usuario);

}

// Para cada requisicao, se tiver o usuario, gravar ultimo acesso
if ($gb_id_usuario != "") {

    $usuario = new usuario($gb_bd);

    $usuario->gravaUltimoAcesso($gb_id_usuario);

}

$gb_bd->desconecta();