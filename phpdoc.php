<?php
{/*PhpDoc:
name:  phpdoc.php
title: phpdoc.php - affichage de la doc PhpDoc
includes: [ root.yaml, inc.php ]
functions:
doc: |
  Récriture de phpdoc dans un souci de simplification du code notamment pour l'utiliser dans un contexte où les ressources
  ne sont pas toutes présentes
journal: |
  1/5/2019:
    - ajout inc.php
  27-28/4/2019:
    - remplacement de Spyc par le module Yaml de Symfony
    - améliorations diverses
  19/4/2017:
    typage des paramètres des méthodes (Php 7)
  4/12/2016:
    Ajout de la fonction phpdoc() permettant d'effectuer une relecture des fichiers phpdoc lors de la suppression d'un phpdocagg.yaml
  26-27/11/2016:
    première version
*/}
require_once __DIR__.'/inc.php';

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

echo "<html><head><meta charset='UTF-8'><title>phpdoc2</title></head><body>\n";

$context = [
  'verbose' => ($_SERVER['SERVER_NAME']<>'geobases.alwaysdata.net'), // verbose est vrai sauf sur geobases.alwaysdata.net
];

/*PhpDoc: functions
name:  phpdoc
title: "function phpdoc(array $context): void - Permet d'effectuer une relecture des fichiers phpdoc"
doc: |
  Permet d'effectuer une relecture des fichiers phpdoc lors de la suppression d'un phpdocagg.yaml
*/
function phpdoc(array $context): void {
  //echo "phpdoc(): ligne ",__LINE__,"<br>\n";
  //echo "<pre>context="; print_r($context); echo "</pre>\n";

  $root = new Module(Elt::read_yaml(__DIR__.'/root.yaml'), $context);
  //echo "<pre>root="; print_r($root);
  $root->solveLinks();
  $root->store(__DIR__.'/root.pser');
  if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if (isset($_GET['key'])) {
      $key = explode('/',$_GET['key']);
      array_shift($key);
      $elt = $root->access($key);
      $elt->$action($context);
    } else
      $root->$action($context);
  }
  else {
    $root->show($context);
  }
}
phpdoc($context);
