<?php
{/*PhpDoc:
name:  stats.php
title: stats.php - statistiques sur les fichiers les plus réutilisés
includes: [ root.yaml, inc.php ]
functions:
classes:
doc: |
  L'indicateur est le nbre de lignes de fichiers impactés par un fichier donné
journal: |
  1/5/2019:
    première version
*/}
require_once __DIR__.'/inc.php';
require_once __DIR__.'/search/trclosure.inc.php';

class Stats {
  private $root;
  private $nbLinesPerFile; // [ {path} => {nblines} ] - nblignes des fichiers indexé par fichier Php
  private $nbImpactedLines; // [ {path} => {nblines} ] - nblignes de code impacté par un fichier
  
  function __construct(Module $root) { $this->root = $root; $this->nbLinesPerFile = []; }
  
  function calcNbLinesPerFile(Module $module=null) {
    if (!$module)
      $module = $this->root;
    //echo "module: ",$module->name(),"<br>\n";
    foreach(array_merge($module->children('phpScripts'), $module->children('phpIncludes')) as $phpFile) {
      $path = $module->name().'/'.$phpFile->name();
      //echo "phpFile: $path<br>\n";
      $srce = file_get_contents(__DIR__."/..$path");
      $nblines = count(explode("\n", $srce));
      //echo "nblines=$nblines<br>\n";
      $this->nbLinesPerFile[$path] = $nblines;
    }
    
    foreach($module->children('submodules') as $submodule) {
      $this->calcNbLinesPerFile($submodule);
    }
  }
  
  function calcNbImpactedLines(Module $module=null) {
    if (!$module)
      $module = $this->root;
    //echo "module: ",$module->name(),"<br>\n";
    foreach(array_merge($module->children('phpScripts'), $module->children('phpIncludes')) as $phpFile) {
      $path = $module->name().'/'.$phpFile->name();
      $nblines = 0;
      foreach (filepathsOfTC($phpFile) as $tcpath) {
        if (isset($this->nbLinesPerFile[$tcpath]))
          $nblines += $this->nbLinesPerFile[$tcpath];
      }
      $this->nbImpactedLines[$path] = $nblines;
    }
    
    foreach($module->children('submodules') as $submodule) {
      $this->calcNbImpactedLines($submodule);
    }
  }
  
  function showNbImpactedLines() {
    arsort($this->nbImpactedLines);
    echo "<table border=1>\n";
    foreach ($this->nbImpactedLines as $path => $nblines) {
      echo "<tr><td><a href='?file=$path'>$path</a></td><td align='right'>$nblines</td></tr>\n";
    }
    echo "</table>\n";
  }
  
  function showImpactedFiles(string $path) {
    $phpFile = Module::getByPath(dirname($path))->findChildByName(basename($path));
    echo "<table border=1>\n";
    foreach (filepathsOfTC($phpFile) as $tcpath) {
      $nblines = count(explode("\n", file_get_contents(__DIR__."/..$tcpath")));
      echo "<tr><td><a href='index.php?path=$tcpath'>$tcpath</td><td align='right'>$nblines</td></tr>\n";
    }
    echo "</table>\n";
  }
};

if (!isset($_GET['file'])) {
  $stats = new Stats(Module::read(__DIR__.'/root.pser'));
  $stats->calcNbLinesPerFile(); // calcul de $nbLinesPerFile
  $stats->calcNbImpactedLines(); // calcul de $nbImpactedLines
  $stats->showNbImpactedLines(); // affichage des stats  
}
else {
  $stats = new Stats(Module::read(__DIR__.'/root.pser'));
  $stats->calcNbLinesPerFile(); // calcul de $nbLinesPerFile
  $stats->showImpactedFiles($_GET['file']);
}