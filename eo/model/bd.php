<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 22/02/16
 * Time: 08:25
 */


class bd {

    private $servidor;
    private $usuario;
    private $senha;
    private $banco;
    private $conexao;


    function bd() {

        $this -> servidor = "200.147.61.72";
        $this -> usuario = "eonline";
        $this -> senha = "antonio-56";
        $this -> banco = "eonline";

    }

    function conecta() {

        $this -> conexao = mysql_connect($this -> servidor, $this -> usuario, $this -> senha);
        mysql_select_db($this -> banco);
        mysql_set_charset('UTF8', $this->conexao);

    }

    function desconecta() {

        mysql_close($this -> conexao);

    }

    function incluiRegistro($par_tabela, $par_campos, $par_valores) {

        $vr_sql = 'insert into ' . $par_tabela . ' ( ' . $par_campos . ' ) VALUES (' . $par_valores . ' )';

        mysql_query($vr_sql, $this -> conexao);

        return  mysql_insert_id($this -> conexao);

    }

    function editaRegistro($par_tabela, $par_campos_valores, $par_filtro, $par_valor) {

        $vr_sql = "UPDATE $par_tabela SET $par_campos_valores where $par_filtro = $par_valor";
         
        mysql_query($vr_sql);
    }

    function deletaRegistro($par_tabela, $par_campo, $par_id) {

        $vr_sql = "DELETE FROM $par_tabela where $par_campo = '$par_id'";

        mysql_query($vr_sql);

        return mysql_affected_rows();
    }

}
?>