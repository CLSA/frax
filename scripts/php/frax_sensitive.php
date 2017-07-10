<?php
require_once('util.class.php');
util::initialize();

// test frax sensitivity to changes in t-score

$tscore = range(-3.0, 3.0, 0.1);
$data = array('t', 19, 57.03, 1, 28.68, 0, 0, 0, 0, 0, 0, 0);
$output = array();
foreach($tscore as $score)
{
  $str = $data;
  $str[] = $score;
  $file = fopen('input.txt', 'w');
  if(false === $file)
  {
    util::out('input.txt file cannot be opened');
    die();
  }
  $str = util::flatten($str, ',');
  fwrite($file, $str . PHP_EOL);
  fclose($file);
  exec('wine blackbox.exe');
  $file = fopen('output.txt', 'r');
  if(false === $file)
  {
    util::out('output.txt file cannot be opened');
    die();
  }
  $outdata = array();
  while(false !== ($line = fgets($file)))
  {
    $line = trim($line, "\,\n\"\t");
    $line = explode(',', $line);
    foreach($line as $value) $outdata[] = $value;
  }

  fclose($file);
  if(17 != count($outdata))
  {
    util::out('ERROR: missing output data for ' . $uid . ' : ' . util::flatten($outdata));
    die();
  }
  $output[] = util::flatten($outdata, ',');
}

$outfileName = 'test_sensitive.csv';
$file = fopen($outfileName, 'w');
if(false === $file)
{
  util::out('file ' . $outfileName . ' cannot be created');
  die();
}

foreach($output as $str)
{
  fwrite($file, $str . PHP_EOL);
}
fclose($file);

?>
