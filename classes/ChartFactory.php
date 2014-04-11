<?php
	class ChartFactory 
	{
		public static $thumbnail_width = 650;
		#comment
		public static $default_width = 1000;

	public static function alterBrightness($color, $steps) 
	{
		$a = ($color & 0xff000000) >> 24;
		$r = ($color & 0x00ff0000) >> 16;
		$g = ($color & 0x0000ff00) >> 8;
		$b = $color & 0x000000ff;
		
		if($color > 0xffffff) $a = max(0,min(255,$a + $steps));
		$r = max(0,min(255,$r + $steps)); 
		$g = max(0,min(255,$g + $steps));  
		$b = max(0,min(255,$b + $steps));
		
		return ($a << 24)+($r << 16)+($g << 8)+$b;
	}

		
	//http://martin.ankerl.com/2009/12/09/how-to-create-random-colors-programmatically/
	public static function getColors($count = NULL)
	{
		$ret = array();
		
		$stmt = DataWarehouse::connect()->handle()->prepare("select color from moddb.Colors order by `order`");
		$stmt->execute();
		
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);	
		if($count == NULL || count($results) >=  $count)
		{		
			foreach ($results as $result)
			{
				$ret[] = hexdec($result['color']);
			}
		}
		$ret_count = count($ret);
		srand($count);
		if($count != NULL && $ret_count < $count)
		{
			$value = 15;
			$increment =310.0/($count - $ret_count); 
			
			for($i = $ret_count; $i < $count; $i++)
			{
				$value = $value + $increment;
				
				//$value = $value % 360.0;
				
				$rgb = self::HSVtoRGB(array($value/360.0, rand(80,90)/100.0, rand(75,99)/100.0));
				//print_r(array($value/360.0, rand(45,55)/100.0, rand(75,99)/100.0));
				$color_string = sprintf("%02x%02x%02x",$rgb[0]*255,$rgb[1]*255,$rgb[2]*255);
				
				$next_color = hexdec($color_string);

				$ret[] = $next_color;
			}
		}
		srand();
		return $ret ;
	}				
			
	
	function value_to_giga_value(&$value, $key) {
			$value = $value / 1000000000.0;
	}
	function value_to_mega_value(&$value, $key) {
			$value = $value / 1000000.0;
	}
	
	function value_to_kilo_value(&$value, $key) {
			$value = $value / 1000.0;
	}
	
	function hour_to_minute(&$value, $key) {
			$value = $value * 60.0;
	}
	
	function hour_to_day(&$value, $key) {
			$value = $value / 24.0;
	}
	
	function hour_to_year(&$value, $key) {
			$value = $value / 8765.81277;
	}
	
	function day_to_hour(&$value, $key) {
			$value = $value * 24.0;
	}
	
	function day_to_year(&$value, $key) {
			$value = $value / 365.242199;
	}
	
	function day_to_millenium(&$value, $key) {
			$value = $value / 365242.199;
	}
	function endsWith($Haystack, $Needle){
		return strrpos($Haystack, $Needle) === strlen($Haystack)-strlen($Needle);
	}
	
	
	public static function HSVtoRGB(array $hsv) {
		list($H,$S,$V) = $hsv;
		//1
		$H *= 6;
		//2
		$I = floor($H);
		$F = $H - $I;
		//3
		$M = $V * (1 - $S);
		$N = $V * (1 - $S * $F);
		$K = $V * (1 - $S * (1 - $F));
		//4
		switch ($I) {
			case 0:
				list($R,$G,$B) = array($V,$K,$M);
				break;
			case 1:
				list($R,$G,$B) = array($N,$V,$M);
				break;
			case 2:
				list($R,$G,$B) = array($M,$V,$K);
				break;
			case 3:
				list($R,$G,$B) = array($M,$N,$V);
				break;
			case 4:
				list($R,$G,$B) = array($K,$M,$V);
				break;
			case 5:
			case 6: //for when $H=1 is given
				list($R,$G,$B) = array($V,$M,$N);
				break;
		}
		return array($R, $G, $B);
	}
}
?>
