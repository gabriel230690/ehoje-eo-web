<?php

class vale{
	
	// Nome do site
	const VALE_SITE = 'http://vale1convite.com.br';
	
	// Quantidade de dias a serem buscados
	const VALE_DIAS = 10;
	
	// ID do site
	const ID_VALE = 5;
	
	//Armazena a cidade/estado em questão para envio na inserção do evento na base
	static $gb_ds_cidade;
	static $gb_ds_estado;
	
	//Armazena a data para consulta dos eventos
	static $gb_dt_proxima;
	
	//Utilizada para a requisição curl
	static $gb_ch;
	
	// Construtor da classe principal
	function vale () {
		
	}	
	
	function inicializaVale($pr_flg_gravar){
			
		ECHO "VAI 2";	
			
		// Criamos um novo recurso do tipo Curl
		self::$gb_ch = curl_init();
		
		//Seta as opções da requisição curl
		curl_setopt(self::$gb_ch, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt(self::$gb_ch, CURLOPT_COOKIESESSION, TRUE); 
		curl_setopt(self::$gb_ch, CURLOPT_COOKIEFILE, "cookiefile"); 
		curl_setopt(self::$gb_ch, CURLOPT_COOKIE, session_name() . '=' . session_id()); 
			
		$vr_arr_cidades = array();
		
		//Busca todas as cidades onde serão buscados os eventos
		$vr_arr_cidades = $this-> buscaCidades();
		
		//Para cada cidade
		for ($row = 0; $row < count($vr_arr_cidades); $row++){
			
			//Pega a data atual
			self::$gb_dt_proxima = date('dmY');
			self::$gb_ds_cidade = $vr_arr_cidades[$row]['dscidade'];
			self::$gb_ds_estado = $vr_arr_cidades[$row]['dsestado'];
			
			// Juntar os eventos do local na variavel que acumula todos os eventos 
			$vr_arr_eventos[] = $this -> alteraCidade($vr_arr_cidades[$row]['idcidade'],$pr_flg_gravar);
					
		}
		
		//Libera a requisição curl
		curl_close(self::$gb_ch);
		
		// Trata eventos e retornar os mesmos
     	return json_encode($vr_arr_eventos);
     	
	}
	
	function buscaCidades(){
	
		// Cidades a serem pesquisadas
		$vr_arr_cidades = array();
		
		$vr_arr_descricao = array();
		$vr_arr_descricao['idcidade'] = 1;
		$vr_arr_descricao['dscidade'] = 'Florianópolis';
		$vr_arr_descricao['dsestado'] = 'SC';		
		$vr_arr_cidades[0] = $vr_arr_descricao;		
		
		$vr_arr_descricao = array();
		$vr_arr_descricao['idcidade'] = 4;
		$vr_arr_descricao['dscidade'] = 'Baln. Camboriú';
		$vr_arr_descricao['dsestado'] = 'SC';
		$vr_arr_cidades[1] = $vr_arr_descricao;
		
		$vr_arr_descricao = array();
		$vr_arr_descricao['idcidade'] = 8;
		$vr_arr_descricao['dscidade'] = 'Blumenau';
		$vr_arr_descricao['dsestado'] = 'SC';
		$vr_arr_cidades[2] = $vr_arr_descricao;
		
		$vr_arr_descricao = array();
		$vr_arr_descricao['idcidade'] = 5;
		$vr_arr_descricao['dscidade'] = 'Londrina';
		$vr_arr_descricao['dsestado'] = 'PR';
		$vr_arr_cidades[3] = $vr_arr_descricao;
		
		$vr_arr_descricao = array();
		$vr_arr_descricao['idcidade'] = 6;
		$vr_arr_descricao['dscidade'] = 'Maringá';
		$vr_arr_descricao['dsestado'] = 'PR';
		$vr_arr_cidades[4] = $vr_arr_descricao;
		
		return $vr_arr_cidades;
	}
	
	function alteraCidade($pr_id_cidade,$pr_flg_gravar) {
	
		// Informar URL e outras funções ao CURL
		curl_setopt(self::$gb_ch, CURLOPT_URL, self::VALE_SITE . '/site/index/alteraCidade.php?idCidade='.$pr_id_cidade);
		
		// Retornar saida ao inves de imprimir
		curl_setopt(self::$gb_ch, CURLOPT_RETURNTRANSFER, true);
		
		// Acessar a URL e retornar a saída
		$vr_ds_saida = curl_exec(self::$gb_ch);
		
		// Se houver algum erro na requisição Curl então, grava log e vai para o próximo site
		if (curl_error(self::$gb_ch) != ''){
		
			// Armazenar o erro
			$vr_ds_erro = 'Erro na requisição CURL ' . curl_error(self::$gb_ch);
		
			// Instanciar a classe do log e gerar critica
			$log = new log();
			
			$log->geraLog ( self::ID_VALE , $vr_ds_erro);
				
			return;
		}
		
		// Instanciar classe da data
		$vr_data = new data();
				
		for($i = 1; $i <= self::VALE_DIAS; $i++){
	
			// Retornar a String com os eventos
			$vr_arr_eventos[] = $this -> obtemEventos(substr(self::$gb_dt_proxima,0,2),substr(self::$gb_dt_proxima,2,2),substr(self::$gb_dt_proxima,4,4));
			
			//Pega a próxima data para consultar os eventos
			self::$gb_dt_proxima = $vr_data -> incrementaDiasData(self::$gb_dt_proxima,1);
			
		}
		
		return json_encode($this -> trataEventos($vr_arr_eventos,$pr_flg_gravar));
		
	}
	
	function obtemEventos($pr_nr_dia,$pr_nr_mes,$pr_nr_ano) {
	
		// Informar URL e outras funções ao CURL
		curl_setopt(self::$gb_ch, CURLOPT_URL, self::VALE_SITE . '/site/agenda/agenda.php?dia='.$pr_nr_dia.'&mes='.$pr_nr_mes.'&ano='.$pr_nr_ano.'&param=eventos');
		
		// Acessar a URL e retornar a saída
		$vr_ds_saida = curl_exec(self::$gb_ch);
		
		// Se houver algum erro na requisição Curl então, grava log e vai para o próximo site
		if (curl_error(self::$gb_ch) != ''){
		
			// Armazenar o erro
			$vr_ds_erro = 'Erro na requisição CURL ' . curl_error(self::$gb_ch);

			// Instanciar a classe do log e gerar critica
			$log = new log();
			
			$log->geraLog ( self::ID_VALE , $vr_ds_erro);
				
			return;
		}
				
		// Retornar a String com os eventos
        return $this -> trataDadosVale($vr_ds_saida);
		
	}
	
	function trataDadosVale($pr_ds_saida){
		
		$vr_nr_desconsidera = strpos($pr_ds_saida,'<ul class="boxAgenda">');  
		$vr_ds_dados = substr($pr_ds_saida,$vr_nr_desconsidera);

		$vr_ds_dados = str_replace("&","e_comercial",$vr_ds_dados);
		$vr_ds_dados = str_replace("nbsp;","",$vr_ds_dados);
		$vr_ds_dados = str_replace("fb:comments-count","a",$vr_ds_dados);
		$vr_ds_dados = str_replace("nowrap", "",$vr_ds_dados);
		$vr_ds_dados = str_replace("<A", "<a",$vr_ds_dados);
		$vr_ds_dados = str_replace("/A", "/a",$vr_ds_dados);
		$vr_ds_dados = str_replace('<IMG src="/resources/img/social/facebook.png" title="Promover no Facebook" width="65">', '',$vr_ds_dados);
		$vr_ds_dados = str_replace('<IMG border="0" src="/resources/img/social/twitter.png" title="Promover no Twitter" width="65">', '',$vr_ds_dados);
		$vr_ds_dados = str_replace('<IMG border="0" src="/resources/img/social/icone_link.png" tooltip="Envie seu Link Exclusivo para seus amigos" width="65">', '',$vr_ds_dados);
		$vr_ds_dados = "<div>".$vr_ds_dados."</div>";
		
		return $vr_ds_dados;
	
	}	
	
	// Ler todos os eventos obtidos e fazer a gravacão dos mesmos na base
	function trataEventos ($pr_arr_eventos, $pr_flg_gravar) {
	
		// Array para devolver os eventos
		$vr_arr_eventos = array();
		
		for($i = 0; $i < count($pr_arr_eventos); $i++){
			
			try {
			    
				// Obter a String XML
				$vr_ds_dados = $pr_arr_eventos[$i];
				
				if($vr_ds_dados == ''){
					continue;
				}
				
				// Converter em um objeto XML				
				$vr_xml_eventos = new DomDocument;
				$vr_xml_eventos->loadXML(utf8_encode($vr_ds_dados));
				
				// Tratar os dados do XML e salvar em um array para retornar os eventos
				$vr_arr_eventos[] = $this->trataXMLVale($vr_xml_eventos, $pr_flg_gravar );

			} catch (Exception $ex) {
			 
				// Armazenar o erro
				$vr_ds_erro = 'Erro na conversão para XML ' . $ex->getMessage();
			
				// Instanciar a classe do log e gerar critica
				$log = new log();
				
				$log->geraLog ( self::ID_VALE ,$vr_ds_erro);
								  
				// Proximo XML	  
				continue; 
			}
			
		}
		
		return $vr_arr_eventos;		
		
	}	
	
	
	function trataXMLVale($pr_xml_eventos, $pr_flg_gravar) {
		
		// Array para devolver os eventos
		$vr_arr_eventos = array();
		
		$vr_bd = new bd(false);
		$vr_bd -> conecta();
		
		$vr_elementos = $pr_xml_eventos->getElementsByTagName("li");
		
		foreach ($vr_elementos as $vr_elemento) {
					
			try {
				
				// Data do Evento
				if ($vr_elemento->getAttribute('class') == 'reset') {
				
					$vr_dtevento = trim(substr($vr_elemento->getElementsByTagName('div')->item(0)->nodeValue,0,10));
				    $vr_dtevento = substr($vr_dtevento,6, 4)."-".substr($vr_dtevento,3, 2)."-".substr($vr_dtevento,0, 2);
										
				}

				// Demais dados do evento
				if ($vr_elemento->getAttribute('class') == "window_box_li") {
					
					if( $vr_elemento->getElementsByTagName('p')->item(0)){
						if($vr_elemento->getElementsByTagName('p')->item(0)->getAttribute('class') == 'aviso'){
						
							continue;				
							
						}
						
					}				

					$vr_ds_local    = trim($vr_elemento->getElementsByTagName("img")->item(0)->getAttribute("title"));
					$vr_ds_link     = self::VALE_SITE.trim($vr_elemento->getElementsByTagName("a")->item(1)->getAttribute("href"));
					$vr_ds_atracoes = trim($vr_elemento->getElementsByTagName("dt")->item(0)->nodeValue);
					
					// Se tem que gravar		                         
					if ($pr_flg_gravar == 1) {
				
						// Instanciar a classe de events
						$evento = new eventos($vr_bd);
			
					    // Inserir o evento e salvar o retorno
						$vr_ds_retorno = $evento->insereEvento(self::VALE_SITE, $vr_dtevento , $vr_ds_local, $vr_ds_link, '' , $vr_ds_atracoes , self::$gb_ds_cidade, self::$gb_ds_estado, 'Brasil');
				
					}  
					
					// Salvar dados do evento no Array para retorno   
					$vr_arr_eventos[] = array('evento'  => $vr_ds_atracoes,
											  'horario' => $vr_dtevento,
											  'lugar'   => $vr_ds_local, 
											  'retorno' => $vr_ds_retorno); 
					
				}
			} catch (Exception $ex) {
    					
				// Armazenar o erro
				$vr_ds_erro = 'Erro em evento individual ' . $ex->getMessage();
	
				// Instanciar a classe do log e gerar critica
				$log = new log();
				
				$log->geraLog ( self::ID_VALE ,  $vr_ds_erro);
								
				continue;
			}	
			
		}	
		
		// Desconectar
		$vr_bd -> desconecta();
		
		return $vr_arr_eventos;
	
	}	
	
}

?>