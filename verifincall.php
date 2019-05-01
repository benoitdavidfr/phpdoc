<?php
{/*PhpDoc:
name:  verifincall.php
title: verifincall.php - vérifie toutes les inclusions
includes: [ root.yaml, inc.php ]
functions:
doc: |
journal: |
  1/5/2019:
    - ajout inc.php
  30/4/2019:
    première version
*/}
require_once __DIR__.'/inc.php';

function verifInc(Module $module) {
  $path = $module->name();
  echo "<h1>$path</h1>\n";
  $module->verifyInc();
  foreach($module->children('submodules') as $submodule) {
    verifInc($submodule);
  }
}

$context = [
  'verbose' => ($_SERVER['SERVER_NAME']<>'geobases.alwaysdata.net'), // verbose est vrai sauf sur geobases.alwaysdata.net
];

$root = new Module(Elt::read_yaml(__DIR__.'/root.yaml'), $context);
$root->solveLinks();
$root->store(__DIR__.'/root.pser');
//$root->dump();
verifInc($root);
