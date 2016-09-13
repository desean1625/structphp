<?php
class Struct
{
    private $_sPattern = '/(\\d+)?([AxcbBhHsfdiIlL])/';
    private $el;
    private $bBE = false;

    public function __construct()
    {

        $this->_elLut = array(
            'x' => array('len' => 1),
            'A' => array('en' => array($this, '_EnArray'), 'de' => array($this, '_DeArray'), 'len' => 1),
            's' => array('en' => array($this, '_EnString'), 'de' => array($this, '_DeString'), 'len' => 1),
            'c' => array('en' => array($this, '_EnChar'), 'de' => array($this, '_DeChar'), 'len' => 1),
            'b' => array('en' => array($this, '_EnInt'), 'de' => array($this, '_DeInt'), 'len' => 1, 'bSigned' => true, 'min' => -128, 'max' => 127),
            'B' => array('en' => array($this, '_EnInt'), 'de' => array($this, '_DeInt'), 'len' => 1, 'bSigned' => false, 'min' => 0, 'max' => 255),
            'h' => array('en' => array($this, '_EnInt'), 'de' => array($this, '_DeInt'), 'len' => 2, 'bSigned' => true, 'min' => -32768, 'max' => 32767),
            'H' => array('en' => array($this, '_EnInt'), 'de' => array($this, '_DeInt'), 'len' => 2, 'bSigned' => false, 'min' => 0, 'max' => 65535),
            'i' => array('en' => array($this, '_EnInt'), 'de' => array($this, '_DeInt'), 'len' => 4, 'bSigned' => true, 'min' => -2147483648, 'max' => 2147483647),
            'I' => array('en' => array($this, '_EnInt'), 'de' => array($this, '_DeInt'), 'len' => 4, 'bSigned' => false, 'min' => 0, 'max' => 4294967295),
            'l' => array('en' => array($this, '_EnInt'), 'de' => array($this, '_DeInt'), 'len' => 4, 'bSigned' => true, 'min' => -2147483648, 'max' => 2147483647),
            'L' => array('en' => array($this, '_EnInt'), 'de' => array($this, '_DeInt'), 'len' => 4, 'bSigned' => false, 'min' => 0, 'max' => 4294967295),
            'f' => array('en' => array($this, '_En754'), 'de' => array($this, '_De754'), 'len' => 4, 'mLen' => 23, 'rt' => 5.960464477539062e-8),
            'd' => array('en' => array($this, '_En754'), 'de' => array($this, '_De754'), 'len' => 8, 'mLen' => 52, 'rt' => 0));

    }

    // Pack the supplied values into a new octet array, according to the fmt string
    public function pack($fmt, $values)
    {
        $a = array_fill(0, $this->calcLength($fmt), null);
        $a = $this->PackTo($fmt, $a, 0, $values);
        return $a;
    }

    public function unpack($fmt, $a, $p = 0)
    {
        $a = unpack('C*', $a);
        array_unshift($a, 0);
        array_shift($a);
        $this->bBE = ($fmt[0] != '<');
        preg_match_all($this->_sPattern, $fmt, $t);
        $matches = array();
        $rv      = array();
        foreach ($t[0] as $key => $value) {
            $matches[] = array($t[0][$key], $t[1][$key], $t[2][$key]);
        }
        foreach ($matches as $key => $m) {
            $n = (($m[1] == null) || ($m[1] == '')) ? 1 : (int) $m[1];
            $s = $this->_elLut[$m[2]]['len'];
            if (($p + $n * $s) > count($a)) {
                return null;
            }
            switch ($m[2]) {
                case 'A':case 's':
                    $rv[] = call_user_func($this->_elLut[$m[2]]['de'], $a, $p, $n);
                    break;

                case 'c':case 'b':case 'B':case 'h':case 'H':
                case 'i':case 'I':case 'l':case 'L':case 'f':case 'd':
                    $this->el = $this->_elLut[$m[2]];
                    foreach ($this->_UnpackSeries($n, $s, $a, $p) as $key => $value) {
                        $rv[] = $value;
                    }
                    break;
                case 'x':
                    $rv[] = null;
                    break;
            }
            $p += $n * $s;
        }
        return $rv;
    }

    public function calcsize($fmt)
    {
        return $this->calcLength($fmt);
    }
    // Determine the number of bytes represented by the format string
    public function calcLength($fmt)
    {
        $sum = 0;
        preg_match_all($this->_sPattern, $fmt, $t);
        $matches = array();

        foreach ($t[0] as $key => $value) {
            $matches[] = array($t[0][$key], $t[1][$key], $t[2][$key]);
        }

        foreach ($matches as $key => $m) {
            $sum += (($m[1] == null) || ($m[1] == '')) ? $this->_elLut[$m[2]]['len'] : (Int) $m[1] * $this->_elLut[$m[2]]['len'];
        }
        return $sum;
    }

    /*Unpack functions
    /****************************************/
    // Raw byte arrays
    private function _DeArray($a, $p, $l)
    {
        echo $p . " " . $l;
        $rv = array_slice($a, $p, $p + $l);
        return $rv;
    }
    // ASCII characters
    private function _DeChar($a, $p)
    {
        return chr($a[$p]);
    }
    // Little-endian (un)signed N-byte integers
    private function _DeInt($a, $p)
    {
        //$a = array_reverse ($a);
        $lsb  = $this->bBE ? ($this->el['len'] - 1) : 0;
        $nsb  = $this->bBE ? -1 : 1;
        $stop = $lsb + $nsb * $this->el['len'];
        $rv   = 0;
        $i    = $lsb;
        $f    = 1;
        while ($i != $stop) {
            $rv += ($a[$p + $i] * $f);
            $i += $nsb;
            $f *= 256;
        }
        if ($this->el['bSigned'] && ($rv & pow(2, $this->el['len'] * 8 - 1))) {
            $rv -= pow(2, $this->el['len'] * 8);
        }
        return $rv;
    }
    // ASCII character strings
    private function _DeString($a, $p, $l)
    {
        $rv = '';
        $i  = 0;
        while ($i < $l) {
            $rv .= chr($a[$p + $i]);
            $i++;
        }
        return $rv;
    }
    // Little-endian N-bit IEEE 754 floating point
    private function _De754($a, $p)
    {
        $mLen  = $this->el['mLen'];
        $eLen  = $this->el['len'] * 8 - $this->el['mLen'] - 1;
        $eMax  = (1 << $eLen) - 1;
        $eBias = $eMax >> 1;
        $i     = $this->bBE ? 0 : ($this->el['len'] - 1);
        $d     = $this->bBE ? 1 : -1;
        $s     = $a[$p + $i];
        $i += $d;
        $nBits = -7;
        $e     = $s & ((1 << (-$nBits)) - 1);
        $s >>= (-$nBits);
        $nBits += $eLen;
        while ($nBits > 0) {
            $e = $e * 256 + $a[$p + $i];
            $i += $d;
            $nBits -= 8;
        }
        $m = $e & ((1 << (-$nBits)) - 1);
        $e >>= (-$nBits);
        $nBits += $mLen;
        while ($nBits > 0) {
            $m = $m * 256 + $a[$p + $i];
            $i += $d;
            $nBits -= 8;
        }

        switch ($e) {
            case 0:
                // Zero, or denormalized number
                $e = 1 - $eBias;
                break;
            case $eMax:
                // NaN, or +/-Infinity
                return $m ? null : (($s ? -1 : 1) * INF);
            default:
                // Normalized number
                $m = $m + pow(2, $mLen);
                $e = $e - $eBias;
                break;
        }
        return ($s ? -1 : 1) * $m * pow(2, $e - $mLen);

    }

    /*Pack functions
    /****************************************/
    private function _EnArray($a, $p, $l, $v)
    {
        $i = 0;
        while ($i < $l) {
            $a[$p + $i] = $v[$i] ? $v[$i] : 0;
            $i++;
        }
        return $a;
    }
    private function _EnChar($a, $p, $v)
    {
        $a[$p] = $v[0];
        return $a;
    }
    private function _EnInt($a, $p, $v)
    {
        $lsb  = $this->bBE ? ($this->el['len'] - 1) : 0;
        $nsb  = $this->bBE ? -1 : 1;
        $stop = $lsb + $nsb * $this->el['len'];
        $i    = $lsb;
        $v    = ($v < $this->el['min']) ? $this->el['min'] : ($v > $this->el['max']) ? $this->el['max'] : $v;
        while ($i != $stop) {
            $a[$p + $i] = $v & 0xff;
            $i += $nsb;
            $v >>= 8;
        }
        return $a;
    }

    private function _EnString($a, $p, $l, $v)
    {
        $i = 0;
        while ($i < $l) {
            $a[$p + $i] = $v[$i];
            $i++;
        }
        return $a;
    }
    private function _En754($a, $p, $v)
    {
        $mLen  = $this->el['mLen'];
        $eLen  = $this->el['len'] * 8 - $this->el['mLen'] - 1;
        $eMax  = (1 << $eLen) - 1;
        $eBias = $eMax >> 1;

        $s = $v < 0 ? 1 : 0;
        $v = abs($v);
        if (!is_numeric($v) || is_infinite($v)) {
            $m = !is_numeric($v) ? 1 : 0;
            $e = $eMax;
        } else {
            $e = floor(log($v) / 0.6931471805599453); // Calculate log2 of the value
            if ($v * ($c = pow(2, -$e)) < 1) {
                $e--;
                $c *= 2;}

            // Round by adding 1/2 the significand's LSD
            if ($e + $eBias >= 1) {$v += $this->el['rt'] / $c;} // Normalized:  mLen significand digits
            else { $v += $this->el['rt'] * pow(2, 1 - $eBias);} // Denormalized:  <= mLen significand digits
            if ($v * $c >= 2) {
                $e++;
                $c /= 2;} // Rounding can increment the exponent

            if ($e + $eBias >= $eMax) {
                // Overflow
                $m = 0;
                $e = eMax;
            } else if ($e + $eBias >= 1) {
                // Normalized - term order matters, as Math.pow(2, 52-e) and v*Math.pow(2, 52) can overflow
                $m = ($v * $c - 1) * pow(2, $mLen);
                $e = $e + $eBias;
            } else {
                // Denormalized - also catches the '0' case, somewhat by chance
                $m = $v * pow(2, $eBias - 1) * pow(2, $mLen);
                $e = 0;
            }
        }
        $i = $this->bBE ? ($this->el['len'] - 1) : 0;
        $d = $this->bBE ? -1 : 1;
        while ($mLen >= 8) {
            $a[$p + $i] = $m & 0xff;
            $i += $d;
            $m /= 256;
            $mLen -= 8;
        }
        $e = ($e << $mLen) | $m;
        $eLen += $mLen;
        while ($eLen > 0) {
            $a[$p + $i] = $e & 0xff;
            $i += $d;
            $e /= 256;
            $eLen -= 8;
        }
        $a[$p + $i - $d] |= $s * 128;
        return $a;
    }

    private function _UnpackSeries($n, $s, $a, $p)
    {
        $fxn = $this->el['de'];
        $rv  = array();
        $i   = 0;
        while ($i < $n) {
            $rv[] = call_user_func($fxn, $a, $p + $i * $s);
            $i++;
        }
        return $rv;
    }

    // Pack a series of n elements of size s from array v at offset i to array a at offset p with fxn
    private function _PackSeries($n, $s, $a, $p, $v, $i)
    {
        $fxn = $this->el['en'];
        $o   = 0;
        while ($o < $n) {
            $a = call_user_func($fxn, $a, $p + $o * $s, $v[$i + $o]);
            $o++;
        }
        return $a;
    }
    // Pack the supplied values into the octet array a, beginning at offset p, according to the fmt string
    private function PackTo($fmt, $a, $p, $values)
    {
        // Set the private bBE flag based on the format string - assume big-endianness
        $rv = "";
        $this->bBE = ($fmt[0] != '<');
        preg_match_all($this->_sPattern, $fmt, $t);
        $matches = array();
        $i       = 0;
        foreach ($t[0] as $key => $value) {
            $matches[] = array($t[0][$key], $t[1][$key], $t[2][$key]);
        }
        foreach ($matches as $key => $m) {
            $n = (($m[1] == null) || ($m[1] == '')) ? 1 : (int) $m[1];
            $s = $this->_elLut[$m[2]]['len'];
            if (($p + $n * $s) > count($a)) {
                return false;
            }
            switch ($m[2]) {
                case 'A':case 's':
                    if (($i + 1) > count($values)) {return false;}
                    $a = call_user_func($this->_elLut[$m[2]]['en'], $a, $p, $n, $values[$i]);
                    $tmp = array_slice($a,$p,$n);
                    $rv .= implode("",$tmp);
                    $i += 1;
                    break;
                case 'c':case 'b':case 'B':case 'h':case 'H':
                case 'i':case 'I':case 'l':case 'L':case 'f':case 'd':
                    $this->el = $this->_elLut[$m[2]];
                    if (($i + $n) > count($values)) {return false;}
                    $a = $this->_PackSeries($n, $s, $a, $p, $values, $i);
                    $tmp = array_slice($a,$p,$s);
                    array_unshift($tmp,"C*");
                    $rv .= call_user_func_array("pack",$tmp);
                    $i += $n;
                    break;
                case 'x':
                    $j = 0;
                    while ($j < $n) {
                        $rv .= pack("x");
                        $j++;
                    }
                    break;
            }
            $p += $n * $s;
        }
        return $rv;
    }
}
