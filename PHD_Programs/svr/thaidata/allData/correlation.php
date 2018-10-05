<?php 

require_once 'vendor/autoload.php';
use Phpml\Math\Statistic\Correlation;
error_reporting(0);

if( !isset($argv[1]) && !isset($argv[2]))
{
  die("Usage: php correlation.php numShift year");
}
$numShift = $argv[1]; // number of shifts
$year = $argv[2]; //year

$result = [];

$districts = [
'Amnat Charoen',
'Ang Thong',
'Bangkok Metropolis',
'Bueng Kan',
'Buri Ram',
'Chachoengsao',
'Chai Nat',
'Chaiyaphum',
'Chanthaburi',
'Chiang Mai',
'Chiang Rai',
'Chon Buri',
'Chumphon',
'Kalasin',
'Kamphaeng Phet',
'Kanchanaburi',
'Khon Kaen',
'Krabi',
'Lampang',
'Lamphun',
'Loei',
'Lop Buri',
'Mae Hong Son',
'Maha Sarakham',
'Mukdahan',
'Nakhon Nayok',
'Nakhon Pathom',
'Nakhon Phanom',
'Nakhon Ratchasima',
'Nakhon Sawan',
'Nakhon Si Thammarat',
'Nan',
'Narathiwat',
'Nong Bua Lam Phu',
'Nong Khai',
'Nonthaburi',
'Pathum Thani',
'Pattani',
'Phangnga',
'Phatthalung',
'Phayao',
'Phetchabun',
'Phetchaburi',
'Phichit',
'Phitsanulok',
'Phra Nakhon Si Ayutthaya',
'Phrae',
'Phuket',
'Prachin Buri',
'Prachuap Khiri Khan',
'Ranong',
'Ratchaburi',
'Rayong',
'Roi Et',
'Sa Kaeo',
'Sakon Nakhon',
'Samut Prakan',
'Samut Sakhon',
'Samut Songkhram',
'Saraburi',
'Satun',
'Si Sa Ket',
'Sing Buri',
'Songkhla',
'Sukhothai',
'Suphan Buri',
'Surat Thani',
'Surin',
'Tak',
'Trang',
'Trat',
'Ubon Ratchathani',
'Udon Thani',
'Uthai Thani',
'Uttaradit',
'Yala',
'Yasothon'
];

$numDistricts = count($districts);
$count=0;


foreach ($districts as $district) {
  $count++;
  echo "Runnign correlation for district $district  - $count of $numDistricts ".PHP_EOL;




$file = fopen($year.'_'.$district.'.csv',"r");

if(!$file)
{
  echo "File could not be opened..exiting..";
  die();
}

$cases = [];
$rain = [];
$temp = [];
$ndvi = [];

do{

  $line = fgets($file);
  $ar_line = explode(",",$line);
 
     $cases[] = $ar_line[0];
     $rain[] = $ar_line[1];
     //$temp[] = $ar_line[2];
     //$ndvi[] = $ar_line[3];
    
   
}while(!feof($file));

$null = array_pop($data_temp);
$null = array_pop($data_rain);
$null = array_pop($data_pop);
$null = array_pop($data_full);

$temp_results = [];

echo "District = $district".PHP_EOL;
$temp_results[] = $district;

echo PHP_EOL."*************No shifting ******************".PHP_EOL;
 //correlation for rain
  $cor = Correlation::pearson($cases, $rain);
  echo "Correlation rain and cases".$cor.PHP_EOL;
  $temp_results[] = $cor;

/*
   //correlation for temp
  $cor = Correlation::pearson($cases, $temp);
  echo "Correlation temp and cases".$cor.PHP_EOL;
 $temp_results[] = $cor;
     //correlation for ndvi
  $cor = Correlation::pearson($cases, $ndvi);
  echo "Correlation ndvi and cases".$cor.PHP_EOL;
 $temp_results[] = $cor;
 */

  $shiftedCases = shiftData($cases, $numShift);

  echo PHP_EOL."*************With shifting ******************".PHP_EOL;
 //correlation for rain
  $cor = Correlation::pearson($shiftedCases, $rain);
  echo "Correlation rain and cases".$cor.PHP_EOL;
   $temp_results[] = $cor;
   /*

   //correlation for temp
  $cor = Correlation::pearson($shiftedCases, $temp);
  echo "Correlation temp and cases".$cor.PHP_EOL;
   $temp_results[] = $cor;

     //correlation for ndvi
  $cor = Correlation::pearson($shiftedCases, $ndvi);
  echo "Correlation ndvi and cases".$cor.PHP_EOL;
   $temp_results[] = $cor;
   */

 $result[] = $temp_results;
}

writeCSVData("results\\".$year."_CorrelationResults.csv", $result);

function writeCSV($data, $filename)
{
   $file = fopen("$filename","w");

   if($file==NULL)
    {
     echo "Error opening $filename\n";
     return;
    }
   
   for($i=0;$i<count($data);$i++)
    {
      fprintf($file,"%d,%f\n",$data[$i][0], $data[$i][1]);
    }
  fclose($file);
}


function getCSVData($file)
{
  $dataArray = [];
  if (($handle = fopen($file, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 5000, ",")) !== FALSE) {
         $dataArray[] = $data;
    }
    fclose($handle);
  }

  return $dataArray;
}

function writeCSVData($file, $data)
{
  $fp = fopen($file, 'w');

  foreach ($data as $fields) {
    fputcsv($fp, $fields);
    }

 fclose($fp);

}

function shiftData($data, $numPushes)
{
    $dataReversed = array_reverse($data);
  
    for($i=0; $j< $numPushes; $j++)
    {
     array_push($dataReversed, array_shift($dataReversed));
    }

   $dataReversed = array_reverse($dataReversed);

  return $dataReversed;
}