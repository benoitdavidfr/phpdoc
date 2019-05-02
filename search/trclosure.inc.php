<?php
{/*PhpDoc:
name:  trclosure.inc.php
title: trclosure.inc.php - définition de filepathsOfTC() et getLineOfFile()
functions:
doc: |
journal: |
  1/5/2019:
    première version
*/}

/*PhpDoc: functions
name:  transitiveClosure
title: "function transitiveClosure(string $linkType, array $files, string $category='includes'): array"
doc: |
  prend un ensemble de File et renvoie un ensemble de File
  ajoute par appel récursif les liens, s'arrête quand plus aucun fichier n'est ajouté
  $linkType vaut 'links' ou 'reverseLinks'
*/
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

/*PhpDoc: functions
name:  filepathsOfTC
title: "function filepathsOfTC(File $file): array - chemins des fichiers incluant et inclus"
doc: |
  retourne le chemin du fichier passé en paramètre
  + de tous les fichiers incluant récursivement ce fichier
  + de tous les fichiers inclus récursivement dans ces fichiers
*/
function filepathsOfTC(File $file): array {
  $filepaths = [];
  foreach (transitiveClosure('links', transitiveClosure('reverseLinks', [$file])) as $f) {
    $filepaths[] = $f->path();
  }
  return $filepaths;
}

/*PhpDoc: functions
name:  filepathsOfTC
title: "function getLineOfFile(int $line, string $fileContents): string - extrait une ligne du code source"
*/
function getLineOfFile(int $line, string $fileContents): string {
  $tab = explode("\n", $fileContents);
  //print_r($tab);
  return $tab[$line-1];
}
