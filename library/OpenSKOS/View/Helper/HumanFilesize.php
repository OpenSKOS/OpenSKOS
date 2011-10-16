<?php

class OpenSKOS_View_Helper_HumanFilesize {
	
	const PRECISION = 2;
	
	public function humanfilesize($bytes, $precision = null) {
		if (null===$precision) $precision = self::PRECISION;
	    $units = array('b', 'kb', 'Mb', 'Gb', 'Tb');
	  
	    $bytes = max($bytes, 0);
	    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	    $pow = min($pow, count($units) - 1);
	  
	    $bytes /= pow(1024, $pow);
	  
	    return number_format($bytes, $precision, ',', '.') . ' ' . $units[$pow]; 
	}
	
}
