<?php

class log {

	private $bd;

	function log () {
	
		// Conectar ao BD
		$this->bd = new bd(false);
		$this->bd->conecta();
		
	}
	
	function geraLog ($pr_id_site, $pr_ds_erro) {
		
		// Retirar aspas
		$pr_ds_erro = str_replace("'","",$pr_ds_erro);
		$pr_ds_erro = str_replace('"','',$pr_ds_erro); 
				
		// Obter data e hora
		setlocale(LC_TIME, 'pt_BR', 'ptb');
		$vr_dt_log = date("Y-m-d");
		$vr_hr_log = date("H:i:s");
		
		// Inserir registro de log		
		$vr_tabela  = "log_gravacao";
		$vr_campos  = "dtLog , hrLog, idSite , dsError";
		$vr_valores = "'$vr_dt_log' , '$vr_hr_log' , $pr_id_site , '$pr_ds_erro'";
		
		$this -> bd -> incluiRegistro($vr_tabela, $vr_campos, $vr_valores);
			
		$this->bd->desconecta();
		
	}

	
	function gravaSugestaoLocal ($pr_ds_local) {
		
		// Buscar o ID da Fanpage
		$facebook = new facebook();
		$vr_infos = $facebook->buscaPagina($pr_ds_local, '', $this->bd);
		$vr_id_fanpage =  $vr_infos['idFanpage'];
		$vr_nm_cidade  =  $vr_infos['nmCidade'];
			
		// Buscar a cidade
		$endereco = new endereco($this->bd);
		$vr_id_cidade = $endereco-> buscaCidade(1, $vr_nm_cidade, null);
		$vr_id_cidade = ($vr_id_cidade == -1) ? 0 : $vr_id_cidade;		
		$vr_qt_facelikes = $vr_infos['faceLikes'];
			
		// Buscar o ID do foursquare
		$foursquare = new foursquare();
		$vr_infos = $foursquare->buscaInfos ($pr_ds_local, $vr_nm_cidade, $this->bd);
		$vr_id_foursquare = $vr_infos['idFoursquare'];

		// Se tiver os requisitos necessarios, criar o local			
		return $this->verificaRequisitos($pr_ds_local , $vr_id_cidade, $vr_id_fanpage, $vr_id_foursquare, $vr_qt_facelikes);
				
	}
	
	function verificaRequisitos ($pr_ds_local , $pr_id_cidade, $pr_id_fanpage, $pr_id_foursquare, $pr_qt_facelikes) {
	
		// Se tem cidade cadastrada, tem Fanpage, Foursquare e + de X curtidas, criar o local
		if ($pr_id_cidade > 0 && $pr_id_fanpage > 0  && $pr_id_foursquare != '' && $pr_qt_facelikes > 7000 ) {
		
			// Inserir registro de local		
			$vr_tabela  = "local";
			$vr_campos  = "dsLocal, idCidade";
			$vr_valores = " '$pr_ds_local' , $pr_id_cidade";

			return $this -> bd -> incluiRegistro($vr_tabela, $vr_campos, $vr_valores);
		
		} 
		else 
		if ($pr_qt_facelikes > 7000) {

			// Inserir na tabela de sugestões de locais
			$vr_tabela  = "sugestao_local";
			$vr_campos  = "dsLocal, idCidade, id_fanpage, id_foursquare, qtFaceLikes";
			$vr_valores = " '$pr_ds_local' , $pr_id_cidade, $pr_id_fanpage , '$pr_id_foursquare' , $pr_qt_facelikes ";

			return $this -> bd -> incluiRegistro($vr_tabela, $vr_campos, $vr_valores);
		
		}

        return 0;
	
	}
	
}

?>