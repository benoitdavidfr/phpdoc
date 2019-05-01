<?php
{/*PhpDoc:
name:  verifincall.php
title: verifincall.php - vérifie toutes les inclusions
includes:
  - ../vendor/autoload.php
  - root.yaml
  - elt.inc.php
  - module.inc.php
  - htmlfile.inc.php
  - exyamlfile.inc.php
  - phpfile.inc.php
  - exyamlphp.inc.php
  - sql.inc.php
  - synchro.inc.php
functions:
doc: |
journal: |
  30/4/2019:
    première version
*/}
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/elt.inc.php';
require_once __DIR__.'/module.inc.php';
require_once __DIR__.'/htmlfile.inc.php';
require_once __DIR__.'/exyamlfile.inc.php';
require_once __DIR__.'/phpfile.inc.php';
require_once __DIR__.'/exyamlphp.inc.php';
require_once __DIR__.'/sql.inc.php';
require_once __DIR__.'/synchro.inc.php';

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
