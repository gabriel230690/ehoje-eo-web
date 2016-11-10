<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 22/02/16
 * Time: 08:23
 */

class conversa {


    private $gb_bd;

    function conversa ($pr_bd) {
        $this->gb_bd = $pr_bd;
    }

    function buscaConversas($pr_id_usuario , $pr_flg_administra) {

        // Carregar as conversas
        $vr_sql = "SELECT c.* , u.dsNome dsNomeAutor , u2.dsNome dsNomeUlt "  .
                   "FROM conversa c, usuario u , usuario u2 " .
                  "WHERE c.idUsuario = u.idUsuario "          .
                    "AND c.idUltUsuario = u2.idUsuario "      .
               "ORDER BY c.dtUltMensagem DESC"; 

        $vr_results = mysql_query($vr_sql);

        $vr_rows = array();

        // Percorrer as conversas, e salvar em array
        while ($vr_row = mysql_fetch_assoc($vr_results)) {

            $vr_flg_novamensagem = true;

            if ($pr_id_usuario == '') {  // Se não esta logado, sem novas mensagens
                $vr_flg_novamensagem = false;
            } else if ($pr_flg_administra !=  1) { // Se esta logado, mas não é administrador
                if ($pr_id_usuario != $vr_row['idUsuario']) { // Se não é mensagem dele, sem novas mensagens
                    $vr_flg_novamensagem = false;
                }
            }

            // Se entrar até aqui, é porque esta logado e (é administrador ou é usuario comum na sua pergunta).
            if ($vr_flg_novamensagem) {

                // Verificar se realmente tem alguma mensagem nova para o usuario ou administrador
                $conversa_lida = new conversa_lida($this->gb_bd);

                $vr_flg_novamensagem = $conversa_lida->verificaNovaMensagem($pr_id_usuario , $vr_row['idConversa'], $vr_row['qtMensagens']);

            }

           // Data da criação da conversa
           $vr_dt_criacao =  substr($vr_row['dtCriacao'],8,2) . '/'    .
                             substr($vr_row['dtCriacao'],5,2) . '/'    .
                             substr($vr_row['dtCriacao'],0,4) . ' as ' .
                             substr($vr_row['dtCriacao'],11,5);

            // Data da ultima mensagem
            $vr_dt_ultmensagem = substr($vr_row['dtUltMensagem'],8,2) . '/'    .
                                 substr($vr_row['dtUltMensagem'],5,2) . '/'    .
                                 substr($vr_row['dtUltMensagem'],0,4) . ' as ' .
                                 substr($vr_row['dtUltMensagem'],11,5);

            // Converter o HTML em Emoctions
            $vr_ds_assunto = emoji_html_to_unified($vr_row['dsAssunto']);

            $vr_rows[] = array ('idConversa'      => $vr_row['idConversa'],
                                'idUsuario'       => $vr_row['idUsuario'],
                                'dsNomeAutor'     => $vr_row['dsNomeAutor'],
                                'dsNomeUlt'       => $vr_row['dsNomeUlt'],
                                'dsAssunto'       => $vr_ds_assunto,
                                'dtCriacao'       => $vr_dt_criacao,
                                'dtUltMensagem'   => $vr_dt_ultmensagem,
                                'qtMensagens'     => $vr_row['qtMensagens'],
                                'flgNovaMensagem' => $vr_flg_novamensagem);

        }

        return json_encode($vr_rows);

    }

    function deletaConversa ($pr_id_conversa) {

        $vr_flg_deletou = $this->gb_bd->deletaRegistro('conversa', 'idConversa', $pr_id_conversa);

        $vr_arr_retorno = array('retorno'  => ($vr_flg_deletou == 1) ? 'OK' : 'NOK' ,
                                'mensagem' => ($vr_flg_deletou == 1) ? '' : 'Não foi possível excluir a conversa. Tente novamente.');

        return json_encode($vr_arr_retorno);

    }

    function criaConversa ($pr_id_usuario , $pr_ds_assunto , $pr_dt_mensagem) {

        // Insere registro de conversa
        $vr_ds_campos = 'idUsuario, dsAssunto, dtCriacao, idUltUsuario';

        $vr_ds_valores = $pr_id_usuario . ', "' . $pr_ds_assunto . '" , "' . $pr_dt_mensagem . '" , ' . $pr_id_usuario;

        $vr_id_conversa = $this->gb_bd->incluiRegistro('conversa', $vr_ds_campos, $vr_ds_valores);

        return $vr_id_conversa;

    }

    function atualizaConversa($pr_id_conversa, $pr_id_usuario , $pr_dt_mensagem) {

        // Alterar a conversa para + 1 mensagem, ultimo usuario e data
        $vr_tabela          = "conversa";
        $vr_campos_valores  = "qtMensagens = qtMensagens + 1, dtUltMensagem = '$pr_dt_mensagem' , idUltUsuario = $pr_id_usuario";
        $vr_filtro          = "idConversa";
        $vr_valor           = $pr_id_conversa;

        $this->gb_bd-> editaRegistro($vr_tabela, $vr_campos_valores, $vr_filtro, $vr_valor);

    }

    function buscaCriador($pr_id_conversa) {

        // Buscar o ID do criador da conversa
        $vr_sql = "SELECT c.idUsuario " .
                    "FROM conversa c "  .
                   "WHERE c.idConversa = $pr_id_conversa";

        $vr_results = mysql_query($vr_sql);

        $vr_row = mysql_fetch_assoc($vr_results);

        return $vr_row['idUsuario'];
    }


}