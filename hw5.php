<?php
include 'SpellCorrector.php';
include_once('simple_html_dom.php');

// make sure browsers see this page as utf-8 encoded HTML
//AddDefaultCharset UTF-8
header('Content-Type: text/html; charset=utf-8');

$limit = 10;
$query = false;
if(isset($_REQUEST['q'])) {
  $query = $_REQUEST['q'];
}
$results = false;

$additionalParameters = array(
  'sort' => 'pageRankFile desc',
); ?>

<html>
  <head>
    <script>
    function showHint(str) {
    if (str.length == 0) {
        document.getElementById("txtHint").innerHTML = "";
        return;
    } else {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var options = "";
                var arr = this.responseText.split(',');
                console.log("resp " + this.responseText + "%");
                //arr = arr.split(',');
                options += '<select size = " '+ (arr.length - 1) + '">';
                for(var i = 0; i < arr.length - 1; i++) {
                  options += '<option>' + arr[i] + '</option>';
                }
                options += "</select>";
                if(arr.length == 1) {
                  options = "";
                }
                document.getElementById("suggestions").innerHTML = options;
            }
        }
        console.log("hi " + str);
        xmlhttp.open("GET", "auto.php?q=" + str.toLowerCase(), true);
        xmlhttp.send();
    }
}
  function setEmpty() {
    document.getElementById("suggestions").innerHTML = "";
  }
</script>
<style>
#suggestions{
    position: absolute;
    left: 60px;
    width: 150px;
}
option {
  width:125px;
}
</style>
    <title>PHP Solr Client Example</title>
  </head>
  <body>
    <form accept-charset="utf-8" action = "hw5.php" method="get">
      <label for="q">Search:</label>
      <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>" list="suggestions" onkeyup="showHint(this.value)" onblur="setEmpty()"/> <br>
      <div id="suggestions"></div>
      <input type="radio" name="gcore" value="lucene" <?php if(!isset($_GET['gcore']) || (isset($_GET['gcore']) && $_GET['gcore'] == 'lucene')) echo ' checked="checked"'?> > With Lucene
      <input type="radio" name="gcore" value="pageRank" <?php if(isset($_GET['gcore']) && $_GET['gcore'] == 'pageRank')  echo ' checked="checked"';?>> With pageRank <br>
      <input type="submit"/>
    </form>


<?php
if ($query)
{
  $query_correct = SpellCorrector::correct($query);
  if(strcmp($query,$query_correct) != 0) {
    echo "<h3> Showing results for ". $query_correct . "</h3>";
    $query = $query_correct;
  }
  // The Apache Solr Client library should be on the include path
  // which is usually most easily accomplished by placing in the
  // same directory as this script ( . or current directory is a default
  // php include path entry in the php.ini)
  require_once('solr-php-client/Apache/Solr/Service.php');

  // create a new solr service instance - host, port, and webapp
  // path (all defaults in this example)
  $solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample');
  if(isset($_GET['gcore']) && ($_GET['gcore'] == 'pageRank')) {
    $solr = new Apache_Solr_Service('localhost', 8983, '/solr/iniyai-core');
  }

  else {
        $solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample');
  }

  // if magic quotes is enabled then stripslashes will be needed
  if (get_magic_quotes_gpc() == 1)
  {
    $query = stripslashes($query);
  }

  // in production code you'll always want to use a try /catch for any
  // possible exceptions emitted  by searching (i.e. connection
  // problems or a query parsing error)
  try
  {
    $results = $solr->search($query, 0, $limit);
    if(isset($_GET['gcore']) && ($_GET['gcore'] == 'pageRank')) {
      $results = $solr->search($query, 0, $limit, $additionalParameters);
    }

  }
  catch (Exception $e)
  {
    // in production you'd probably log or email this error to an admin
    // and then show a special message to the user but for this example
    // we're going to show the full exception
    die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
  }
}

// display results
if ($results)
{
  $total = (int) $results->response->numFound;
  $start = min(1, $total);
  $end = min($limit, $total);
?>
    <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
    <ol>
<?php
  // iterate result documents
  foreach ($results->response->docs as $doc)
  {
?>
      <li>
        <table style="border: 1px solid black; text-align: left">
          <?php
              // iterate document fields / values
              /*foreach ($doc as $field => $value)
              {
                if(($field == 'title') || ($field == 'description') || ($field == 'id') ||($field == 'og_url')) {
          ?>
                    <tr>
                      <!-- <th><?php echo htmlspecialchars($field, ENT_NOQUOTES, 'utf-8'); ?></th> -->
                      <td><?php  if (($field == 'og_url') ){?>
                        <a href= <?=$value ?> target = "_blank"> <?=$value?></a>
                        <?php } else if(is_array($value)){  echo implode(" ", $value);  }
                          else if (($field == 'id') ){
                            //echo $value;
                            $content = strip_tags(str_get_html(file_get_contents($value)));
                            $matchpos = strpos($content,"donald");
                            if($matchpos != -1){
                              echo substr($content,$matchpos,100);
                            }
                            else {
                              echo "No matches found in the document";
                            }
                            //echo $content;
                            //foreach($content->find('a') as $element)
                            //  echo $element->href . '<br>';
                          }
                          else{ echo $value;} ?>
                     </td>
                    </tr>
          <?php
                }
              }*/
            $doc_id = $doc->id;
            echo ' <tr><td><a href= "'. $doc->og_url .'" target = "_blank"> ' . $doc->og_url . "</a></td></tr>";
            echo "  <tr><td><b>Description </b>" . $doc->description . "</td></tr>";
            $a = str_get_html(file_get_contents($doc_id));
            $a = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $a);
            preg_match("/<body[^>]*>(.*?)<\/body>/is", $a, $matches);
            $content = strtolower(strip_tags($matches[1]));
            $matchpos = strpos($content,$query);
            echo "<tr><td>";
            if($matchpos != -1){
              echo "<b>Snippet from the doc:</b> " . substr($content,$matchpos,156);
            }
            else {
              echo "No matches found in the document";
            }
            //echo "hi" . $matchpos;
            echo "</td></tr>";
            //echo "  <tr><td>" . $doc->id . "</td></tr>";
            //echo "  <tr><td>" . $doc->title . "</td></tr>";
          ?>
        </table>
      </li>
<?php
  }
?>
    </ol>
<?php
}
?>
  </body>
</html>
