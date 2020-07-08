# Documentation des sources Php
Ce module exploite la documentation intégrée dans les sources Php, conformément aux principes définis ci-dessous.

## Structuration en classes/objets/propriétés et liens
La documentation est organisée sous la forme d'un arbre d'objets, chacun appartenant à une classe,
chacune définissant des propriétés.
De plus, des liens entre objets peuvent être définis.

### Liste des classes d'objets
Les classes suivantes sont définies:

  - **Module** : ensemble de fichiers contenus dans un même répertoire  
    Identifié par le chemin absolu du répertoire par rapport à la racine des sites Apache.
  - **HtmlFile** : fichier HTML, JS, CSS ou Yaml  
    Identifié par son chemin relatif par rapport à son module
  - **PhpFile** : fichier Php destiné à être exécuté en Php (appelé alors script) ou inclus dans un autre fichier Php  
    Identifié par son chemin relatif par rapport à son module
  - **Screen** : exécution distincte d’un script,
    concept utile pour un script structuré comme un ensemble de possibilités indépendantes les unes des autres  
    Identifié par son script et par un nom de l’écran
  - **FunClassVar** : regroupe classe Php, propriété d'une classe, fonction Php et variable Php  
    Identifié par son fichier Php et par un nom
  - **Method** : méthode d'une classe  
    Identifié par sa classe et par un nom
  - **Parameter** : paramètre d’une fonction ou d’une méthode  
    Identifié par sa fonction ou sa méthode et par un nom
  - **Type** : type d’un paramètre ou de la valeur retournée par une fonction ou une méthode
  - **SqlFile** : fichier contenant des commandes SQL  
    Identifié par son chemin relatif par rapport à son module
  - **SqlDB** : base de données regroupant un ensemble de tables appartenant à la même base ;
    cependant les tables n’appartiennent pas à la base mais à l’objet qui les crée (SqlFile ou PhpFile)  
    Identifié par son module et un nom
  - **SqlTable** : table SQL ou collections de documents MongoDB  
    Appartient à l’objet qui la crée (SqlFile ou PhpFile) ; des liens inverses permettent de savoir quels scripts
    ou fichiers SQL la consulte ou la modifie  
    Identifié par l’objet qui la crée et un nom
  - **Column** : Colonne d'une table SQL ou propriété d'un document MongoDB  
    Identifié par sa table/collection et un nom
  - **Synchro** : infos de synchronisation entre serveurs

Pour chaque classe, un certain nombre de propriétés sont définies.

### Arbre des objets
Les objets documentés sont structurés dans un arbre selon les liens parent-enfants suivants:

  - Module
    - Module - sous-module d'un module
    - Synchro
    - SqlDB - une BD est rattachée à un module
    - HtmlFile
    - SqlFile
      - SqlTable - table/collection créée par le fichier Sql
          - SqlTable - sous-document d'un document MongoDB
          - Column - colonne appartenant à une table
    - PhpFile - fichier .php ou fichier .inc.php
      - Screen
      - _SqlTable - table créée par un script Php_
      - FunClassVar
          - FunClassVar - propriété publique ou privée d'une classe
          - Type - type de la valeur retournée par une fonction ou type d'une variable
          - Parameter - paramètre d'une fonction
              - Type - type du paramètre
          - Method - méthode d'une classe
              - Type - type de la valeur retournée par la méthode
              - Parameter - paramètre de la méthode

La racine de l'arbre est définie dans le fichier `root.yaml` de `phpdoc`.
### Liens entre objets
Des liens suivants peuvent être définis entre objets en fonction de leur classe :

  - Module
    - **requires** - un module nécessite un objet (autre module ou PhpFile) pour être défini
    - **forks** - un module est un fork d'un autre fichier ou d'un module
  - HtmlFile
    - **hrefs** - un fichier référence une URL (HtmlFile|PhpFile|Screen)
    - **includes** - un fichier en inclus un autre
  - PhpFile
    - **includes** - un fichier Php en inclus un autre
    - **forks** - un fichier est un fork d'un autre fichier ou d'un module
    - **uses** - un fichier utilise une fonction, une classe ou une variable définie dans un autre fichier
    - **hrefs** - le HTML généré par le script référence une URL (HtmlFile|PhpFile|Screen)
    - **selects** - un script Php effectue un select d'une table SQL
    - **updates** - un script Php effectue un update d'une table SQL
  - Screen
    - **hrefs** - le HTML généré par le script référence une URL (HtmlFile|PhpFile|Screen)
  - FunClassVar
    - **uses** - une fonction ou une classe utilise une fonction, une classe ou une variable définie dans un autre fichier
    - **selects** - une fonction ou une classe effectue un select d'une table SQL
    - **updates** - une fonction ou une classe effectue un update d'une table SQL
  - Method
    - **uses** - une méthode utilise une fonction, une classe ou une variable définie dans un autre fichier
    - **selects** - une méthode effectue un select d'une table SQL
    - **updates** - une méthode effectue un update d'une table SQL
  - SqlTable
    - **database** - base de données àa laquelle la table appartient

## Principes de la documentation
La documentation est rédigée en Yaml dans le code source:

  - pour un module, dans un fichier `phpdoc.yaml` dans le répertoire,
  - pour un fichier Html, dans un commentaire commencant par `<!--PhpDoc:`
  - pour un fichier Php, JS, CSS ou Sql, dans un commentaire commencant par `/*PhpDoc:`
  - pour un fichier Yaml, dans le contenu du fichier:
    - s'il comporte un champ `phpDoc` alors ce champ est retourné,
    - sinon s'il comporte un champ `title` ou `description` alors ce champ est retourné respectivement
      comme champ `title` ou `doc`
  - pour un objet défini dans un fichier Php ou Sql, dans un commentaire commencant par `/*PhpDoc:`
    suivi du mot-clé correspondant au type d'objet:
    - `screens` pour `Screen`
    - `functions` pour une function
    - `classs` pour une classe
    - `methods` pour une méthode
    - `tables` pour une table SQL
    - ...
  - pour une base SQL par un champ `sqlDBs` du `phpdoc.yaml` du module dans lequel la base est définie,
  
## Scripts phpdoc
Le module phpdoc exploite la documentation, il est composé principalement des scripts suivants:

  - `index.php` et `phpdoc.php` permettent de visualiser et de naviguer dans l'arbre de la documentation,
  - `stats.php` génère des statistiques
  - `verifincall.php` vérifie la documentation des d'inclusions entre fichiers Php et les comparant au code Php.
  - `structure.php` génère un document JSON décrivant la structure de la doc,
  - `export.php` exporte la doc en JSON.
