<?php
  if(isset($_GET['q'])) {
    $query = trim($_GET['q']);
    $query_simplified = str_replace(" ", "+",$query);
    $suggest_url = 'http://localhost:8983/solr/myexample/suggest?indent=on&q=' . $query_simplified . '&wt=json';
    error_log($suggest_url);
    $json = file_get_contents($suggest_url);
    error_log($json);
    $jfo = json_decode($json);
    $query = $jfo->suggest->suggest->$_GET['q']->suggestions;
    foreach ($query as $term) {
      error_log("sugge " . substr($term->term, 0, 15));
      echo substr($term->term, 0, 15) . ",";
    }
    //var_dump($query);
  }
 ?>
