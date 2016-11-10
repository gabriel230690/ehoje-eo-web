<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 22/02/16
 * Time: 08:52
 */

class mensagem {


    private $gb_bd;

    function mensagem ($pr_bd) {
        $this->gb_bd = $pr_bd;
    }

    function buscaMensagens($pr_id_conversa) {

        // Carregar as mensagens de determinada conversa
        $vr_sql = "SELECT m.idMensagem, m.dtMensagem, m.dsMensagem, u.dsNome " .
                    "FROM mensagem m, conversa c, usuario u " .
                   "WHERE m.idConversa = $pr_id_conversa "    .
                     "AND m.idConversa = c.idConversa "       .
                     "AND m.idUsuario  = u.idUsuario "        .
                "ORDER BY m.idMensagem ASC"; 

        $vr_results = mysql_query($vr_sql);

        $vr_rows = array();

        // Percorrer as conversas, e salvar em array
        while ($vr_row = mysql_fetch_assoc($vr_results)) {

            // Data do mensagem
            $vr_dt_mensagem =  substr($vr_row['dtMensagem'],8,2) . '/'    .
                               substr($vr_row['dtMensagem'],5,2) . '/'    .
                               substr($vr_row['dtMensagem'],0,4) . ' as ' .
                               substr($vr_row['dtMensagem'],11,5);

            // Converter o HTML em Emoctions
            $vr_ds_mensagem = emoji_html_to_unified($vr_row['dsMensagem']);

            $vr_rows[] = array ('idMensagem' => $vr_row['idMensagem'],
                                'dtMensagem' => $vr_dt_mensagem,
                                'dsMensagem' => $vr_ds_mensagem,
                                'dsNome'     => $vr_row['dsNome']);

        }

        return json_encode($vr_rows);

    }

    function insereMensagem ($pr_id_conversa, $pr_id_usuario, $pr_ds_assunto, $pr_ds_mensagem) {

        // Obter data e hora deste momento
        setlocale(LC_TIME, 'pt_BR', 'ptb');
        $vr_dt_mensagem = date("Y-m-d H:i:s");

        // Atualizar tabela de conversa
        $conversa = new conversa($this->gb_bd);

        // Se conversa nao existe ainda, criar
        if ($pr_id_conversa == 0) {

            if ($pr_ds_assunto == '' || $pr_ds_mensagem == '') {
                $vr_arr_retorno =  array('retorno'  => 'NOK' ,
                                         'mensagem' => 'O assunto e a mensagem devem estar preenchidos.');

                return json_encode($vr_arr_retorno);

            }

            // Criar conversa pois ela e' nova
            $pr_id_conversa = $conversa->criaConversa($pr_id_usuario , $pr_ds_assunto , $vr_dt_mensagem);

        }

        if ($pr_ds_mensagem == '') {
            $vr_arr_retorno =  array('retorno'  => 'NOK' ,
                                     'mensagem' => 'A mensagem deve estar preenchida.');

            return json_encode($vr_arr_retorno);
        }

        // Inserir a mensagem
        $vr_ds_campos = 'idConversa, dtMensagem, dsMensagem, idUsuario';
        $vr_ds_valores = $pr_id_conversa . ', "' . $vr_dt_mensagem . '" , "' . $pr_ds_mensagem . '" , ' . $pr_id_usuario;

        $vr_id_mensagem = $this->gb_bd->incluiRegistro('mensagem', $vr_ds_campos, $vr_ds_valores);

        // Se deu errado, retornar NOK
        if ($vr_id_mensagem > 0 == false) {

            $vr_arr_retorno = array('retorno'  => 'NOK' ,
                                    'mensagem' => 'Não foi possível incluir a mensagem. Tente novamente.');

            return json_encode($vr_arr_retorno);

        }

        // Atualizar conversa
        $conversa->atualizaConversa($pr_id_conversa, $pr_id_usuario , $vr_dt_mensagem);

        // Atualizar tabela conversa_lida
        $conversa_lida = new conversa_lida($this->gb_bd);

        $conversa_lida->marcarConversaLida($pr_id_usuario , $pr_id_conversa, 0);

        // Alertar o outro usuario sobre nova mensagem
        $this->alertaNovaMensagem($pr_id_usuario , $pr_id_conversa);

        // Retorno OK para o envio da mensagem
        $vr_arr_retorno = array('retorno'  => 'OK',
                                'mensagem' => '' );

        return json_encode($vr_arr_retorno);

    }

    function alertaNovaMensagem($pr_id_usuario , $pr_id_conversa) {

        $usuario = new usuario($this->gb_bd);

        // Se for usuario normal, verificar se o EO esta online
        if ($pr_id_usuario != 1) {

            // Destinatario é o EO
            $pr_id_usuario = 1;

            $vr_flg_ativo = json_decode($usuario->estaAtivo($pr_id_usuario, 30))->ativo;

        }  else {
            // Se for EO, obter o criador da conversa
            $conversa = new conversa($this->gb_bd);

            $pr_id_usuario = $conversa->buscaCriador($pr_id_conversa);

            // Se o EO for o criador, desconsiderar
            if ($pr_id_usuario == 1) {
                return;
            }

            $vr_flg_ativo = json_decode($usuario->estaAtivo($pr_id_usuario, 30))->ativo;
        }

        // Se o destinatario não esta ativo, enviar e-mail notificando nova mensagem
        if ($vr_flg_ativo == 0) {
            $usuario->enviarNotificacaoMensagem($pr_id_usuario);
        }

    }

}