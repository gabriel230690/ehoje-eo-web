<?php

class participante {

	// Acesso ao Banco de dados
	private $bd;
	
	// Imagem -> https://graph.facebook.com/100006067060217/picture?width=150&height=150	
	// Link   -> http://facebook.com/100006067060217

	// Construtor principal da classe
	function participante ($pr_bd) {
		$this->bd = $pr_bd;
	}
	
	// Deletar todos os participantes de determinado evento
	function deletarParticipantes ($pr_id_evento) {
	
		$vr_sql = "DELETE FROM evento_participante " . 
		           "WHERE idEvento = $pr_id_evento";
		           		
		// Efetuar exclusão
		mysql_query($vr_sql);
	
	}
	
	// Verifica se o perfil já existe
	function verificaCriaPerfil ($pr_id_participante) {
	
		$vr_sql = "SELECT 1 " . 
		            "FROM participante p " . 
		           "WHERE p.idParticipante = $pr_id_participante";
		           
		// Executar consulta
		$vr_results = mysql_query($vr_sql);
				
		// Retornar Verdadeiro se achou o perfil e falso caso contrario
		if ($vr_row = mysql_fetch_assoc($vr_results)) {
			return 0;
		}
				
		return 1;
	
	}
	
	// Buscar os dados para gravar no evento (Quant homens, mulheres, etc)
	function dadosEventos ($pr_id_evento) {
	
		$vr_sql = "SELECT p.dsSexo " . 
		            "FROM evento_participante e , participante p " . 
		           "WHERE e.idEvento = $pr_id_evento " .
		             "AND e.idParticipante = p.idParticipante ";
		             		           
		// Executar consulta
		$vr_results = mysql_query($vr_sql);
		
		$vr_cont_total = 0;
		$vr_cont_homem = 0;
		$vr_cont_mulher = 0;
					
		// Retornar os participantes do evento e contabilizar 
		while ($vr_row = mysql_fetch_assoc($vr_results)) {
		
			$vr_ds_sexo = $vr_row["dsSexo"];
						
			if ($vr_ds_sexo == 'f') { // Mulheres
				$vr_cont_mulher = $vr_cont_mulher + 1;
			}
			else if ($vr_ds_sexo == 'm') { // Homens
				$vr_cont_homem = $vr_cont_homem + 1;
			}
			
			$vr_cont_total = $vr_cont_total + 1;
		
		}
		
		
		// Se não há confirmados, deixar zerado
		if ($vr_cont_total == 0) {
			$vr_pc_homem = 0;
			$vr_pc_mulher = 0;
		}
		else {
			// Obter a porcentagem de homens e mulheres
			$vr_pc_homem  = ($vr_cont_homem  / $vr_cont_total) * 100;
			$vr_pc_mulher = ($vr_cont_mulher / $vr_cont_total) * 100;
		}
		
		$vr_tabela  = "evento";
		$vr_campos_valores  = "qtConfirmados = $vr_cont_total , qtHomens = $vr_cont_homem ," .
		                      "qtMulheres = $vr_cont_mulher   , pcHomens = $vr_pc_homem , pcMulheres = $vr_pc_mulher";
		              		
		// Atualizar dados do evento
		$this->bd->editaRegistro($vr_tabela, $vr_campos_valores, 'idEvento', $pr_id_evento);
		
	} 
	
	function carregaParticipantes($pr_id_evento, $pr_ds_sexo ) {
	
		$vr_sql = "SELECT * FROM " .
		          "evento_participante ep, participante e " .
		           "WHERE ep.idEvento = $pr_id_evento " .
		             "AND e.idParticipante = ep.idParticipante " .
		             "AND (e.dsSexo = '$pr_ds_sexo' OR '$pr_ds_sexo' = 't' OR '$pr_ds_sexo' = '' ) " . 
		          "ORDER BY e.idParticipante";
		           
		// Executar consulta
		$vr_results = mysql_query($vr_sql);
				
		// Array para devolver os participantes
		$vr_arr_participantes = array();
		
		$facebook = new facebook();
		
		// Retornar os participantes do evento e contabilizar 
		while ($vr_row = mysql_fetch_assoc($vr_results)) { 
			
			$vr_id_facebook   = $vr_row['idParticipante'];
			$vr_ds_imgProfile = $facebook->trazImgPersonProfile($vr_id_facebook);
			
			// Salvar dados do participante no Array para retorno   
			$vr_arr_participantes[] = array('idParticipante' => $vr_id_facebook,
			           			  		    'dsNome'         => $vr_row['dsNome'],
			                    		    'dsImgProfile'   => $vr_ds_imgProfile);  
						
		}	
		
		return json_encode($vr_arr_participantes);           
	
	}

    // Buscar o sexo de determinado nome
    function buscarSexo ($pr_ds_nome) {

        $vr_sql = "SELECT dsSexo " .
                    "FROM participante p " .
                   "WHERE substring_index(dsNome,' ',1) = '$pr_ds_nome' " .
                   "LIMIT 1";

        // Executar consulta
        $vr_results = mysql_query($vr_sql);

        // Retornar os participantes do evento e contabilizar
        $vr_row = mysql_fetch_assoc($vr_results);

        if ($vr_row) {

            return $vr_row['dsSexo'];

        }

        return false;

    }

}

?>