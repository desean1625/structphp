# php-Struct
Python Struct Port to PHP for pack and unpack functions

The module defines the following functions:

  unpack(fmt, a, p)
    Return an array containing values unpacked from the octet array a,
  beginning at position p, according to the supplied format string.  If there
  are more octets in a than required by the format string, the excess is
  ignored.  If there are fewer octets than required, Unpack() will return
  undefined.  If no value is supplied for the p argument, zero is assumed.


  pack(fmt, values)
    Return an octet array containing the packed values array.  If there are
  more values supplied than are specified in the format string, the excess is
  ignored.  If there are fewer values supplied, Pack() will return false.  If
  any value is of an inappropriate type, the results are undefined.
  calcsize(fmt)
     Return the number of octets required to store the given format string.
  calcLength(fmt)
    same as calcsize
```
    Format | C Type         |  Size (octets) | s
    -------------------------------------------------------------------
       A   | char[]         |     Length     
       x   | pad byte       |        1      
       c   | char           |        1       
       b   | signed char    |        1       
       B   | unsigned char  |        1       
       h   | signed short   |        2      
       H   | unsigned short |        2       
       i   | signed long    |        4      
       I   | unsigned long  |        4       
       l   | signed long    |        4      
       L   | unsigned long  |        4      
       s   | char[]         |     Length     
       f   | float          |        4      
       d   | double         |        8       
```
```php
require "Struct.php";
$struct = new Struct();

 print_r($struct->unpack("<2h", pack("v2", 10, 10)));//v	unsigned short (always 16 bit, little endian byte order)
 print_r($struct->unpack(">2h", pack("n2", 10, 10)));//n	unsigned short (always 16 bit, big endian byte order)
 print_r($struct->unpack(">2L", pack("N2", 10, 10)));//N	unsigned long (always 32 bit, big endian byte order)
 print_r($struct->unpack("<2L", pack("V2", 10, 10)));//V	unsigned long (always 32 bit, little endian byte order)
```
