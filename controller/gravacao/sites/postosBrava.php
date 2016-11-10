<?php

class postosBrava{
	
	// Nome do site
	const POSTOSBRAVA_SITE = 'http://www.postosbrava.com.br';
	
	// Quantidade de dias a serem buscados
	const POSTOSBRAVA_DIAS = 20;
	
	// ID do site
	const ID_POSTOSBRAVA = 6;
	
	//Armazena a próxima data para consulta de eventos
	static $gb_dt_proxima;
	
	//Armazena o nome do site
	static $gb_ds_site;
		
	// Construtor da classe principal
	function postosBrava () {
		
	}	
	
	function inicializaPostosBrava($pr_flg_gravar){
	
		//Pega a data atual
		self::$gb_dt_proxima = date('dmY');		
		
		// Instanciar classe da data
		$vr_data = new data();
		
		//Para cada dia
		for($i = 1; $i <= self::POSTOSBRAVA_DIAS; $i++){
	
			// Retornar a String com os eventos
			$vr_arr_eventos[] = $this -> obtemEventos(substr(self::$gb_dt_proxima,0,2),substr(self::$gb_dt_proxima,2,2),substr(self::$gb_dt_proxima,4,4));
			
			//Pega o próximo dia 
			self::$gb_dt_proxima = $vr_data -> incrementaDiasData(self::$gb_dt_proxima,1);
			
		}
		
		// Trata eventos e retornar os mesmos
     	return json_encode($this -> trataEventosPostosBrava($vr_arr_eventos,$pr_flg_gravar));
     	
	}
	
	function obtemEventos($pr_nr_dia,$pr_nr_mes,$pr_nr_ano) {
	
		// Criamos um novo recurso do tipo Curl
		$vr_ch = curl_init();
		
		self::$gb_ds_site = self::POSTOSBRAVA_SITE . '/agenda/&data='.$pr_nr_dia.'/'.$pr_nr_mes.'/'.$pr_nr_ano.'&pesquisa';
		
		// Informar URL e outras funções ao CURL
		curl_setopt($vr_ch, CURLOPT_URL, self::$gb_ds_site);
		
		// Retornar saida ao inves de imprimir
		curl_setopt($vr_ch, CURLOPT_RETURNTRANSFER, true);
		
		// Acessar a URL e retornar a saída
		$vr_ds_saida = curl_exec($vr_ch);
		
		// Se houver algum erro na requisição Curl então, grava log e vai para o próximo site
		if (curl_error($vr_ch) != ''){
		
			// Armazenar o erro
			$vr_ds_erro = 'Erro na requisição CURL ' . curl_error($vr_ch);
			
			// Instanciar a classe do log e gerar critica
			$log = new log();
			
			$log->geraLog ( self::ID_POSTOSBRAVA,  $vr_ds_erro);
			
			// Liberar recurso
			curl_close($vr_ch);
			
			return;
		}
				
		// Liberar recurso
		curl_close($vr_ch);

		// Retornar a String com os eventos
        return $this -> trataDadosPostosBrava($vr_ds_saida);
		
	}
	
	function trataDadosPostosBrava($pr_ds_saida){
		
		$vr_nr_desconsidera = strpos($pr_ds_saida,'institucional_area') - 13;
		$vr_ds_dados        = substr($pr_ds_saida,$vr_nr_desconsidera);
		$vr_nr_limite       = strpos($vr_ds_dados,"paginacao") - 13;
		
		$vr_ds_dados = substr($vr_ds_dados,0,$vr_nr_limite);
		$vr_ds_dados = str_replace("&","e_comercial",$vr_ds_dados);
		$vr_ds_dados = str_replace("<br>","",$vr_ds_dados);
		$vr_ds_dados = str_replace("<br />","",$vr_ds_dados);
		$vr_ds_dados = str_replace('="--"', '=""',$vr_ds_dados);
		$vr_ds_dados = "<div>".$vr_ds_dados."</div>";
		
		return $vr_ds_dados;
	
	}	
	
	// Ler todos os eventos obtidos e fazer a gravacão dos mesmos na base
	function trataEventosPostosBrava ($pr_arr_eventos, $pr_flg_gravar) {
	
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
				$vr_arr_eventos[] = $this->trataXMLPostosBrava($vr_xml_eventos, $pr_flg_gravar );

			} catch (Exception $ex) {
			 
				// Armazenar o erro
				$vr_ds_erro = 'Erro na conversão para XML ' . $ex->getMessage();
									
				// Instanciar a classe do log e gerar critica
				$log = new log();
				
				$log->geraLog ( self::ID_POSTOSBRAVA,  $vr_ds_erro);
					
				// Proximo XML	  
				continue; 
			}
			
		}
		
		return $vr_arr_eventos;		
		
	}	
	
	
	function trataXMLPostosBrava($pr_xml_eventos, $pr_flg_gravar) {
		
		// Array para devolver os eventos
		$vr_arr_eventos = array();
		
		$vr_elementos = $pr_xml_eventos->getElementsByTagName("div");
		
		foreach ($vr_elementos as $vr_elemento) {
					
			try {
				
				// Data do Evento
				if ($vr_elemento->getAttribute('id') != 'institucional_area') {
					continue;				
				}
				
				$vr_dt_monta = new data();
				
				// O link é da agenda do dia e nao especificamente do evento
				$vr_ds_link     = self::$gb_ds_site;
				$vr_ds_local    = trim($vr_elemento->getElementsByTagName("div")->item(6)->textContent);
				$vr_ds_atracoes = $vr_elemento->getElementsByTagName("p")->item(0)->textContent." ".($vr_elemento->getElementsByTagName("p")->item(1) ? $vr_elemento->getElementsByTagName("p")->item(1)->textContent : '');
				$vr_nr_dia      = trim($vr_elemento->getElementsByTagName("div")->item(4)->textContent);
				$vr_nr_mes      = $vr_elemento->getElementsByTagName("h3")->item(0)->textContent;
				$vr_dt_evento   = $vr_dt_monta -> montaData($vr_nr_dia,$vr_nr_mes,substr(self::$gb_dt_proxima,4,4));
				
				if($vr_dt_evento == ''){
					continue;
				}
							
				$vr_ds_cidade = trim($vr_elemento->getElementsByTagName("div")->item(7)->textContent);
				$vr_ds_estado = "SC";
					
				// Se tem que gravar		                         
				if ($pr_flg_gravar == 1) {

                    // Conectar ao BD
                    $vr_bd = new bd(false);
                    $vr_bd -> conecta();
			
					// Instanciar a classe de events
					$evento = new eventos($vr_bd);
										
					// Inserir o evento e salvar o retorno
					$vr_ds_retorno = $evento->insereEvento(self::POSTOSBRAVA_SITE, $vr_dt_evento , $vr_ds_local, $vr_ds_link, '' , $vr_ds_atracoes , $vr_ds_cidade, $vr_ds_estado, 'Brasil');

                    // Desconectar
                    $vr_bd -> desconecta();

				} 
				
				// Salvar dados do evento no Array para retorno   
				$vr_arr_eventos[] = array('evento'  => $vr_ds_atracoes,
										  'horario' => $vr_dt_evento,
										  'lugar'   => $vr_ds_local, 
										  'retorno' => $vr_ds_retorno); 					
					
				
			} catch (Exception $ex) {
			 
				// Armazenar o erro
				$vr_ds_erro = 'Erro na conversão para XML ' . $ex->getMessage();
			
				// Instanciar a classe do log e gerar critica
				$log = new log();
				
				$log->geraLog ( self::ID_POSTOSBRAVA , $vr_ds_erro);
					
				// Proximo XML	  
				continue; 
			}
			
		}

		return $vr_arr_eventos;
	
	}	
		
}


?>