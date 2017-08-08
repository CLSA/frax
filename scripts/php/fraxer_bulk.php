<?php
require_once('util.class.php');
util::initialize();

if($argc != 3) {
  util::out('Usage: fraxer_bulk <input.csv> <output.csv>');
  die();
}

$verbose = false;
$infileName = $argv[1];
$file = fopen($infileName,'r');
if(false === $file)
{
  util::out('file ' . $infileName . ' cannot be opened');
  die();
}

$outfileName = $argv[2];
$participant_list = array();
$line = NULL;
$line_count = 0;
$first = true;
$header = NULL;
util::out('reading csv data' );
while(false !== ($line = fgets($file)))
{
  $line_count++;
  $line = trim($line, "\n\"\t");
  $line = explode('","',$line);
  if($first)
  {
    $header = $line;
    $first = false;
    continue;
  }

  if(count($header) != count($line))
  {
    util::out('Error: line (' . $line_count . ') wrong number of elements ' . util::flatten($line));
    continue;
  }
  $line = array_combine($header,$line);
  $uid = $line['UID'];
  if(preg_match('/^[A-Z]\d{6}$/',$uid))
  {
    $participant_list[$uid] = $line;
  }
  else
  {
    util::out('Error: line (' . $line_count . ') unrecognized UID ' . $uid );
    continue;
  }
}
fclose($file);
$target = count($participant_list);
util::out('read ' . $target . ' UIDs' );

$frax_keys = array(
'Age',
'Sex',
'BMI',
'PreviousFx',
'ParentFx',
'Smoker',
'Gluco',
'ArthritisR',
'Osteo',
'Alcohol',
'TScore');
$header = $frax_keys;
array_unshift($header,'UID','TYPE','COUNTRY_CODE');
$header[] = 'P_OSTEOFX_NOBMD';
$header[] = 'P_HIPFX_NOBMD';
$header[] = 'P_OSTEOFX_BMD';
$header[] = 'P_HIPFX_BMD';
$header[] = 'OSTEOFX_CAT';

$frax_keys = array_combine($frax_keys, array_fill(0, count($frax_keys), '_'));
$frax_keys['Osteo'] = 0;

// we will only run this on sets with non-missing TScores and unknown secondary osteoporosis condition,
// setting the Osteo key value to zero and not '_'
// therefore, of the four output probabilities:
//10 year probability (x 100) of osteoporotic fracture, calculated without knowing BMD (positive real number with decimals).
//10 year probability (x 100) of hip fracture, calculated without knowing BMD (positive real number with decimals).
//10 year probability (x 100) of osteoporotic fracture, calculated knowing BMD (real number with decimals).
//10 year probability (x 100) of hip fracture, calculated knowing BMD (real number with decimals).
// we will always ignore the first two probabilities

$current = 0;
foreach($participant_list as $uid => $data)
{
  $file = fopen('input.txt', 'w');
  if(false === $file)
  {
    util::out('input.txt file cannot be opened');
    die();
  }
  $str = array('t', '19');
  foreach($frax_keys as $key => $def)
  {
    if(array_key_exists($key, $data))
      $str[] = $data[$key];
    else
      $str[] = $def;
  }
  $str = util::flatten($str, ',');
  if($verbose)
    util::out('UID: ' . $uid . ' input { ' . $str . ' }');
  fwrite($file, $str . PHP_EOL);
  fclose($file);

  exec('wine blackbox.exe');

  if($verbose)
    util::out(exec('cat output.txt'));
  $file = fopen('output.txt','r');
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
  array_unshift($outdata, $uid);
  // add in the 3 level risk category
  $osteofx_cat = 'NA';
  $outdata[] = $osteofx_cat;
  $outdata = array_combine($header, $outdata);

  $osteofx = $outdata['P_OSTEOFX_BMD'];
  $tscore = $outdata['TScore'];
  if('_' != $osteofx)
  {
    if(10.0 > $osteofx)
      $osteofx_cat = 'LOW';
    else if(20.0 < $osteofx)
      $osteofx_cat = 'HIGH';
    else
      $osteofx_cat = 'MODERATE';
    if(-2.5 >= $tscore && 'LOW' == $osteofx_cat)
      $osteofx_cat = 'MODERATE';
    $outdata['OSTEOFX_CAT'] = $osteofx_cat;
  }

  $participant_list[$uid] = $outdata;
  if(!$verbose)
    util::show_status(++$current, $target);
}

$file = fopen($outfileName, 'w');
if(false === $file)
{
  util::out('file ' . $outfileName . ' cannot be created');
  die();
}

$str = '"' . util::flatten($header, '","') . '"' . PHP_EOL;
fwrite($file, $str);
foreach($participant_list as $uid => $data)
{
  $str = '"' . util::flatten(array_values($data), '","') . '"' . PHP_EOL;
  fwrite($file, $str);
}

fclose($file);

?>
