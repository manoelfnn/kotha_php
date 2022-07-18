<?php
namespace Core\Libs;

class Color {

	public static function adjustBrightness($hex, $steps) {
		// Steps should be between -255 and 255. Negative = darker, positive = lighter
		$steps = max(- 255, min(255, $steps));

		// Normalize into a six character long hex string
		$hex = str_replace('#', '', $hex);
		if (strlen($hex) == 3) {
			$hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
		}

		// Split into three parts: R, G and B
		$color_parts = str_split($hex, 2);
		$return = '#';

		foreach ($color_parts as $color) {
			$color = hexdec($color); // Convert to decimal
			$color = max(0, min(255, $color + $steps)); // Adjust color
			$return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
		}

		return $return;
	}
	
	public static function invert($_hex) {
		$color = str_replace('#', '', $_hex);
		if (strlen($color) != 6){ return '000000'; }
		$rgb = '';
		for ($x=0;$x<3;$x++){
			$c = 255 - hexdec(substr($color,(2*$x),2));
			$c = ($c < 0) ? 0 : dechex($c);
			$rgb .= (strlen($c) < 2) ? '0'.$c : $c;
		}
		return '#'.$rgb;
	}

	public static function hex2rgb($_hex, $_alpha = null, $_adjust = null) {
		if ($_adjust) {
			$_hex = self::adjustBrightness($_hex, $_adjust);
		}
		list ($r, $g, $b) = sscanf($_hex, "#%02x%02x%02x");
		if ($_alpha) {
			return 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $_alpha . ')';
		} else {
			return 'rgb(' . $r . ', ' . $g . ', ' . $b . ')';
		}
	}
}