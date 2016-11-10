<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 02/03/16
 * Time: 11:11
 */

class conversa_lida {

    private $gb_bd;

    function conversa_lida($pr_bd) {
        $this->gb_bd = $pr_bd;
    }

    function verificaNovaMensagem($pr_id_usuario , $pr_id_conversa, $pr_qt_mensanges) {

        // Carregar as conversas
        $vr_sql = "SELECT cl.qtMensagensLidas "            .
                    "FROM conversa_lida cl "               .
                   "WHERE cl.idUsuario = $pr_id_usuario "  .
                     "AND cl.idConversa = $pr_id_conversa ";

        $vr_results = mysql_query($vr_sql);

        // Verificar informação da mensagem lida
        while ($vr_row = mysql_fetch_assoc($vr_results)) {

            $vr_flg_nova = ($vr_row['qtMensagensLidas'] != $pr_qt_mensanges);

            return $vr_flg_nova;
        }

        // Se chegou aqui é porque não leu nenhuma mensagem ainda.
        return true;

    }

    function marcarConversaLida($pr_id_usuario , $pr_id_conversa, $pr_qt_mensanges) {

        // Buscar o ID da conversa_lida
        $vr_sql = "SELECT cl.idLida "                      .
                    "FROM conversa_lida cl "               .
                   "WHERE cl.idUsuario = $pr_id_usuario "  .
                     "AND cl.idConversa = $pr_id_conversa ";

        $vr_results = mysql_query($vr_sql);

        // Informação da mensagem lida
        $vr_row = mysql_fetch_assoc($vr_results);

        // Se nao leu nehuma mensagem ainda, cria
        if ($vr_row == false) {

            $vr_ds_campos = 'idUsuario, idConversa, qtMensagensLidas';
            $vr_ds_valores = $pr_id_usuario . ',' . $pr_id_conversa . ', 1';

            $this->gb_bd->incluiRegistro('conversa_lida', $vr_ds_campos, $vr_ds_valores);

        } else { // Senao atualiza

            // Se não enviou a quantidade, colocar + 1
            $pr_qt_mensanges = ($pr_qt_mensanges == 0) ?  'qtMensagensLidas + 1' : $pr_qt_mensanges;

            // Atualizar a conversa com a quantidade de mensagens lidas
            $vr_tabela          = "conversa_lida";
            $vr_campos_valores  = "qtMensagensLidas = $pr_qt_mensanges";
            $vr_filtro          = "idLida";
            $vr_valor           = $vr_row['idLida'];

            $this->gb_bd->editaRegistro($vr_tabela, $vr_campos_valores, $vr_filtro, $vr_valor);

        }

    }



}