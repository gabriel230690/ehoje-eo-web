<?php

class funplace
{

    // Nome Site
    const ENJOOY_SITE = 'https://br.enjooy.com';

    // Quantidade de dias a serem buscados
    const FUNPLACE_DIAS = 1;

    // ID do site
    const ID_FUNPLACE = 7;

    //Armazena a cidade/estado em questão para envio na inserção do evento na base
    static $gb_ds_cidade;

    static $gb_ds_estado;


    // Construtor da classe principal
    function funplace()
    {

    }

    function inicializaFunplace($pr_flg_gravar)
    {

        // Criamos um novo recurso do tipo Curl
        $vr_ch = curl_init();

        //Seta as opções da requisição curl
        curl_setopt($vr_ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($vr_ch, CURLOPT_COOKIESESSION, TRUE);
        curl_setopt($vr_ch, CURLOPT_COOKIEFILE, "cookiefile");
        curl_setopt($vr_ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

        //Busca todas as cidades onde serão buscados os eventos
        $vr_arr_cidades = $this->buscaCidades();
        $vr_arr_eventos = array();

        //Para cada cidade
        for ($row = 0; $row < count($vr_arr_cidades); $row++) {

            //Pega a data atual, cidade e estado
            self::$gb_ds_cidade = $vr_arr_cidades[$row]['dscidade'];
            self::$gb_ds_estado = $vr_arr_cidades[$row]['dsestado'];

            // Juntar os eventos do local na variavel que acumula todos os eventos
            $vr_arr_eventos[] = $this->alteraCidade($vr_arr_cidades[$row]['url'], $pr_flg_gravar);

        }

        //Libera a requisição curl
        curl_close($vr_ch);

        // Trata eventos e retornar os mesmos
        return json_encode($vr_arr_eventos);

    }

    function buscaCidades()
    {

        // Conecta no banco
        $vr_bd = new bd(false);
        $vr_bd->conecta();

        // Buscar 5 cidadeas aleatorias
        $endereco = new endereco ($vr_bd);
        $vr_arr_cidades = json_decode($endereco->carregaCidades(true));

        // Desconectar do Banco
        $vr_bd->desconecta();

        $vr_arr_cidades_2 = array();

        // Percorrer todas as cidades
        for ($i = 0; $i < count($vr_arr_cidades); $i++) {

            $vr_infos = $this->buscaCidade($vr_arr_cidades[$i]->nmCidade);
            $vr_id_cidade = $vr_infos["idCidade"];
            $vr_ds_url = $vr_infos["url"];

            if ($vr_id_cidade == "") {
                continue;
            }

            $vr_arr_descricao = array();
            $vr_arr_descricao['idcidade'] = $vr_id_cidade;
            $vr_arr_descricao['dscidade'] = $vr_arr_cidades[$i]->nmCidade;
            $vr_arr_descricao['dsestado'] = $vr_arr_cidades[$i]->nmEstado;
            $vr_arr_descricao['qtpagina'] = 1;
            $vr_arr_descricao['url'] = $vr_ds_url;
            $vr_arr_cidades_2[$i] = $vr_arr_descricao;

        }

        return $vr_arr_cidades_2;
    }

    function buscaCidade($pr_nm_cidade)
    {

        // Substituir os acentos
        $padroniza = new padroniza();
        $pr_nm_cidade = $padroniza->retiraAcentos($pr_nm_cidade);

        // Substituir o espaco em branco pelo caracter correspondente
        $pr_nm_cidade = str_replace(" ", "%20", $pr_nm_cidade);

        // Criamos um novo recurso do tipo Curl
        $vr_ch = curl_init();

        // Informar URL e outras funções ao CURL
        curl_setopt($vr_ch, CURLOPT_URL, self::ENJOOY_SITE . '/ajax/autocomplete/getcities?q=' . $pr_nm_cidade);

        // Evitar problemas com requisições HTTPS
        curl_setopt($vr_ch, CURLOPT_SSL_VERIFYPEER, false);

        // Retornar saida ao inves de imprimir
        curl_setopt($vr_ch, CURLOPT_RETURNTRANSFER, true);

        // Acessar a URL e retornar a saída
        $vr_saida = curl_exec($vr_ch);

        // Se houver algum erro na requisição Curl então, grava log e vai para o próximo site
        if (curl_error($vr_ch) != '') {

            // Armazenar o erro
            $vr_ds_erro = 'Erro na requisição CURL (buscaCidade) ' . curl_error($vr_ch);

            // Instanciar a classe do log e gerar critica
            $log = new log();

            $log->geraLog(self::ID_FUNPLACE, $vr_ds_erro);

            return false;
        }

        // Converter para JSON
        $vr_json_cidades = json_decode($vr_saida);

        // Retornar ID e Url
        return array('idCidade' => $vr_json_cidades->geonames[0]->Id,
            'url' => $vr_json_cidades->geonames[0]->Url);

    }

    function alteraCidade($pr_ds_url, $pr_flg_gravar)
    {

        // Criamos um novo recurso do tipo Curl
        $vr_ch = curl_init();

        // Informar URL e outras funções ao CURL
        curl_setopt($vr_ch, CURLOPT_URL, self::ENJOOY_SITE . '/' . $pr_ds_url);

        // Evitar problemas com requisições HTTPS
        curl_setopt($vr_ch, CURLOPT_SSL_VERIFYPEER, false);

        // Retornar saida ao inves de imprimir
        curl_setopt($vr_ch, CURLOPT_RETURNTRANSFER, true);

        // Acessar a URL e retornar a saída
        $vr_saida = curl_exec($vr_ch);

        // Se houver algum erro na requisição Curl então, grava log e vai para o próximo site
        if (curl_error($vr_ch) != '') {

            // Armazenar o erro
            $vr_ds_erro = 'Erro na requisição CURL (alteraCidade) ' . curl_error($vr_ch);

            // Instanciar a classe do log e gerar critica
            $log = new log();

            $log->geraLog(self::ID_FUNPLACE, $vr_ds_erro);

            return false;
        }

        $vr_arr_eventos[] = $this->trataDadosFunplace($vr_saida);

        return json_encode($this->trataEventos($vr_arr_eventos, $pr_flg_gravar));

    }

    function trataDadosFunplace($vr_ds_html)
    {

        $vr_pos_init = strpos($vr_ds_html, 'festa-big text-center event-item') - 12;
        $vr_ds_dados = substr($vr_ds_html, $vr_pos_init);
        $vr_pos_fim = strpos($vr_ds_dados, "next-wrapper") - 12;
        $vr_ds_dados = substr($vr_ds_dados, 0, $vr_pos_fim);

        $vr_ds_dados = str_replace("&","e_comercial",$vr_ds_dados);
        $vr_ds_dados = str_replace('alt="', 'alt = ""> </img>', $vr_ds_dados);
        $vr_ds_dados = "<div>" . $vr_ds_dados;

        /*
        $fp = fopen("bloco_3.xml", "a");

        // Escreve "exemplo de escrita" no bloco1.txt
        $escreve = fwrite($fp, $vr_ds_dados);

        fclose($fp);
        */

        return $vr_ds_dados;

    }

    // Ler todos os eventos obtidos e fazer a gravacão dos mesmos na base
    function trataEventos($pr_arr_eventos, $pr_flg_gravar)
    {

        // Array para devolver os eventos
        $vr_arr_eventos = array();

        for ($i = 0; $i < count($pr_arr_eventos); $i++) {


            // Obter a String XML
            $vr_ds_dados = $pr_arr_eventos[$i];

            if ($vr_ds_dados == '') {
                continue;
            }

            // Converter em um objeto XML
            $vr_xml_eventos = new DomDocument;
            $vr_xml_eventos->loadXML(utf8_encode($vr_ds_dados));

            // Se não converte para o xml
            if ($vr_xml_eventos == false) {

                // Armazenar o erro
                $vr_ds_erro = 'Erro na conversão para XML (trataEventos) FUNPLACE';

                // Instanciar a classe do log e gerar critica
                $log = new log();

                $log->geraLog(self::ID_FUNPLACE, $vr_ds_erro);

                // Proximo XML
                continue;

            }

            // Tratar os dados do XML e salvar em um array para retornar os eventos
            $vr_arr_eventos[] = $this->trataXMLFunplace($vr_xml_eventos, $pr_flg_gravar);


        }

        return $vr_arr_eventos;

    }


    function trataXMLFunplace($pr_xml_eventos, $pr_flg_gravar)
    {

        // Array para devolver os eventos
        $vr_arr_eventos = array();

        $vr_bd = new bd(false);
        $vr_bd->conecta();

        $vr_elementos = $pr_xml_eventos->getElementsByTagName("div");

        foreach ($vr_elementos as $vr_elemento) {

            try {

                if ($vr_elemento->getAttribute("class") != "hover-wrapper") {
                    continue;
                }

                // Obter os dados do evento
                $vr_ds_link    = $vr_elemento->getElementsByTagName("a")->item(0)->getAttribute("href");
                $vr_ds_local   = $vr_elemento->getElementsByTagName("a")->item(0)->textContent;
                $vr_ds_atracao = $vr_elemento->getElementsByTagName("a")->item(1)->textContent;

                // Obter DIV da data / hora
                $vr_div_data = $vr_elemento->getElementsByTagName("div");

                foreach ($vr_div_data as $vr_obj_data) {

                    if ($vr_obj_data->getAttribute("class") != "data" &&
                        $vr_obj_data->getAttribute("class") != "hora"
                    ) {
                        continue;
                    }

                    $vr_tp_info = $vr_obj_data->getAttribute("class");

                    // Obter a data do evento
                    if ($vr_tp_info == 'data') {

                        $vr_dt_evento = $vr_obj_data->getElementsByTagName('span')->item(1)->textContent;

                        if (substr($vr_dt_evento, 0, 4) == "Hoje") {
                            $vr_dt_evento = date('Y-m-d');
                        } else
                            if (substr($vr_dt_evento, 0, 5) == "Amanh") {
                                $vr_dt_evento = date('Y-m-d', strtotime("+1 days"));
                            } else {
                                $vr_dt_evento = date("Y") . "/" . substr($vr_dt_evento, 3, 2) . "/" . substr($vr_dt_evento, 0, 2);
                            }
                    } else { // Obter a hora do evento

                        $vr_hr_evento = $vr_obj_data->getElementsByTagName('span')->item(1)->textContent;

                        if (strrpos($vr_hr_evento, ":") > 0) {
                            $vr_hr_evento .= ":00";
                        }

                    }

                }

                // Obter a imagem do evento
                $vr_ds_imgcover = str_replace('e_comercialamp;','&',$vr_elemento->getElementsByTagName("img")->item(0)->getAttribute("src"));
                $vr_id_facebook = 0;

                // Se tem que gravar
                if ($pr_flg_gravar == 1) {

                    // Instanciar a classe de events
                    $evento = new eventos($vr_bd);

                    // Inserir o evento e salvar o retorno
                    $vr_ds_retorno = $evento->insereEvento(self::ENJOOY_SITE, $vr_dt_evento, $vr_ds_local, $vr_ds_link, "", $vr_ds_atracao, self::$gb_ds_cidade, self::$gb_ds_estado, 'Brasil', $vr_id_facebook, $vr_hr_evento, $vr_ds_imgcover);

                }

                // Salvar dados do evento no Array para retorno
                $vr_arr_eventos[] = array('evento'  => $vr_ds_atracao,
                                          'local'   => $vr_ds_local,
                                          'data'    => $vr_dt_evento,
                                          'retorno' => $vr_ds_retorno);


            } catch (Exception $ex) {

                // Armazenar o erro
                $vr_ds_erro = 'Erro em evento individual (trataXMLFunplace) ' . $ex->getMessage();

                // Instanciar a classe do log e gerar critica
                $log = new log();

                $log->geraLog(self::ID_FUNPLACE, $vr_ds_erro);

                continue;
            }

        }

        // Desconectar
        $vr_bd->desconecta();

        return $vr_arr_eventos;

    }

    function trazIdFacebook($pr_ds_link)
    {
        // Criamos um novo recurso do tipo Curl
        $vr_ch = curl_init();

        // Informar URL e outras funções ao CURL
        curl_setopt($vr_ch, CURLOPT_URL, $pr_ds_link);

        // Evitar problemas com requisições HTTPS
        curl_setopt($vr_ch, CURLOPT_SSL_VERIFYPEER, false);

        // Retornar saida ao inves de imprimir
        curl_setopt($vr_ch, CURLOPT_RETURNTRANSFER, true);

        // Acessar a URL e retornar a saída
        $vr_ds_saida = curl_exec($vr_ch);

        // Obter o ID do Facebook
        $vr_pos_init = strpos($vr_ds_saida, 'confirmar-presenca btnAttending caps');
        $vr_ds_dados = substr($vr_ds_saida, $vr_pos_init);
        $vr_pos_init = strpos($vr_ds_dados, 'data-eid="') + 10;
        $vr_ds_dados = substr($vr_ds_dados, $vr_pos_init);
        $vr_pos_fim  = strpos($vr_ds_dados, 'data-attending') - 2;

        $vr_id_facebook = substr($vr_ds_dados, 0, $vr_pos_fim);

        return $vr_id_facebook;

    }

}

?>