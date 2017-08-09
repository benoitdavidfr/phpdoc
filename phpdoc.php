<?php
/*PhpDoc:
name:  phpdoc.php
title: phpdoc.php - affichage de la doc PhpDoc
includes:
  - root.yaml
  - elt.inc.php
  - module.inc.php
  - htmlfile.inc.php
  - exyamlfile.inc.php
  - phpfile.inc.php
  - exyamlphp.inc.php
  - sql.inc.php
  - synchro.inc.php
  - '..spyc/spyc2.inc.php'
functions:
doc: |
  Récriture de phpdoc dans un souci de simplification du code notamment pour l'utiliser dans un contexte où les ressources
  ne sont pas toutes présentes
journal: |
  19/4/2017:
    typage des paramètres des méthodes (Php 7)
  4/12/2016:
    Ajout de la fonction phpdoc() permettant d'effectuer une relecture des fichiers phpdoc lors de la suppression d'un phpdocagg.yaml
  26-27/11/2016:
    première version
*/
require_once 'elt.inc.php';
require_once 'module.inc.php';
require_once 'htmlfile.inc.php';
require_once 'exyamlfile.inc.php';
require_once 'phpfile.inc.php';
require_once 'exyamlphp.inc.php';
require_once 'sql.inc.php';
require_once 'synchro.inc.php';
require_once '../spyc/spyc2.inc.php';

echo "<html><head><meta charset='UTF-8'><title>phpdoc2</title></head><body>\n";

$context = [
  'verbose' => ($_SERVER['SERVER_NAME']<>'geobases.alwaysdata.net'), // verbose est vrai sauf sur geobases.alwaysdata.net
];

/*PhpDoc: functions
name:  phpdoc
title: function phpdoc(array $context)
doc: |
  Permet d'effectuer une relecture des fichiers phpdoc lors de la suppression d'un phpdocagg.yaml
*/
function phpdoc(array $context) {
//  echo "phpdoc(): ligne ",__LINE__,"<br>\n";
//  echo "<pre>context="; print_r($context); echo "</pre>\n";

  $root = new Module(Elt::read_yaml('root.yaml'), $context);
//  echo "<pre>root="; print_r($root);
  $root->solveLinks();
  switch (isset($_GET['action']) ? $_GET['action'] : null) {
    case null:
      $root->show($context);
      break;
    default:
      $action = $_GET['action'];
      if (isset($_GET['key'])) {
        $key = explode('/',$_GET['key']);
        array_shift($key);
        $elt = $root->access($key);
        $elt->$action($context);
      } else
        $root->$action($context);
  }
}
phpdoc($context);