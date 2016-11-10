<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 22/02/16
 * Time: 12:43
 */

class usuario {

    private $gb_bd;

    function usuario ($pr_bd) {
        $this->gb_bd = $pr_bd;
    }

    function criaConta($pr_ds_nome, $pr_ds_mail, $pr_nr_ddd, $pr_nr_telefone ) {

        if ($pr_ds_nome == '' || $pr_ds_mail == '') {
            $vr_arr_retorno = array('retorno'  => 'NOK' ,
                                    'mensagem' => 'O nome e o e-mail são obrigatórios.');

            return json_encode($vr_arr_retorno);
        }

        $vr_ds_campos = 'dsNome, dsEmail, nrDDD, nrTelefone, flgAdministra';
        $vr_ds_valores = '"' . $pr_ds_nome . '" , "' . $pr_ds_mail . '" ,' . $pr_nr_ddd . ',' . $pr_nr_telefone . ', 0';

        $vr_id_usuario = $this->gb_bd->incluiRegistro('usuario', $vr_ds_campos, $vr_ds_valores);

        $vr_arr_retorno = array('retorno'   => ($vr_id_usuario > 0 ) ? 'OK' : 'NOK' ,
                                'mensagem'  => ($vr_id_usuario > 0 ) ? ''   : 'Não foi possível incluir a conta. Tente novamente.', 
                                'idUsuario' =>  $vr_id_usuario); 

        return json_encode($vr_arr_retorno);

    }

    function verificaLogin ($pr_ds_email) {

        $vr_sql = "SELECT * " .
            "FROM usuario u " .
            "WHERE u.dsEmail = '$pr_ds_email'";

        $vr_results = mysql_query($vr_sql);

        // Trazer dados do usuario 
        while ($vr_row = mysql_fetch_assoc($vr_results)) {

            $vr_arr_retorno  = array ('retorno'        => 'OK',
                                      'idUsuario'      => $vr_row['idUsuario'],
                                      'dsNome'         => $vr_row['dsNome'],
                                      'flgAdministra'  => $vr_row['flgAdministra']);

        }

        if ( count($vr_arr_retorno) == 0 ) {
            $vr_arr_retorno = array('retorno'  => 'NOK' ,
                                    'mensagem' => 'E-mail não cadastrado. Verifique os dados e tente novamente.');
        }

        return json_encode($vr_arr_retorno);

    }

    function gravaUltimoAcesso( $pr_id_usuario ) {

        // Obter data e hora deste momento
        setlocale(LC_TIME, 'pt_BR', 'ptb');
        $vr_dt_acesso = date("Y-m-d H:i:s");

        // Alterar a conversa para + 1 mensagem, ultimo usuario e data
        $vr_tabela          = "usuario";
        $vr_campos_valores  = "dtUltAcesso = '$vr_dt_acesso'";
        $vr_filtro          = "idUsuario";
        $vr_valor           = $pr_id_usuario;

        $this->gb_bd-> editaRegistro($vr_tabela, $vr_campos_valores, $vr_filtro, $vr_valor);

    }

    function estaAtivo( $pr_id_usuario , $pr_nr_minutos ) {

        // Buscar a hora de ultimo acesso de determinado usuario
        $vr_sql = "SELECT dtUltAcesso " .
                    "FROM usuario u "   .
                   "WHERE u.idUsuario = $pr_id_usuario";

        $vr_results = mysql_query($vr_sql);

        $vr_row = mysql_fetch_assoc($vr_results);

        $vr_dt_acesso = $vr_row['dtUltAcesso'];

        $vr_dt_atual = new DateTime('now');

        $vr_dt_acesso_2 = DateTime::createFromFormat('Y-m-d H:i:s', $vr_dt_acesso);

        $vr_diff_datas = $vr_dt_atual->diff($vr_dt_acesso_2);

        // Se acessou hoje, verificar o horario
        if ($vr_diff_datas->y == 0 && $vr_diff_datas->m == 0 && $vr_diff_datas->d == 0) {

            // Se acessou há menos de XX minutos (parametro) , retornar ativo senao inativo
            if ($vr_diff_datas->h == 0 && $vr_diff_datas->i <= $pr_nr_minutos) {
                return json_encode (array('ativo'  => 1));

            }

        }

        return json_encode (array('ativo'  => 0));

    }

    function destinatarioAtivo ($pr_id_conversa, $pr_id_usuario) {

        // Se for usuario normal, verificar se o EO esta online
        if ($pr_id_usuario != 1) {

            return $this->estaAtivo( 1 , 5 ) ;

        } else {

            // Se for EO, obter o criador da conversa
            $conversa = new conversa($this->gb_bd);

            $pr_id_usuario = $conversa->buscaCriador($pr_id_conversa);

            return $this->estaAtivo($pr_id_usuario, 5);
        }

    }

    function enviarNotificacaoMensagem($pr_id_usuario){

        // Buscar o e-mail do usuario
        $vr_sql = "SELECT dsEmail, dsNome "  .
                    "FROM usuario u "        .
                   "WHERE u.idUsuario = $pr_id_usuario";

        $vr_results = mysql_query($vr_sql);

        $vr_row = mysql_fetch_assoc($vr_results);

        // Email e nome
        $vr_ds_email = $vr_row['dsEmail'];
        $vr_ds_nome  = $vr_row['dsNome'];

        // Enviar o e-mail para o usuario notificando a nova mensagem
        $vr_ds_mensagem = '<html><body> Olá, ' . $vr_ds_nome  . '. <br/><br/> Você possui uma nova mensagem ' .
                          'no app EO. Acesse o aplicativo para visualizar.  <br/><br/> Atenciosamente  </body></html>';

        $vr_headers  = "MIME-Version: 1.0" . "\r\n";
        $vr_headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $vr_headers .= "From: contato@ehojeapp.com.br";

        mail($vr_ds_email,"Nova mensagem no EO",$vr_ds_mensagem, $vr_headers );

    }

}