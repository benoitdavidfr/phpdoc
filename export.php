<?php
/*PhpDoc:
name: export.php
title: export.php - export de la doc en JSON
includes: [ root.yaml, inc.php ]
*/
require_once __DIR__.'/inc.php';

header('Content-type: application/json');
//header('Content-type: text/plain');

$structure = [];
foreach (['Module','HtmlFile','PhpFile','Screen','FunClassVar','Method','Parameter','Type',
          'SqlFile','SqlDB','SqlTable','Column','Synchro'] as $class) {
  $structure[$class] = isset($class::$structure) ? $class::$structure : null;
}

$root = Module::read(__DIR__.'/root.pser');
$docs = $root->asArray();
echo json_encode([
  'title'=> "Export de la documentation PhpDoc",
  'description'=> "Export de la doucumentation:
 - structures définit la structure classes/propriétés/liens de la doc
 - titles indique les titres à utiliser pour afficher la doc
 - modules liste récursivement les modules",
  'issued'=> date(DateTime::ATOM, time()),
  'structures'=> $structure,
  'titles'=> Elt::$titles,
  'modules'=> $docs['submodules'],
], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
