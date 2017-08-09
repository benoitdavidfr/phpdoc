<?php
/*PhpDoc:
name:  exyamlphp.inc.php
title: exyamlphp.inc.php - définit la fonction extractYamlFromPhpFile()
functions:
*/

/*PhpDoc: functions
name: extractYamlFromPhpFile
title: function extractYamlFromPhpFile($filepath)
doc: |
  Extrait d'un fichier Php la documentation PhpDoc en YAML contenue dans des commentaires commencant par /*PhpDoc:
  et retourne la structure Php correspondant à cette documentation.
  Les champs YAML doivent correspondre à ceux définis pour un Script ou un PhpInclude.
  Le code YAML peut être répartis en plusieurs commentaires. Dans ce cas, dans les commentaires autres que le premier
  la chaine /*PhpDoc: est suivi d'un blanc et d'une chaine définissant le champ concerné d'un des commentaires précédents.
  Un modificateur peut aussi être ajouté après la chaine définissant le champ
  Retourne null is le fichier n'existe pas
  
journal: |
  19/4/2017:
    typage des paramètres des méthodes (Php 7)
  14/1/2017:
    ajout de la possibilité d'ajouter une chaine derrière '/*PhpDoc:' qui sera recopiée dans le titre
    L'objectif est d'afficher une chaine sur cette première ligne visible quand le commentaire est replié
  26/11/2016:
    adaptation pour PhpDoc2
  30/5/2015:
    Nouvelle version utilisant token_get_all() au lieu de la recherche de la chaine/*PhpDoc
*/

function extractYamlFromPhpFile(string $filepath) {
//  echo "extractYamlFromPhpFile(filepath=$filepath)<br>\n";
  if (!is_file($filepath))
    return null;
  $tokens = @token_get_all(file_get_contents($filepath));
  $first = 1;
  $yamls = [];
  foreach ($tokens as $token) {
    if (is_array($token) and ($token[0]==T_COMMENT) and (strncmp($token[1],'/*PhpDoc:',strlen('/*PhpDoc:'))==0)) {
      $comment = $token[1];
//      echo "commentaire:<pre>$comment</pre>\n";
      if ($first) { // le premier commentaire a un format différent
        $name = '';
        $modif = '';
        $start = strlen('/*PhpDoc:');
        $first = 0;
      } else {
        if (!preg_match('!^/\\*PhpDoc: *([a-z]+)([^\r\n]*)[\r\n]+!', $comment, $matches, 0, strlen('')))
          throw new Exception("nom du champ non trouvé dans extractYamlFromPhp() ligne ".__LINE__);
        $name = $matches[1];
        $modif = trim($matches[2]); // la chaine trouvée après le nom du champ
        $start = strlen($matches[0]);
      }
      $yamlText = substr($comment, $start, strlen($comment)-$start-2);
//      echo "<b>texte Yaml extrait</b><pre>\n$yamlText\n</pre>\n";
      $yaml = spycLoad($yamlText);
      if (isset($yaml['title']) and $modif)
        $yaml['title'] .= ' - '.$modif;
//      echo "yaml="; print_r($yaml); echo "<br>\n";
      if (!$yaml) {
        echo "Erreur de lecture du text yaml:</b><pre>\n$yamlText\n</pre><pre>\n";
        throw new Exception("Erreur de lecture du text yaml dans extractYamlFromPhp()");
      }
      $yamls[] = [$name, $yaml];
    }
  }
  if (!$yamls)
    return '';
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
      throw new Exception("Erreur name '$name' non trouvé pour l'affectation dans extractYamlFromPhp()");
    }
  }
//    echo "<pre>yamls après agrégation="; print_r($yamls);  echo "</pre>\n";
  return $yamls[0][1];
}
