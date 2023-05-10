<?php

class Node 
{
  public $value, $index, $left, $right;
  public function __construct($value, $index)
  {
    $this->value = $value;
    $this->index = $index;
  }
}

class BinarySearchTree
{
  private $root;

  public function __construct()
  {
    $this->root = null;
  }

  public function insertArray($filtered_array, $start, $end)
  {
    if ($start > $end) {
      return null;
    }
    $mid = intdiv(($end + $start), 2);
    $new_node = new Node($filtered_array[$mid], $mid);
    $new_node->left = $this->insertArray($filtered_array, $start, $mid-1);
    $new_node->right = $this->insertArray($filtered_array, $mid+1, $end);
    return $new_node;
  }

  public function find($query) {
    $current = $this->root;
    $step=0;
    while ($current) {
      $step++;
      if ($query > $current->value) {
        $current = $current->right;
      } elseif ($query < $current->value) {
        $current = $current->left;
      } else {
        break;
      }
    }
    return (array("step"=>$step,"index"=>$current->index));

  }
  public function restore($node){
      $this->root = $node;
    }
}

function parse_file($filename, $index_var) {
  $source_array = json_decode(file_get_contents($filename), true);
  // based on the example data we see that array is sorted, so we recursively split it into halves to get balanced tree. 
  // actually, this suppose that we will search only on name field, since in other cases it will be required to rebalance tree on adding new nodes
  // we need to compact array for the case of absense of some fileds and formally respect the task 
  $filtered_array = array();
  foreach ($source_array as $key => $document) {
    if (array_key_exists($index_var,$document)) {
      // since we need to exact match in search result, we treat all field as strings
      if (gettype($document[$index_var]) != "string") {
        die("Based on the example data given, data in the nested arrays are redundant, please use rec* fields for search");
      }
      $filtered_array[$key] = $document[$index_var];
    }   
  }
  $root = new BinarySearchTree;
  $treetext = serialize($root->insertArray($filtered_array, 0, (sizeof($filtered_array)-1)));
  // saving index tree to file 
  $indexfile = getcwd().DIRECTORY_SEPARATOR.$filename.".index";
  $ft = fopen($indexfile,"w") or die("Error opening index file for writing");
  fwrite($ft, $treetext);
  fclose($ft);
  echo "Binary index was created and saved to " . $indexfile."\n";
}

function query_doc($filename, $index_var, $query) {
  // getting original array
  $source_array = json_decode(file_get_contents($filename), true);

  // restoring binary tree from file
  $treetext = file_get_contents($filename.".index");
  $root = new BinarySearchTree;
  $root->restore(unserialize($treetext));

  echo "Searching binary tree\n";
  $res = $root->find($query);

  if (is_null($res["index"])) {
    echo "No result found. ".strval($res["step"])." steps taken.\n";
  } else {
    echo "Document found. ".strval($res["step"])." steps taken.\n";
    print_r($source_array[$res["index"]]);
  }

  echo "Searching using loop\n";
  $step = 0;
  foreach ($source_array as $document) {
    $step++;
    if ($document[$index_var]==$query) {
      echo "Document found. ".strval($step)." steps taken.\n";
      print_r($source_array[$res["index"]]);
      break;
    }
  }
}

parse_str(implode('&', array_slice($argv, 1)), $_GET);

if (isset($_GET["file"])) {
  if (isset($_GET["index_var"])) {
    if (!(file_exists($_GET["file"].".index")) || isset($_GET["force"]) ) {  
      parse_file($_GET["file"], $_GET["index_var"]);
    } else {
      echo ("Index file already exists. Please add force option to rebuild\n");
    } 
    // not an optimal solution if search will be performed in one run with indexing, but seems to be ok for use case in task
    if (isset($_GET["query"])) {
      query_doc($_GET["file"], $_GET["index_var"], $_GET["query"]);
      
    }
  }  else {
      die ("Index variable is not specified. Please run php -f test.php file=filename index_var=index_variable");
  }

} else {
  echo ("For indexing of documents please run php -f test.php file=filename index_var=index_variable\n");
  echo ("For search, please add query parameter\n");
  echo ("For forcing index rebuid, please add force parameter\n");
}

?>
