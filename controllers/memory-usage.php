<?php
function convert($size)
{
   $unit=array('b','kb','mb','gb','tb','pb');
   return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

echo convert(memory_get_usage()) . "\n";

$a = str_repeat("Hello", 99999999);

echo convert(memory_get_usage()) . "\n";

unset($a);

echo convert(memory_get_usage()) . "\n";
