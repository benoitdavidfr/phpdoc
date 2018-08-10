<?php
/*PhpDoc:
name:  calltab.php
title: calltab.php - Construction d'un tableau d'appels
includes: [ '../../phplib/yaml.inc.php', mysqlprms.inc.php ]
doc: |
  Construction pour un fichier source Php pour chaque fonction et méthode de la liste des appels identifiés par
  le nom de la fonction ou de la méthode dans laquelle l'appel est effectué et du no de ligne de l'appel.
  Il s'agit d'une aide pour identifier notamment des fonctions inutiles.
  le résultat peut être approximatif.
  A FAIRE:
    devrait être étendu en multi-fichiers
journal: |
  13/1/2016:
    première version
*/
//$phpsrc = '../../geom2d/tiledpolyg.inc.php';
//$phpsrc = 'test.php';

if (!isset($_GET['src']) or !is_file("../../$_GET[src]")) {
  $dir = '../..'.(isset($_GET['src']) ? "/$_GET[src]" : '');
  $dh = opendir($dir);
  while (($file = readdir($dh)) !== false) {
    $src = (isset($_GET['src']) ? "$_GET[src]/" : '').$file;
    echo "<a href='?src=",urlencode($src),"'>$file</a><br>\n";
  }
  closedir($dh);
  die();
}

$phpsrc = "../../$_GET[src]";
if (!($contents = @file_get_contents($phpsrc))) {
  $error = error_get_last();
  die("Erreur d'ouverure de $phpsrc : \"$error[message]\" dans $error[file] ligne $error[line]");
}
$tokens = token_get_all($contents);

echo "<html><head><meta charset='UTF-8'><title>calltab</title></head><body>\n";
$action = (isset($_GET['action']) ? $_GET['action'] : null);
$actions = [];

$actions['show_tokens'] = "affichage du tableau des tokens";
if ($action=='show_tokens') {
  echo "<table border=1><th>noligne</th><th>token</th><th>srce</th>\n";
  foreach ($tokens as $token) {
    echo "<tr><td>";
    if (is_array($token))
      echo $token[2],'</td><td><i>',token_name($token[0]),'</i></td><td>',htmlspecialchars($token[1]);
    else
      echo '</td><td>',$token;
    echo "</td></tr>\n";
  }
  echo "</table>\n";
  die("FIN ligne ".__LINE__."\n");
}

$actions['show_funcclassmethod'] = "affichage les fonctions, classes et méthodes";
if ($action=='show_funcclassmethod') {
/*[ classname => [             // nom de la classe ou '' pour les fonctions
    'filename' => nom du fichier
    'lineno' => no_de_ligne,   // no de ligne de définition de la classe
    'functions' => [
      funcname => [
        lineno => no_de_ligne, // no de ligne de définition de la méthode ou de la focntion
        'calls' => [ [
          'funcname' => nom de la fonction depuis laquelle l'appel est effectué
          'lineno' => no de ligne de l'appel
        ] ]
      ]
    ]
  ] ]
*/
  $classes = [ '' => ['lineno'=>0, 'functions'=>[]] ]; // la classe ayant pour nom '' a les fonctions comme méthodes
  $functions = []; // [ name => ['classname'=>classname] ]
// 1ère phase de construction de la liste des fonctions, classes et méthodes
  $classname = '';
  $braceLevel = 0;
  foreach ($tokens as $no => $token)
    if (is_array($token)) {
      $lineno = $token[2];
      switch ($token[0]) {
        case T_CLASS :
          $name = $tokens[$no+2][1];
//          echo "$lineno : class $name<br>\n";
          $classname = $name;
          $classes[$classname] = ['lineno'=>$lineno, 'functions'=>[]];
          break;
        case T_FUNCTION :
          $name = $tokens[$no+2][1];
//          echo "braceLevel=$braceLevel<br>\n";
          if ($braceLevel==0) { // définition d'une fonction
//            echo "$lineno : function $name<br>\n";
            $classes['']['functions'][$name] = ['lineno'=>$lineno];
            $functions[$name] = ['classname'=>''];
          }
          else { // définition d'une méthode d'une classe
//            echo "$lineno : method $classname::$name<br>\n";
            $classes[$classname]['functions'][$name] = ['lineno'=>$lineno];
            $functions[$name] = ['classname'=>(isset($functions[$name]) ? '*' : $classname)];
// si plusieurs méthodes portent le même nom => '*'
          }
          break;
      }
    }
    else
      switch($token) {
        case '{': $braceLevel++; break;
        case '}': $braceLevel--; break;
      }
  
//  echo "<pre>classes="; print_r($classes); echo "</pre>\n";
//  echo "<pre>functions="; print_r($functions); echo "</pre>\n";
  
// 2nd phase pour détecter les appels aux fonctions et méthodes
  $classname = '';
  $braceLevel = 0;
  $braceLevel = 0;
  foreach ($tokens as $no => $token) {
    if (is_array($token)) {
      $lineno = $token[2];
      switch ($token[0]) {
        case T_CLASS : // mémorisation de la classe courante
          $classname = $tokens[$no+2][1];
//          echo "$lineno> T_CLASS $classname<br>\n";
          break;
        case T_FUNCTION : // mémorisation de la fonction ou méthode courante
          $funcname = ($braceLevel ? $classname.'::' : '').$tokens[$no+2][1];
//          echo "$lineno> T_FUNCTION $funcname<br>\n";
          break;
        case T_NEW:
          $cname = $tokens[$no+2][1]; // classe faisant l'objet du new
//          echo "$lineno> T_NEW $cname<br>\n";
          if (isset($classes[$cname]))
            $classes[$cname]['functions']['__construct']['calls'][] = ['funcname'=>$funcname, 'lineno'=>$lineno];
          break;
        case T_STRING:
          $string = $token[1];
// je considère qu'un string suivi d'une ( correspond à un appel de fonction ou de méthode
// si cette string est déjà reprée comme nom de fonction ou de méthode
          if (isset($functions[$string]) and ($tokens[$no+1]=='(')) {
            $cname = $functions[$string]['classname']; // classe portant la méthode ou '' pour une fonction
            if ($cname<>'*') { // si plusieurs méthodes ont même nom, j'abandonne (ex. draw())
              if ($classes[$cname]['functions'][$string]['lineno'] <> $lineno) // je vérifie qu'il ne s'agit pas de la définition
//                echo "$lineno> callto $cname::$string<br>\n";
                $classes[$cname]['functions'][$string]['calls'][] = ['funcname'=>$funcname, 'lineno'=>$lineno];
//              else echo "$lineno> def $cname::$string<br>\n";
            }
//            else echo "$lineno> callto *::$string<br>\n";
          }
          break;
      }
    }
    else
      switch($token) {
        case '{': $braceLevel++; break;
        case '}':
          $braceLevel--;
          if (($braceLevel==1) and ($classname<>'')) // je sors d'une méthode
            $funcname = '';
          if ($braceLevel==0) {
            if ($classname=='') // je sors d'une fonction
              $funcname = '';
            else
              $classname = '';
          }
          break;
      }
  }
//  echo "<pre>classes="; print_r($classes); echo "</pre>\n";

// Affichage du résultat
  echo "<h2>Appels aux fonctions et méthodes de /$_GET[src]</h2>\n";
  foreach ($classes as $classname => $class) {
    if ($classname)
      echo "<h3>class $classname",(isset($class['lineno'])?" ($class[lineno])":''),"</h3>\n";
    elseif ($class['functions'])
      echo "<h3>Functions</h3>\n";
    echo "<ul>\n";
    foreach ($class['functions'] as $funcname => $func)
      if (($funcname<>'__construct') and ($functions[$funcname]['classname']=='*'))
        echo "<li><s>$funcname",(isset($func['lineno'])?" ($func[lineno])":''),"</s>\n";
      else {
        echo "<li>$funcname",(isset($func['lineno'])?" ($func[lineno])":''),"<ul>\n";
        if (isset($func['calls'])) {
          $callfuncs = [];
          foreach ($func['calls'] as $call)
            $callfuncs[$call['funcname']][] = $call['lineno'];
          foreach ($callfuncs as $fname => $linenos)
            echo "<li>$fname (",implode(',',$linenos),")\n";
        }
        echo "</ul>\n";
      }
    echo "</ul>\n";
  }
  die("FIN ligne ".__LINE__."\n");
}

foreach ($actions as $name => $title)
  echo "<a href='?action=$name&amp;src=",urlencode($_GET['src']),"'>$title</a><br>\n";
