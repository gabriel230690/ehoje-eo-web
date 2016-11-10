<?php

class facebook
{

    // API do Facebook
    const FACEBOOK_API = 'https://graph.facebook.com/v2.1/';

    // Identificado do "e hoje?" com o Facebook
    const FACEBOOK_ID_APP = '165374886992771';

    // Token para o Facebook
    const FACEBOOK_TOKEN = '675e62d4e851f1bd10b30e0e3187a793';

    // Nome do site
    const FACEBOOK_SITE = 'www.facebook.com/events';

    // ID do site
    const ID_FACEBOOK = 8;

    private $bd;


    // Construtor principal da classe
    function facebook()
    {


    }


    // Obter os eventos dos locais que tenham ID do face
    // cadastrado e incluir os eveos na base de dados.
    function obtemEventosPages($pr_flg_gravar)
    {

        // Conectar ao BD
        $vr_bd = new bd(false);
        $vr_bd->conecta();

        // Instanciar a classe local
        $local = new local($vr_bd);

        // Obter os locais que tenham o ID da página do Facebook cadastrado
        $vr_arr_locais = $local->carregaFanpages();

        $vr_bd->desconecta();

        // Armazenar todos os eventos
        $vr_arr_eventos = array();

        // Percorrer os locais e chamar o método
        // responsável  por procurar os eventos do mesmo
        while (list($key, $vr_idFanpage) = each($vr_arr_locais)) {

            // Juntar os eventos do local na variavel que acumula todos os eventos
            $vr_arr_eventos[] = $this->obtemEventosPagina($vr_idFanpage);

        }

        // Trata eventos e retornar os mesmos
        return json_encode($this->trataEventos($vr_arr_eventos, $pr_flg_gravar));

    }


    // Obter os eventos de determinada página do face (casa noturna)
    function obtemEventosPagina($pr_id_fanpage)
    {

        // Criamos um novo recurso do tipo Curl
        $vr_ch = curl_init();

        // Informar URL e outras funções ao CURL
        curl_setopt($vr_ch, CURLOPT_URL, self::FACEBOOK_API . $pr_id_fanpage . '/events?access_token=' . self::FACEBOOK_ID_APP . '|' . self::FACEBOOK_TOKEN);

        // Evitar problemas com requisições HTTPS
        curl_setopt($vr_ch, CURLOPT_SSL_VERIFYPEER, false);

        // Certificado para requisições HTTPS
        curl_setopt($vr_ch, CURLOPT_CAINFO, dirname("cacert") . "/cacert.pem");

        // Retornar saida ao inves de imprimir
        curl_setopt($vr_ch, CURLOPT_RETURNTRANSFER, true);

        // Acessar a URL e retornar a saída
        $vr_saida = curl_exec($vr_ch);

        // Se houver algum erro na requisição Curl então, grava log e vai para o próximo site
        if (curl_error($vr_ch) != '') {

            // Armazenar o erro
            $vr_ds_erro = 'Erro na requisição CURL ' . curl_error($vr_ch);

            // Instanciar a classe do log e gerar critica
            $log = new log();

            $log->geraLog(self::ID_FACEBOOK,  $vr_ds_erro);

            // Liberar recurso
            curl_close($vr_ch);

            return;
        }

        // Liberar recurso
        curl_close($vr_ch);

        // Retornar a String com os eventos
        return $vr_saida;

    }

    // Ler todos os eventos obtidos e fazer a gravacão dos mesmos na base
    function trataEventos($pr_arr_eventos, $pr_flg_gravar)
    {

        // Array para devolver os eventos
        $vr_eventos = array();
        $vr_dt_atual = time();

        $vr_bd = new bd(false);
        $vr_bd->conecta();

        // Percorrer todos os JSON de todos os locais
        for ($i = 0; $i < count($pr_arr_eventos); $i++) {

            $vr_evento = json_decode($pr_arr_eventos[$i]);

            if (is_array($vr_evento->data) == false) {
                continue;
            }

            // Percorrer todos os eventos do local corrente
            foreach ($vr_evento->data as $item_evento) {

                // Dados do evento
                $vr_ds_atracao = $item_evento->name;
                $vr_dt_evento = strtotime(str_replace("T", "", substr($item_evento->start_time, 0, 10)));
                $vr_hr_evento = substr($item_evento->start_time, 11, 8);
                $vr_ds_local = $item_evento->location;
                $vr_id_evento = $item_evento->id;

                // Se o evento já aconteceu, desconsiderar
                if ($vr_dt_atual > $vr_dt_evento) {
                    continue;
                }

                // Data formatada para mostrar/salvar
                $vr_dt_evento = substr($item_evento->start_time, 0, 10);
                $vr_retorno = '';

                // Se tem que gravar
                if ($pr_flg_gravar == 1) {

                    // Instanciar a classe de events
                    $evento = new eventos($vr_bd);

                    // Inserir o evento e salvar o retorno
                    $vr_retorno = $evento->insereEvento(self::FACEBOOK_SITE, $vr_dt_evento, $vr_ds_local, '', '', $vr_ds_atracao, '', '', '', $vr_id_evento, $vr_hr_evento);

                }

                // Salvar dados do evento no Array para retorno
                $vr_eventos[] = array('evento' => $vr_ds_atracao,
                    'horario' => $vr_dt_evento,
                    'lugar' => $vr_ds_local,
                    'retorno' => $vr_retorno);
            }
        }

        // Desconectar
        $vr_bd->desconecta();

        return $vr_eventos;

    }

    // Ler eventos linkados ao Facebook e atualizar os dados de cada um
    /**
     *
     */
    function atualizaEventos()
    {

        // Conectar ao BD
        $vr_bd = new bd(false);
        $vr_bd->conecta();

        // Instanciar a classe de eventos
        $eventos = new eventos($vr_bd);

        // Obter o array de eventos para atualizar
        $vr_arr_eventos = $eventos->trazEventosFacebook();

        // Desconectar
        $vr_bd->desconecta();

        // Percorrer os eventos trazidos
        while (list($key, $val) = each($vr_arr_eventos)) {

            // Obter o ID do evento e o ID do facebook
            $vr_id_evento = $vr_arr_eventos[$key]['idEvento'];
            $vr_id_facebook = $vr_arr_eventos[$key]['idFacebook'];

            $this->buscaParticipantes($vr_id_evento, $vr_id_facebook);

        }

    }

    // Buscar e atualizar quem são os participantes de determinado evento
    function buscaParticipantes($pr_id_evento, $pr_id_facebook)
    {

        // Criamos um novo recurso do tipo Curl
        $vr_ch = curl_init();

        // Informar URL e outras funções ao CURL
        curl_setopt($vr_ch, CURLOPT_URL, self::FACEBOOK_API . $pr_id_facebook . '/attending?access_token=' . self::FACEBOOK_ID_APP . '|' . self::FACEBOOK_TOKEN . "&limit=500");

        // Evitar problemas com requisições HTTPS
        curl_setopt($vr_ch, CURLOPT_SSL_VERIFYPEER, false);

        // Retornar saida ao inves de imprimir
        curl_setopt($vr_ch, CURLOPT_RETURNTRANSFER, true);

        // Acessar a URL e retornar a saída
        $vr_saida = curl_exec($vr_ch);

        // Converter para JSON
        $vr_json_participantes = json_decode($vr_saida);

        // Se nao veio dados, retorna
        if (is_array($vr_json_participantes->data) == false) {
            return;
        }

        $this->trataParticipantes($pr_id_evento, $vr_json_participantes->data);

    }

    function trataParticipantes($pr_id_evento, $pr_arr_participantes)
    {
        // Banco de dados
        $this->bd = new bd(false);

        // Conectar
        $this->bd->conecta();

        // Instanciar a classe de participante
        $participante = new participante($this->bd);

        // Deletar todos os participantes deste evento
        $participante->deletarParticipantes($pr_id_evento);

        // Percorrer todos os participantes
        foreach ($pr_arr_participantes as $vr_participante) {

            // Obter o ID do usuario do facebook
            $vr_id_participante = $vr_participante->id;
            $vr_ds_participante = $vr_participante->name;

            // Verifica se tem que criar ou atualizar o perfil
            $vr_flg_atualiza = $participante->verificaCriaPerfil($vr_id_participante);

            if ($vr_flg_atualiza == 1) {

                // Obter e atualizar os dados do participante
                $vr_flg_cadastrou = $this->cadastraParticipante($vr_id_participante, $vr_ds_participante);

                if (!$vr_flg_cadastrou) {
                    continue;
                }

                $vr_flg_atualiza = 0;

            }

            // Se esta cadastrado o perfil, criar o evento_participante
            if ($vr_flg_atualiza == 0) {

                // Inserir registro do participante neste evento
                $vr_tabela = "evento_participante";
                $vr_campos = "idEvento, idParticipante";
                $vr_valores = "$pr_id_evento, $vr_id_participante";

                $this->bd->incluiRegistro($vr_tabela, $vr_campos, $vr_valores);

            }

        }

        // Buscar os dados para atualizar no evento
        $participante->dadosEventos($pr_id_evento);

        $this->bd->desconecta();


    }

    function cadastraParticipante($pr_id_participante, $pr_ds_participante)
    {

        $padroniza = new padroniza();

        $vr_ds_nome_completo = $padroniza->replaceHTML($pr_ds_participante);
        $vr_ds_primer_nome   = explode(" ", $vr_ds_nome_completo);
        $vr_ds_primer_nome   = $vr_ds_primer_nome[0];

        if ($vr_ds_nome_completo == '' || $vr_ds_primer_nome == null) {
            return false;
        }

        // Buscar o sexo do participante
        $participante = new participante($this->bd);

        $vr_ds_sexo = $participante->buscarSexo($vr_ds_primer_nome);

        // Se não achou o sexo, desconsiderar pessoa
        if (!$vr_ds_sexo) {
            return false;
        }

        // Inserir o participante
        $vr_tabela = "participante";
        $vr_campos = "dsNome, dsSexo, idParticipante";
        $vr_valores = "'$vr_ds_nome_completo' , '$vr_ds_sexo' , $pr_id_participante ";

        $this->bd->incluiRegistro($vr_tabela, $vr_campos, $vr_valores);

        return true;

    }

    function trazImgPageCover($pr_id_facebook)
    {

        // Criamos um novo recurso do tipo Curl
        $vr_ch = curl_init();

        // Informar URL e outras funções ao CURL
        curl_setopt($vr_ch, CURLOPT_URL, self::FACEBOOK_API . $pr_id_facebook . '?fields=cover&access_token=' . self::FACEBOOK_ID_APP . '|' . self::FACEBOOK_TOKEN);

        // Evitar problemas com requisições HTTPS
        curl_setopt($vr_ch, CURLOPT_SSL_VERIFYPEER, false);

        // Retornar saida ao inves de imprimir
        curl_setopt($vr_ch, CURLOPT_RETURNTRANSFER, true);

        // Acessar a URL e retornar a saída
        $vr_saida = curl_exec($vr_ch);

        // Converter para JSON
        $vr_json_imagem = json_decode($vr_saida);

        // Retornar caminho da imagem
        return $vr_json_imagem->cover->source;

    }

    function trazImgPageProfile($pr_id_facebook)
    {

        $vr_ds_imgProfile = self::FACEBOOK_API . $pr_id_facebook . '/picture?type=large&access_token=' . self::FACEBOOK_ID_APP . '|' . self::FACEBOOK_TOKEN;

        return $vr_ds_imgProfile;

    }

    function trazImgPersonProfile($pr_id_facebook)
    {

        $vr_ds_imgProfile = self::FACEBOOK_API . $pr_id_facebook . '/picture?width=150&height=150';

        return $vr_ds_imgProfile;

    }

    function buscaPagina($pr_ds_local, $pr_ds_cidade, $pr_bd)
    {

        // Criamos um novo recurso do tipo Curl
        $vr_ch = curl_init();

        // Informar URL e outras funções ao CURL
        curl_setopt($vr_ch, CURLOPT_URL, self::FACEBOOK_API . 'search?q=' . str_replace(" ", "%20", $pr_ds_local) . '&fields=id,name,likes,location,hours&type=page&access_token=' . self::FACEBOOK_ID_APP . '|' . self::FACEBOOK_TOKEN);

        // Evitar problemas com requisições HTTPS
        curl_setopt($vr_ch, CURLOPT_SSL_VERIFYPEER, false);

        // Retornar saida ao inves de imprimir
        curl_setopt($vr_ch, CURLOPT_RETURNTRANSFER, true);

        // Acessar a URL e retornar a saída
        $vr_saida = curl_exec($vr_ch);

        // Converter para JSON
        $vr_json_fanpage = json_decode($vr_saida);

        if ($pr_ds_cidade == 'Baln. Camboriú') {
            $pr_ds_cidade = 'Balneário Camboriú';
        }

        // Percorrer todos os resultados devolvido pela API
        for ($i = 0; $i < count($vr_json_fanpage->data); $i++) {

            $vr_horas = $vr_json_fanpage->data[$i]->hours;
            $vr_ds_abertura = '';

            // Verifica se tem horario de funcionamento
            if (property_exists($vr_json_fanpage->data[$i], "hours")) {

                // Percorre os horarios para armezenar
                foreach ($vr_horas as $vr_dia => $vr_hora) {

                    $vr_ds_dados = $vr_dia . ';' . $vr_hora;

                    $vr_ds_abertura = ($vr_ds_abertura == '') ? $vr_ds_dados : $vr_ds_abertura . '|' . $vr_ds_dados;

                }
            }

            // Obter o ID da fanpage, telefone e endereco
            $vr_id_fanpage = $vr_json_fanpage->data[$i]->id;
            $vr_qt_facelikes = $vr_json_fanpage->data[$i]->likes;
            $vr_ds_telefone = $vr_json_fanpage->data[$i]->location->phone;
            $vr_ds_endereco = $vr_json_fanpage->data[$i]->location->street;
            $vr_nr_latitude = $vr_json_fanpage->data[$i]->location->latitude;
            $vr_nr_longitude = $vr_json_fanpage->data[$i]->location->longitude;

            // Se bateu o nome, verificar se é da mesma cidade
            if (strcasecmp(trim($vr_json_fanpage->data[$i]->name), trim($pr_ds_local)) == 0) {

                // Se for da mesma cidade ou não foi enviada a cidade, devolver o ID da facebook
                if (strcasecmp(trim($vr_json_fanpage->data[$i]->location->city), trim($pr_ds_cidade)) == 0 || $pr_ds_cidade == "") {
                    return array('idFanpage' => $vr_id_fanpage,
                        'dsTelefone' => $vr_ds_telefone,
                        'dsEndereco' => $vr_ds_endereco,
                        'dsAbertura' => $vr_ds_abertura,
                        'latitude' => $vr_nr_latitude,
                        'longitude' => $vr_nr_longitude,
                        'nmCidade' => $vr_json_fanpage->data[$i]->location->city,
                        'faceLikes' => $vr_qt_facelikes);

                }

            }

            $local = new local ($pr_bd);

            $vr_info = $local->verificaNomesLocal($vr_json_fanpage->data[$i]->name);

            // Se achou o local, verificar se a cidade é a mesma
            if ($vr_info['idLocal'] > 0) {

                // Se for da mesma cidade ou não foi enviada a cidade, devolver o ID do facebook
                if (strcasecmp(trim($vr_json_fanpage->data[$i]->location->city), trim($pr_ds_cidade) == 0) || $pr_ds_cidade == "") {
                    return array('idFanpage' => $vr_id_fanpage,
                        'dsTelefone' => $vr_ds_telefone,
                        'dsEndereco' => $vr_ds_endereco,
                        'dsAbertura' => $vr_ds_abertura,
                        'latitude' => $vr_nr_latitude,
                        'longitude' => $vr_nr_longitude,
                        'nmCidade' => $vr_json_fanpage->data[$i]->location->city,
                        'faceLikes' => $vr_qt_facelikes);
                }

            }

        }

        return array('idFanpage' => 0,
            'dsTelefone' => '',
            'dsEndereco' => '',
            'dsAbertura' => '',
            'latitude' => null,
            'longitude' => null,
            'nmCidade' => '',
            'faceLikes' => 0);

    }

    function retornaHorarioHoje($pr_ds_abertura)
    {

        // Obter data e hora atual
        setlocale(LC_TIME, 'pt_BR', 'ptb');
        $vr_dt_atual = strtolower(date("D"));

        $vr_arr_horario = explode("|", $pr_ds_abertura);

        // Percorre os dias/horarios de abertura
        for ($i = 0; $i < count($vr_arr_horario); $i++) {

            $vr_arr_elemento = explode(";", $vr_arr_horario[$i]);

            // Se abre hoje
            if ($vr_dt_atual == substr($vr_arr_elemento[0], 0, 3)) {
                return $vr_arr_elemento[1];
            }
        }

        // Se chegou aqui, não abre hoje
        return '';

    }

    function buscaEventosAmigos( $pr_ds_cidade , $pr_ds_amigos  ) {

        // Conectar ao BD
        $vr_bd = new bd(false);
        $vr_bd->conecta();

        $eventos = new eventos($vr_bd);

        // Carregar os eventos dos proximos 14 dias
        $vr_arr_eventos = json_decode($eventos->carregaEventos($pr_ds_cidade , '', '2015-12-15' , '2016-02-26' , 100,  false), true );

        // Array a retornar
        $vr_rows = array();

        // Instanciar a classe particpantes
        $participante = new participante($vr_bd);

        // Percorrer os eventos que sejam do Facebook
        foreach ($vr_arr_eventos as $evento ) {

            if ($evento['facebook'] == 0) {
                continue;
            }


            // Carregar participantes do evento
            $vr_arr_participantes = json_decode($participante->carregaParticipantes($evento['id'], '' ), true );

            // Percorrer os participantes
            foreach ($vr_arr_participantes as $pessoa_participante) {

                // Nome do participante
                $vr_ds_nome = $pessoa_participante['dsNome'];
                $vr_ds_img  = $pessoa_participante['dsImgProfile'];

                // Convertir amigos em um array
                $vr_arr_amigos = explode(',',$pr_ds_amigos);

                // Percorrer os amigos do Perfil
                for ($i=0; $i < count($vr_arr_amigos); $i++) {

                    // Se amigo estiver no evento
                    if ($vr_ds_nome == $vr_arr_amigos[$i]) {
                        // Retornar dados do evento
                        $vr_rows[] = array('idEvento'  => $evento['id'],
                                           'atracao'   => $evento['atracao'],
                                           'idLocal'   => $evento['idLocal'],
                                           'dsLocal'   => $evento['local'],
                                           'data'      => $evento['data'],
                                           'hora'      => $evento['hora'],
                                           'imagemEve' => $evento['dsImgProfile'],
                                           'cidade'    => $evento["cidade"],
                                           'estado'    => $evento['estado'],
                                           'amigo'     => $vr_ds_nome,
                                           'imagemPar' => $vr_ds_img);
                        break;
                    }

                }

            }

        }

        $vr_bd->desconecta();

        return json_encode($vr_rows);

    }
}

?>