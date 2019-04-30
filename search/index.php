<?php
/*PhpDoc:
name:  index.php
title: search/index.php - trouve les lignes de code Php appelant la méthode ou fonction définie en paramètre
includes:
  - ../../vendor/autoload.php
  - ../root.yaml
  - ../elt.inc.php
  - ../module.inc.php
  - ../htmlfile.inc.php
  - ../exyamlfile.inc.php
  - ../phpfile.inc.php
  - ../exyamlphp.inc.php
  - ../sql.inc.php
  - ../synchro.inc.php
functions:
doc: |
  Prend en paramètres:
    - file : chemin du fichier Php dans lequel la méthode est définie
    - class : nom de la classe pour laquelle la méthode est définie
    - method : nom de la méthode
  ou:
    - file : chemin du fichier Php dans lequel la fonction est définie
    - function : nom de la fonction
  ou:
    - file : chemin d'un fichier Php pour lequel les fonction et classes/méthodes seront listées
  ou aucun et dans ce cas liste les modules et fichiers à partir de la racine

  Utilise les liens d'inclusion de fichiers pour restreindre l'analyse des sources Php
journal: |
  30/4/2019:
    ajout détection de new
  29/4/2019:
    première version
*/
require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../elt.inc.php';
require_once __DIR__.'/../module.inc.php';
require_once __DIR__.'/../htmlfile.inc.php';
require_once __DIR__.'/../exyamlfile.inc.php';
require_once __DIR__.'/../phpfile.inc.php';
require_once __DIR__.'/../exyamlphp.inc.php';
require_once __DIR__.'/../sql.inc.php';
require_once __DIR__.'/../synchro.inc.php';

//echo "<pre>_GET="; print_r($_GET); echo "</pre>\n";
if (!isset($_GET['file'])) { // choix d'un fichier ou d'un module
  $root = Module::read(__DIR__.'/../root.pser');
  //$root->dump();
  echo "Modules à la racine:<ul>\n";
  foreach($root->children('submodules') as $submodule) {
    $path = $submodule->name();
    echo "<li><a href='?file=$path'>$path</a></li>\n";
  }
  echo "</ul>\n";
  die();
}

/*PhpDoc: functions
name:  listOfClassesMethodsAndFunctionsDefinedByFile
title: "function listOfClassesMethodsAndFunctionsDefinedByFile(string $path): array - extrait la liste des classes, méthodes et fonctions définies dans un fichier Php défini par son path"
doc: |
  le résultat est: [ ''=> [{function_name}=> {lineNo}], {class_name}=> [{method_name}=> {lineNo}]]
  Les fonctions doivent être définies avant les classes
*/
function listOfClassesMethodsAndFunctionsDefinedByFile(string $path): array {
  $verbose = false;
  //$verbose = true;
  $phpsource = file_get_contents($path);
  $tokens = token_get_all($phpsource);
  if ($verbose) echo "<pre>\n";
  $currentClass = '';
  $classes = [];
  $methods = [];
  foreach ($tokens as $itoken => $token) {
    if ($verbose) {
      if (is_array($token))
        echo "Line $token[2]: ",token_name($token[0])," ('",str_replace(['<',"\n"],['&lt;','\n'],$token[1]),"')\n";
      else
        echo "L: $token\n";
    }
    if (is_array($token) && ($token[0]==T_CLASS)
      && ($token1=$tokens[$itoken+1]) && is_array($token1) && ($token1[0]==T_WHITESPACE)
      && ($token2=$tokens[$itoken+2]) && is_array($token2) && ($token2[0]==T_STRING)) {
        if ($verbose) { echo "$itoken "; print_r($token); }
        if ($currentClass)
          $classes[$currentClass] = $methods;
        elseif ($methods)
          $classes[$currentClass] = $methods;
        $currentClass = $token2[1];
        $methods = [];
    }
    elseif (is_array($token) && ($token[0]==T_FUNCTION)
      && ($token1=$tokens[$itoken+1]) && is_array($token1) && ($token1[0]==T_WHITESPACE)
      && ($token2=$tokens[$itoken+2]) && is_array($token2) && ($token2[0]==T_STRING)) {
        if ($verbose) { echo "$itoken "; print_r($token); }
        $methods[$token2[1]] = $token2[2];
    }
  }
  if ($methods)
    $classes[$currentClass] = $methods;
  if ($verbose) { echo "classes="; print_r($classes); echo "</pre>\n"; }
  return $classes;
}

if (!is_file(__DIR__."/../..$_GET[file]")) { // si module alors choix d'un sous-module ou d'un fichier
  echo "<h2>Module $_GET[file]</h2>\n";
  $module = Module::getByPath($_GET['file']);
  //echo "module=$module<br>\n";
  foreach (['submodules','phpScripts','phpIncludes'] as $childCat) {
    if ($module->children($childCat)) {
      echo "<h3>$childCat</h3><ul>\n";
      foreach($module->children($childCat) as $child) {
        $spath = ($childCat == 'submodules' ? '' : "$_GET[file]/").$child->name();
        echo "<li><a href='?file=$spath'>$spath</a></li>\n";
      }
      echo "</ul>\n";
    }
  }
  die();
}

if (isset($_GET['action']) && ($_GET['action']=='token')) { // affichage des tokens
  echo "<h2>$_GET[file]</h2>\n";
  echo "<table border=1>",
    "<tr bgcolor='#32CD32'><td>S</td><td>Source</td></tr>\n",
    "<tr><td>T</td><td>Token</td></tr>\n",
    "<tr bgcolor='#E6E6FA'><td>L</td><td>Litteral</td></tr>\n",
    "</table>\n";
  $phpsource = file_get_contents(__DIR__."/../..$_GET[file]");
  $tokens = token_get_all($phpsource);
  $line = 1;
  echo "<pre><table border=1>\n";
  foreach ($tokens as $itoken => $token) {
    while (is_array($token) && ($line <= $token[2])) {
      echo "<tr bgcolor='#32CD32'><td>S</td><td>$line</td>",
        "<td colspan=3>",str_replace('<','&lt',getLineOfFile($line, $phpsource)),"</td></tr>\n";
      $line++;
    }
    if (is_array($token))
      echo  "<tr><td>T</td><td>$token[2]</td>",
        "<td>",token_name($token[0]),"</td>",
        "<td>",str_replace('<','&lt;',$token[1]),"</td></tr>\n";
    else
      echo "<tr bgcolor='#E6E6FA'><td>L</td><td colspan=4>$token</td></tr>\n";
  }
  die("</table></pre>");
}

if (!(isset($_GET['class']) && isset($_GET['method'])) && !isset($_GET['function'])) { // si fichier
  // alors choix d'une classe/méthode définie dans le fichier
  echo "<h2><a href='?file=$_GET[file]&amp;action=token'>$_GET[file]</a></h2>\n";
  $path = __DIR__."/../..$_GET[file]";
  $phpsource = file_get_contents($path);
  foreach (listOfClassesMethodsAndFunctionsDefinedByFile($path) as $class => $methods) {
    echo "<h3>",!$class ? 'fonctions' : "classe $class","</h3><ul>";
    foreach ($methods as $method => $line) {
      $text = getLineOfFile($line, $phpsource);
      if (!$class)
        echo "<li><a href='?file=$_GET[file]&amp;function=$method'>$text</a></li>\n";
      else
        echo "<li><a href='?file=$_GET[file]&amp;class=$class&amp;method=$method'>$text</a></li>\n";
    }
    echo "</ul>\n";
  }
  die();
}


$context = [
  'verbose' => ($_SERVER['SERVER_NAME']<>'geobases.alwaysdata.net'), // verbose est vrai sauf sur geobases.alwaysdata.net
];

// prend un ensemble de File et renvoie un ensemble de File
// ajoute par appel récursif les liens, s'arrête quand plus aucun fichier n'est ajouté
// $linkType vaut 'links' ou 'reverseLinks'
function transitiveClosure(string $linkType, array $files, string $category='includes'): array {
  //echo "transitiveClosure(linkType=$linkType)<br>\n";
  $newFiles = [];
  foreach ($files as $file) {
    //echo "file=$file<br>\n";
    foreach ($file->$linkType($category) as $link) {
      //echo "link=$link<br>\n";
      if (!in_array($link, $files) && !in_array($link, $newFiles))
        $newFiles[] = $link;
    }
  }
  if (!count($newFiles))
    return $files;
  else
    return transitiveClosure($linkType, array_merge($files, $newFiles), $category);
}

// retourne les chemins du fichier passé en paramètre
// + de tous les fichiers incluant récursivement ce fichier
// + de tous les fichiers inclus récursivement dans ces fichiers
function filepathsOfTC(File $file): array {
  $filepaths = [];
  foreach (transitiveClosure('links', transitiveClosure('reverseLinks', [$file])) as $f) {
    $filepaths[] = $f->path();
  }
  return $filepaths;
}

// extrait une ligne du code source
function getLineOfFile(int $line, string $fileContents): string {
  $tab = explode("\n", $fileContents);
  //print_r($tab);
  return $tab[$line-1];
}

echo "<html><head><meta charset='UTF-8'><title>search</title></head><body>\n";

$module = Module::getByPath(dirname($_GET['file']));
//echo "module=",$module? $module : 'null',"<br>\n";

$file = $module->findChildByName(basename($_GET['file']));

if (0) { // TEST de transitiveClosure() 
  echo "Test de transitiveClosure() sur ",$file->path(),"<br>\n";
  //echo "transitiveClosureUp:<br>\n";
  $tcup = transitiveClosure('reverseLinks', [$file]);
  //foreach ($tcup as $tc) echo "file ",$tc->path(),"<br>\n";
  $tcdown = transitiveClosure('links', $tcup);
  echo "<ul>\n";
  foreach ($tcdown as $tc) echo "<li>",$tc->path(),"\n";
  die("</ul>Fin Test de transitiveClosure() ligne ".__LINE__);
}

//$file->dump();
if (isset($_GET['method']))
  echo "<h1>Method $_GET[file]#$_GET[class]::$_GET[method]</h1>\n";
else
  echo "<h1>Function $_GET[file]#$_GET[function]</h1>\n";
$filepaths = filepathsOfTC($file);

foreach($filepaths as $path) {
  echo "<h2>$path</h2><pre>\n";
  $phpsource = file_get_contents(__DIR__."/../..$path");
  $tokens = token_get_all($phpsource);
  if (isset($_GET['method'])) {
    // détecte enchainement (T_DOUBLE_COLON|T_OBJECT_OPERATOR) $_GET[method]
    foreach ($tokens as $itoken => $token) {
      //echo is_array($token) ? "Line {$token[2]}: ".token_name($token[0])." ('{$token[1]}')\n" : "$token\n";
      if (is_array($token) && in_array(token_name($token[0]), ['T_DOUBLE_COLON','T_OBJECT_OPERATOR'])) {
        $token1 = $tokens[$itoken+1];
        if (is_array($token1) && (token_name($token1[0])=='T_STRING') && ($token1[1]==$_GET['method'])) {
          $line = $token[2];
          //echo "$_GET[method] détecté ligne $line<br>\n";
          printf('%3d: %s%s', $line, str_replace(['<'],['&lt;'],getLineOfFile($line, $phpsource)),"\n");
        }
      }
      elseif (($_GET['method']=='__construct') && is_array($token) && ($token[0]==T_NEW)) {
        $line = $token[2];
        printf('%3d: %s%s', $line, str_replace(['<'],['&lt;'],getLineOfFile($line, $phpsource)),"\n");
      }
    }
  }
  else { // function
    // détecte enchainement $_GET[function] '(' non précédé de T_FUNCTION
    foreach ($tokens as $itoken => $token) {
      //echo is_array($token) ? "Line {$token[2]}: ".token_name($token[0])." ('{$token[1]}')\n" : "$token\n";
      if (is_array($token) && ($token[0]==T_STRING) && ($token[1]==$_GET['function'])) {
        $token1 = $tokens[$itoken+1];
        if (!is_array($token1) && ($token1=='(')) {
          $tokenm2 = $tokens[$itoken-2];
          if (!is_array($tokenm2) || ($tokenm2[0]<>T_FUNCTION)) {
            $line = $token[2];
            //echo "$_GET[function] détecté ligne $line<br>\n";
            printf('%3d: %s%s', $line, str_replace(['<'],['&lt;'],getLineOfFile($line, $phpsource)),"\n");
          }
        }
      }
    }
  }
  echo "</pre>\n";
}

die("Fin Ok");
/*
find . -type f -name "*.php" -exec grep -il 'pattern' {} \;
Pour trouver les fichiers incluant un fichier donné
find .. -type f -name "*.php" -exec grep -il '/gegeom/gegeom.inc.php' {} \;
find .. -type f -name "*.php" -exec grep -il 'require' {} \;
find .. -type f -name "*.php" -exec grep -i 'require' {} \;
*/