<?php
/*PhpDoc:
name: index.php
title: index.php
includes: [lib.inc.php, lib2.inc.php]
*/
echo "index.php<br>\n";
require_once __DIR__.'/lib.inc.php';
require_once __DIR__.'/lib2.inc.php';

fun11();
$obj = new C11;
$obj->m11();
