<?php

class site {

	private $bd;

	function site ($bd) {
		$this->bd = $bd;
	}
	
	function trazSite ($pr_ds_site) {
		
		$vr_sql = "SELECT idSite FROM site WHERE dsSite = '$pr_ds_site'";

		$vr_results = mysql_query($vr_sql);
		
		// Se deu erro na consulta, considerar site 9
		if (!$vr_results) {
			return -1;
		}
		
		// Se não achou site, incluir
		if (mysql_num_rows($vr_results) == 0) {
		
			$vr_tabela  = "site";
			$vr_campos  = "dsSite";
			$vr_valores = "'$pr_ds_site'";

			return $this->bd->incluiRegistro($vr_tabela, $vr_campos, $vr_valores);
		}
		
		// Consultar retorno
		$vr_row = mysql_fetch_assoc($vr_results);

		// Retornar ID do site
		return $vr_row['idSite'];
	}
	

}

?>
