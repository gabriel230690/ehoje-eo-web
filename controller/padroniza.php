<?php

class padroniza {

	// Construtor principal da classe
	function padroniza () {
	
	}
	
	function replaceHTML ($pr_ds_texto) {

		$vr_arr_search  = array ("e_comercial" , "'"        , '"'       , "&aacute;"   , "&#225;"   , "&Aacute;" , "&Atilde;" , "&atilde;" , 
		 					     "&#227;"      , "&Eacute;" , "&eacute;", "&#233;"     , "&Ecirc;"  , "&ecirc;"  , "&Iacute;" , "&iacute;" ,                     
		 					     "&otilde;"    , "&#245;"   , "&Otilde;", "&Ocirc;"    , "&ocirc;"  , "&oacute;" , "&Oacute;" , "&Uacute;" ,		
		 						 "&uacute;"    , "&ccedil;" , "&#231; " , "&Ccedil;"   ,  "&#39;"   , "&nbsp;"   , "&ldquo;"  , "&rdquo;"  ,
		 						 "&deg;"       , "&ordf;"   , "&ndash;" , "&amp;"      , "&quot;"   , "&#170;"   , "#65279#"  , "“"        ,
		 						 "'"           , "★"        , "◦"       , "✚"          , "’"        , "•"        , "♦"        , "✖"        , 
		 						 "♣"           , "♠"        , "♥"       , "☆"          , "❖"        , "&gt;"     , "&lt;"     , "✰"        ,	
		 						 "祭"          , "り"        , "☛"       , "hifenhifen" , "â??"      , "â?"       , "â?²"      , "ç¥­ã??"   , 
		 						 "â??â??â??"   , "â?¦"      , "²"       , "£"          , "250"      , "243"      , "226"      , "237"      ,
 							 	 "234"         , "&agrave;" , "&acirc;" , "&#ó;"       , "&#í;"     , "&#ó;"     , "&#ú;"     , "218"      ,
 							 	 "&#e;"        , "&#ó;"     , "244"     , "&#á;"       , "&#ô;"     , "231"      , "&#ç;"     , "¢"        ,
 							 	 "â?¡"         , "&shy;"    , "★"       , "..."
 		 );

		$vr_arr_replace = array ("&" , ""  , ""  , "á" , "á" , "Á"  , "Ã"  , "ã" , 
		                         "ã" , "É" , "é" , "é" , "Ê" , "ê"  , "Í"  , "í" ,      
							     "õ" , "õ" , "Õ" , "Ô" , "ô" , "ó"  , "Ó"  , "Ú" ,	
							     "ú" , "ç" , "ç" , "Ç" , "'" , " "  , "'"  , "'" ,
							     "." , "." , "-" , "&" , "'" , "a." , ""   , "'" , 
							     " " , "*" , "*" , "*" , " " , "*"  , "*"  , "*" , 
							     "*" , "*" , "*" , "*" , "*" , "=>" , "<=" , "*" ,
							     "*" , "*" , "*" , "--", ""  , ""	, "-"  , ""  ,
							     ""  , ""  , ""  , "*" , "ú" , "ó"  , "á"  , "í" ,
							     "e" , "a" , "â" , "ó" , "í" , "ú"  , "ú"  , "ú" ,
							     "e" , "ó" , "ô" , "â" , "ô" , "ç"  , "ç"  , ""  ,
							     ""  , ""  , "*" , ""
						
		);

		// Trocar caracteres indesejados		
		$pr_ds_texto = str_replace($vr_arr_search,$vr_arr_replace,$pr_ds_texto);
		
		$pattern = '/&#(\d+);/';
		$replacement = '${1}';
		
		return trim(preg_replace($pattern, $replacement, $pr_ds_texto));

	}
	
	function verificaLocal($pr_ds_atracoes, $pr_ds_banda) {

		if (strpos($pr_ds_atracoes,"Em Breve") > -1 || strpos($pr_ds_banda,"Em Breve") > -1) {
			return false;
		}

		return true;

	}
	
	function retiraAcentos( $pr_ds_acentos ) {
		$vr_arr_acentos    = array("á","à","â","ã","ä","é","è","ê","ë","í","ì","î","ï","ó","ò","ô","õ","ö","ú","ù","û","ü","ç"
	    	                      ,"Á","À","Â","Ã","Ä","É","È","Ê","Ë","Í","Ì","Î","Ï","Ó","Ò","Ô","Õ","Ö","Ú","Ù","Û","Ü","Ç");
		
		$vr_arr_semAcentos = array("a","a","a","a","a","e","e","e","e","i","i","i","i","o","o","o","o","o","u","u","u","u","c"
            	                  ,"A","A","A","A","A","E","E","E","E","I","I","I","I","O","O","O","O","O","U","U","U","U","C");
		
		return str_replace( $vr_arr_acentos, $vr_arr_semAcentos, $pr_ds_acentos ); 
	}

}

?>