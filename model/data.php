<?php

class data
{

    private $bd;

    // Construtor principal da classe
    function data()
    {

    }

    // Montar a data no formato necessario para inserção no banco (Y-M-D)
    function montaData($pr_ds_dia, $pr_ds_mes, $pr_nr_ano)
    {

        $vr_nr_mes = $this->trazMes($pr_ds_mes);
        $vr_ds_dia = $pr_ds_dia;

        // Bug esquicito. Se a data for menor a 10, retirar zero da esquerda
        if (strlen($vr_ds_dia) == 2) {
            if (substr($vr_ds_dia, 0, 1) == "0") {
                $vr_ds_dia = substr($vr_ds_dia, 1, 1);
            }
        }

        $vr_ds_dia = (strlen($vr_ds_dia) == 1) ? "0" . $vr_ds_dia : $vr_ds_dia;
        $vr_nr_mes = (strlen($vr_nr_mes) == 1) ? "0" . $vr_nr_mes : $vr_nr_mes;

        $vr_dt_evento = $pr_nr_ano . "-" . $vr_nr_mes . "-" . trim($vr_ds_dia);

        return $vr_dt_evento;

    }

    // Trazer o numero do mes correspondente a descrição enviada
    function trazMes($pr_ds_mes)
    {

        $pr_ds_mes = trim(strtolower($pr_ds_mes));

        if ($pr_ds_mes == "janeiro" || $pr_ds_mes == "jan") {
            return 1;
        }

        if ($pr_ds_mes == "fevereiro" || $pr_ds_mes == "feb" || $pr_ds_mes == "fev") {
            return 2;
        }

        if ($pr_ds_mes == "março" || $pr_ds_mes == "mar") {
            return 3;
        }

        if ($pr_ds_mes == "abril" || $pr_ds_mes == "apr" || $pr_ds_mes == "abr") {
            return 4;
        }

        if ($pr_ds_mes == "maio" || $pr_ds_mes == "may" || $pr_ds_mes == 'mai') {
            return 5;
        }

        if ($pr_ds_mes == "junho" || $pr_ds_mes == "jun" || $pr_ds_mes == "june") {
            return 6;
        }

        if ($pr_ds_mes == "julho" || $pr_ds_mes == "jul") {
            return 7;
        }

        if ($pr_ds_mes == "agosto" || $pr_ds_mes == "aug" || $pr_ds_mes == 'ago') {
            return 8;
        }

        if ($pr_ds_mes == "setembro" || $pr_ds_mes == "sep" || $pr_ds_mes == 'set') {
            return 9;
        }

        if ($pr_ds_mes == "outubro" || $pr_ds_mes == "oct" || $pr_ds_mes == 'out') {
            return 10;
        }

        if ($pr_ds_mes == "novembro" || $pr_ds_mes == "nov") {
            return 11;
        }

        if ($pr_ds_mes == "dezembro" || $pr_ds_mes == "dec" || $pr_ds_mes == 'dez') {
            return 12;
        }

    }

    function incrementaDiasData($pr_dt_incrementa, $pr_nr_dias)
    {

        $vr_nr_ano = substr($pr_dt_incrementa, 4, 4);
        $vr_nr_mes = substr($pr_dt_incrementa, 2, 2);
        $vr_nr_dia = substr($pr_dt_incrementa, 0, 2);
        $vr_dt_incrementa = mktime(0, 0, 0, $vr_nr_mes, $vr_nr_dia + $pr_nr_dias, $vr_nr_ano);

        return strftime("%d%m%Y", $vr_dt_incrementa);

    }


    function verificaExecucao($pr_ds_programa)
    {

        // Obter data e hora deste momento
        setlocale(LC_TIME, 'pt_BR', 'ptb');
        $vr_dt_atu = date("Y-m-d H:i:s");
        $vr_hr_atu = date("H");

        // Só rodar apos a meia noite e até o meio dia
        if ($vr_hr_atu >= 12) {
            return false;
        }

        // Conectar ao BD
        $this->bd = new bd (false);
        $this->bd->conecta();

        // Verifica quando foi a ultima vez que rodou
        $vr_id_cron = $this->verificaCron($pr_ds_programa, $vr_dt_atu);

        // Se rodou ha menos de 15 min, abortar
        if (!$vr_id_cron) {
            $this->bd->desconecta();
            return false;
        }

        // Atualizar registro da cron com a ultima execucao
        $vr_tabela = "cron";
        $vr_campos_valores = "dtExecucao = '$vr_dt_atu'";
        $vr_filtro = "idCron";
        $vr_valor = $vr_id_cron;

        $this->bd->editaRegistro($vr_tabela, $vr_campos_valores, $vr_filtro, $vr_valor);

        // Desconectar do BD
        $this->bd->desconecta();

        return true;

    }

    function verificaCron($pr_ds_programa, $pr_dt_atu)
    {

        // Obter a ultima vez que rodou
        $vr_sql = "SELECT idCron, dtExecucao " .
            "FROM cron c " .
            "WHERE c.dsPrograma = '$pr_ds_programa'";

        $vr_results = mysql_query($vr_sql);

        // Se deu erro na consulta, não rodar
        if (!$vr_results) {
            return false;
        }

        $vr_row = mysql_fetch_assoc($vr_results);

        // Se nao achou o programa, nao rodar
        if (!$vr_row) {
            return false;
        }

        // Converter em Datetime ambas datas
        $pr_dt_atu = new DateTime($pr_dt_atu);
        $vr_dt_exec = new DateTime($vr_row["dtExecucao"]);

        // Verificar quanto tempo de diferenca entre hora atual e ultima execucao deste programa
        $vr_obj_dif = $vr_dt_exec->diff($pr_dt_atu);

        // Se rodou há menos de 15 min, abortar
        if ($vr_obj_dif->d == 0 && $vr_obj_dif->h == 0 && $vr_obj_dif->i < 15) {
            return false;
        }

        return $vr_row["idCron"];

    }

}

?>