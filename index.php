<?php
/*PhpDoc:
name:  index.php
title: index.php - affichage de la doc PhpDoc à partir du root.pser + accès par path
includes: [ root.yaml, inc.php ]
functions:
doc: |
  Récriture de phpdoc dans un souci de simplification du code notamment pour l'utiliser dans un contexte où les ressources
  ne sont pas toutes présentes
journal: |
  27/4/2019:
    première version
*/
require_once __DIR__.'/inc.php';

$context = [
  'verbose' => ($_SERVER['SERVER_NAME']<>'geobases.alwaysdata.net'), // verbose est vrai sauf sur geobases.alwaysdata.net
];

echo "<html><head><meta charset='UTF-8'><title>phpdoc2</title></head><body>\n";

/*PhpDoc: functions
name:  phpdoc
title: "function phpdoc(array $context): void - Permet d'effectuer une relecture des fichiers phpdoc dans certains cas"
doc: |
  Permet d'effectuer une relecture des fichiers phpdoc lors de la suppression d'un phpdocagg.yaml
*/
function phpdoc(array $context): void {
  if (isset($_GET['path'])) { // affichage par path
    if ($module = Module::getByPath($_GET['path'])) {
      $module->show();
    }
    elseif ($module = Module::getByPath(dirname($_GET['path']))) {
      $file = $module->findChildByName(basename($_GET['path']));
      $file->show();
    }
    else {
      echo "$_GET[path] non trouvé<br>\n";
    }
    die();
  }
  
  $action = isset($_GET['action']) ? $_GET['action'] : 'show';

  if ($action == 'update') { // actualisation de root.pser à partir de root.yaml 
    $root = new Module(Elt::read_yaml(__DIR__.'/root.yaml'), $context);
    $root->solveLinks($root);
    $root->store(__DIR__.'/root.pser');
    $action = 'show';
  }
  else { // sinon lecture de root.pser
    $root = Module::read(__DIR__.'/root.pser');
  }

  if (isset($_GET['key']) && $_GET['key']) {
    $key = explode('/',$_GET['key']);
    array_shift($key);
    $elt = $root->access($key);
    $elt->$action($context);
  } else {
    $root->$action($context);
  }
  $key = (isset($_GET['key']) && $_GET['key']) ? "&amp;key=".rawurlencode($_GET['key']) : '';
  if (!isset($_GET['action']) || ($_GET['action']<>'update'))
    echo "<a href='?action=update$key'>Actualisation de la base</a><br>\n";
  else
    echo "<a href='?action=show$key'>Utilisation de la base existante</a><br>\n";
}
phpdoc($context);
