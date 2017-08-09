<?php
/*PhpDoc:
name:  exyamlfile.inc.php
title: exyamlfile.inc.php - définit les fonctions extractYamlFromFile() et  extractYamlFromYamlFile()
functions:
journal: |
  19/4/2017:
    typage des paramètres des méthodes (Php 7)
  3/3/2017
    Génération d'une erreur plus compréhensible en cas d'erreur d'analyse YAML dans extractYamlFromFile()
  27/11/2016
    Modification pour renvoyer null si le fichier est absent
  1/11/2016
    Ajout des fichiers Yaml
  25/10/2016
    Ajout des fichiers JS et CSS
*/
/*PhpDoc: functions
  name: extractYamlFromYamlFile
  title: function extractYamlFromYamlFile(string $filepath) - Extrait une doc PhpDoc d'un fichier Yaml
  doc: |
    Extrait une doc PhpDoc d'un fichier Yaml
*/
function extractYamlFromYamlFile(string $filepath) {
  if (!($yaml = spycLoad($filepath)))
    die("Erreur de lecture de $filepath");
  if (isset($yaml['phpDoc']))
    return $yaml['phpDoc'];
  if (isset($yaml['PhpDoc']))
    return $yaml['PhpDoc'];
  $name = (isset($yaml['name']) ? $yaml['name'] : basename($filepath));
  if (isset($yaml['title']))
    return ['name'=> $name, 'title'=> $yaml['title']];
  throw new Exception("PhpDoc non trouvé dans extractYamlFromYamlFile($filepath)");
}

/*PhpDoc: functions
  name: extractYamlFromFile
  title: function extractYamlFromFile(string $filepath) - Extrait une doc PhpDoc d'un fichier
  doc: |
    Extrait une doc PhpDoc d'un fichier SQL, HTML, JS ou CSS. L'extension du nom du fichier est utilisé pour détecter le type de fichier.
*/
function extractYamlFromFile(string $filepath) {
//  echo "function extractYamlFromFile($filepath)<br>\n";
  if (!is_file($filepath))
    return null;
  if (substr($filepath,-4)=='.php')
    return extractYamlFromPhpFile($filepath);
  elseif (substr($filepath,-4)=='.sql')
    $seps = ['start'=>'/*','end'=>'*/'];
  elseif (substr($filepath,-3)=='.js')
    $seps = ['start'=>'/*','end'=>'*/'];
  elseif (substr($filepath,-4)=='.css')
    $seps = ['start'=>'/*','end'=>'*/'];
  elseif (substr($filepath,-5)=='.html')
    $seps = ['start'=>'<!--','end'=>'-->'];
  elseif (substr($filepath,-5)=='.yaml')
    return extractYamlFromYamlFile($filepath);
  else
    throw new Exception("extension inconnue non trouvé dans extractYamlFromFile($filepath)");
  $filecontents = file_get_contents($filepath);
  $start = strpos($filecontents, $seps['start'].'PhpDoc:');
  if ($start === false) {
//    echo "start === false<br>\n";
    return '';
  }
//  echo "start=$start<br>\n";
  $start += strlen($seps['start'].'PhpDoc:');
  $end = strpos($filecontents, $seps['end'], $start);
  $yamlText = substr($filecontents, $start, $end-$start);
//    echo "start=$start, end=$end<br>\n";
//    echo "<b>texte Yaml extrait</b><pre>\n$yamlText\n</pre>\n";
  $yaml = spycLoad($yamlText);
  if (!$yaml) {
    echo "Erreur de lecture du text yaml:</b><pre>\n$yamlText\n</pre><pre>\n";
    throw new Exception("Erreur de lecture du text yaml dans extractYamlFromFile($filepath)");
  }
  $yamls[0] = ['', $yaml];
  
  while ($start = strpos($filecontents, $seps['start'].'PhpDoc:', $start)) {
    $start += strlen($seps['start'].'PhpDoc:');
    if (preg_match('! *([a-z]+)!', $filecontents, $matches, 0, $start)) {
      $name = $matches[1];
      $start += strlen($matches[0]);
    } else
      throw new Exception("nom non trouvé dans extractYamlFromFile($filepath)");
//      echo "<pre>start=$start, name=$name\n"; echo "</pre>\n";
    $end = strpos($filecontents, $seps['end'], $start);
    $yamlText = substr($filecontents, $start, $end-$start);
//      echo "<b>texte Yaml extrait</b><pre>\n$yamlText\n</pre>\n";
    $yaml = spycLoad($yamlText);
    if (!$yaml) {
      echo "Erreur de lecture du text yaml:</b><pre>\n$yamlText\n</pre><pre>\n";
      throw new Exception("Erreur de lecture du text yaml dans extractYamlFromFile($filepath)");
    }
    $yamls[] = [$name, $yaml];
  }
//  echo "<pre>yamls avant agrégation="; print_r($yamls);  echo "</pre>\n";
  
  while (count($yamls) > 1) {
    list($name,$yaml) = array_pop($yamls);
//      echo "name='$name'<br>\n";
    $n = count($yamls);
    for ($i=$n-1; $i>=0; $i--)
      if (array_key_exists($name, $yamls[$i][1])) {
        if (!$yamls[$i][1][$name])
          $yamls[$i][1][$name] = [];
        array_unshift($yamls[$i][1][$name], $yaml);
        break;
      }
    if ($i==-1) {
      echo "<b>Erreur: dans le fichier $filepath l'élément Yaml extrait '$name' suivant:</b>\n<pre>yaml="; print_r($yaml);
      echo "</pre><b>ne peut pas être ajouté dans les éléments :</b><pre>yamls="; print_r($yamls);
      throw new Exception("Erreur name '$name' non trouvé pour l'affectation dans extractYamlFromFile($filepath)");
    }
  }
//  echo "<pre>yamls après agrégation="; print_r($yamls);  echo "</pre>\n";
  return $yamls[0][1];
}
