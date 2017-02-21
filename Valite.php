<?php
/* 
	BMP简单验证码识别
	Author:CenLingHui
*/


/**
 * BMP 创建函数
 * @author simon
 * @param string $filename path of bmp file
 * @example who use,who knows
 * @return resource of GD
 */
function imagecreatefrombmp($filename) {
	if (!$f1 = fopen($filename, "rb")) return FALSE;
	$FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1, 14));
	if ($FILE['file_type'] != 19778) return FALSE;
	$BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' . '/Vcompression/Vsize_bitmap/Vhoriz_resolution' . '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1, 40));
	$BMP['colors'] = pow(2, $BMP['bits_per_pixel']);
	if ($BMP['size_bitmap'] == 0) $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
	$BMP['bytes_per_pixel'] = $BMP['bits_per_pixel'] / 8;
	$BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
	$BMP['decal'] = ($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
	$BMP['decal']-= floor($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
	$BMP['decal'] = 4 - (4 * $BMP['decal']);
	if ($BMP['decal'] == 4) $BMP['decal'] = 0;
	$PALETTE = array();
	if ($BMP['colors'] < 16777216) {
		$PALETTE = unpack('V' . $BMP['colors'], fread($f1, $BMP['colors'] * 4));
	}
	$IMG = fread($f1, $BMP['size_bitmap']);
	$VIDE = chr(0);
	$res = imagecreatetruecolor($BMP['width'], $BMP['height']);
	$P = 0;
	$Y = $BMP['height'] - 1;
	while ($Y >= 0) {
		$X = 0;
		while ($X < $BMP['width']) {
			if ($BMP['bits_per_pixel'] == 32) {
				$COLOR = unpack("V", substr($IMG, $P, 3));
				$B = ord(substr($IMG, $P, 1));
				$G = ord(substr($IMG, $P + 1, 1));
				$R = ord(substr($IMG, $P + 2, 1));
				$color = imagecolorexact($res, $R, $G, $B);
				if ($color == - 1) $color = imagecolorallocate($res, $R, $G, $B);
				$COLOR[0] = $R * 256 * 256 + $G * 256 + $B;
				$COLOR[1] = $color;
			} elseif ($BMP['bits_per_pixel'] == 24) $COLOR = unpack("V", substr($IMG, $P, 3) . $VIDE);
			elseif ($BMP['bits_per_pixel'] == 16) {
				$COLOR = unpack("n", substr($IMG, $P, 2));
				$COLOR[1] = $PALETTE[$COLOR[1] + 1];
			} elseif ($BMP['bits_per_pixel'] == 8) {
				$COLOR = unpack("n", $VIDE . substr($IMG, $P, 1));
				$COLOR[1] = $PALETTE[$COLOR[1] + 1];
			} elseif ($BMP['bits_per_pixel'] == 4) {
				$COLOR = unpack("n", $VIDE . substr($IMG, floor($P), 1));
				if (($P * 2) % 2 == 0) $COLOR[1] = ($COLOR[1] >> 4);
				else $COLOR[1] = ($COLOR[1] & 0x0F);
				$COLOR[1] = $PALETTE[$COLOR[1] + 1];
			} elseif ($BMP['bits_per_pixel'] == 1) {
				$COLOR = unpack("n", $VIDE . substr($IMG, floor($P), 1));
				if (($P * 8) % 8 == 0) $COLOR[1] = $COLOR[1] >> 7;
				elseif (($P * 8) % 8 == 1) $COLOR[1] = ($COLOR[1] & 0x40) >> 6;
				elseif (($P * 8) % 8 == 2) $COLOR[1] = ($COLOR[1] & 0x20) >> 5;
				elseif (($P * 8) % 8 == 3) $COLOR[1] = ($COLOR[1] & 0x10) >> 4;
				elseif (($P * 8) % 8 == 4) $COLOR[1] = ($COLOR[1] & 0x8) >> 3;
				elseif (($P * 8) % 8 == 5) $COLOR[1] = ($COLOR[1] & 0x4) >> 2;
				elseif (($P * 8) % 8 == 6) $COLOR[1] = ($COLOR[1] & 0x2) >> 1;
				elseif (($P * 8) % 8 == 7) $COLOR[1] = ($COLOR[1] & 0x1);
				$COLOR[1] = $PALETTE[$COLOR[1] + 1];
			} else return FALSE;
			imagesetpixel($res, $X, $Y, $COLOR[1]);
			$X++;
			$P+= $BMP['bytes_per_pixel'];
		}
		$Y--;
		$P+= $BMP['decal'];
	}
	fclose($f1);
	return $res;
}

define('WORD_WIDTH', 6);
define('WORD_HIGHT', 10);
define('OFFSET_X', 0);
define('OFFSET_Y', 0);
define('WORD_SPACING', 4);
class valite {
	public function setImage($Image) {
		$this->ImagePath = $Image;
	}
	public function getData() {
		return $data;
	}
	public function getResult() {
		return $DataArray;
	}
	public function getHec() {
		/* $tmp_name = $this->ImagePath; */
		$res1 = ImageCreateFromBMP($this->ImagePath);
		$tmp_name = './tmp/tmp.jpeg';
		imagejpeg($res1, $tmp_name);
		//echo $res1;exit;
		$res = imagecreatefromjpeg($tmp_name);
		$size = getimagesize($tmp_name);
		$data = array();
		for ($i = 0;$i < $size[1];++$i) {
			for ($j = 0;$j < $size[0];++$j) {
				$rgb = imagecolorat($res, $j, $i);
				$rgbarray = imagecolorsforindex($res, $rgb);
				if ($rgbarray['red'] < 125 || $rgbarray['green'] < 125 || $rgbarray['blue'] < 125) {
					$data[$i][$j] = 1;
				} else {
					$data[$i][$j] = 0;
				}
			}
		}
		$this->DataArray = $data;
		/* echo '<table>';
		for($i=0; $i < WORD_HIGHT; ++$i)
		{
			for($j=0+WORD_WIDTH+WORD_SPACING; $j <WORD_WIDTH+WORD_WIDTH+WORD_SPACING; ++$j)
			{
				echo $data[$i][$j];
			}
		}
		echo '</table>'; */
		$this->ImageSize = $size;
	}
	public function run() {
		$result = "";
		// 查找4个数字
		$data = array("", "", "", "");
		for ($i = 0;$i < 4;++$i) {
			$x = ($i * (WORD_WIDTH + WORD_SPACING)) + OFFSET_X;
			$y = OFFSET_Y;
			for ($h = $y;$h < (OFFSET_Y + WORD_HIGHT);++$h) {
				for ($w = $x;$w < ($x + WORD_WIDTH);++$w) {
					$data[$i].= $this->DataArray[$h][$w];
				}
			}
		}
		// 进行关键字匹配
		foreach ($data as $numKey => $numString) {
			$max = 0.0;
			$num = 0;
			foreach ($this->Keys as $key => $value) {
				$percent = 0.0;
				similar_text($value, $numString, $percent);
				if (intval($percent) > $max) {
					$max = $percent;
					$num = $key;
					if (intval($percent) > 95) break;
				}
			}
			$result.= $num;
		}
		$this->data = $result;
		// 查找最佳匹配数字
		return $result;
	}
	public function Draw() {
		for ($i = 0;$i < $this->ImageSize[1];++$i) {
			for ($j = 0;$j < $this->ImageSize[0];++$j) {
				echo $this->DataArray[$i][$j];
			}
			echo "\n";
		}
	}
	public function __construct() {
		$this->Keys = array('0' => '011110100001100001100001100001100001100001100001100001011110', '1' => '001000111000001000001000001000001000001000001000001000111110', '2' => '011110100001000001000001000010000100001000010000100000111111', '3' => '011110100001000001000001001110000001000001000001100001011110', '4' => '000010000110001010001010010010100010111111000010000010000111', '5' => '111111100000100000100000111110000001000001000001100001011110', '6' => '001110010000100000100000101110110001100001100001100001011110', '7' => '111111100001000010000010000100000100001000001000010000010000', '8' => '011110100001100001100001011110100001100001100001100001011110', '9' => '011110100001100001100001100011011101000001000001000010011100',);
	}
	protected $ImagePath;
	protected $DataArray;
	protected $ImageSize;
	protected $data;
	protected $Keys;
	protected $NumStringArray;
}
?>