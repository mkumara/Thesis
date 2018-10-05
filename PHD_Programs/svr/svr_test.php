<?php 

require_once 'vendor/autoload.php';
use Phpml\Regression\SVR;
use Phpml\SupportVectorMachine\Kernel;

if($argc < 2)
 { echo 'Please provide alpha value'.PHP_EOL;
   echo 'Usage: php rest.php 10'.PHP_EOL;
   exit();
 }

if(isset($argv[1]))

   $alpha = $argv[1];
else
   $alpha = 10;

echo "Running the model with confidence boundary $alpha".PHP_EOL;

$cost_ar = array(100, 1000, 10000);
$epsilon_ar = array(0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8,0.9);

$data = array();
//$district='colombo';

$districts =array(/*'Ampara_2016_all_case','ampara_2016_all_case_lag',
                  'Anuradhapura_2016_all_case','anuradhapura_2016_all_case_lag',
                  'Badulla_2016_all_case','badulla_2016_all_case_lag', 
                  'Batticaloa_2016_all_case','batticaloa_2016_all_case_lag',
		  'Colombo_2016_all_case','colombo_2016_all_case_lag',
		  'Galle_2016_all_case','galle_2016_all_case_lag',
		  'Gampaha_2016_all_case',*/'gampaha_2016_all_case_lag',/*
		  'Hambantota_2016_all_case','hambantota_2016_all_case_lag',
		  'Jaffna_2016_all_case','jaffna_2016_all_case_lag',
		  'Kalutara_2016_all_case','kalutara_2016_all_case_lag',
		  'Kandy_2016_all_case','kandy_2016_all_case_lag',
		  'Kegalle_2016_all_case','kegalle_2016_all_case_lag',
		  'Kilinochchi_2016_all_case','kilinochchi_2016_all_case_lag',
		  'Kurunegala_2016_all_case','kurunegala_2016_all_case_lag',
		  'Mannar_2016_all_case','mannar_2016_all_case_lag',
		  'Matale_2016_all_case','matale_2016_all_case_lag',
		  'Matara_2016_all_case','Matara_2016_all_case_lag',
		  'Moneragala_2016_all_case','Moneragala_2016_all_case_lag',
		  'Mullaitivu_2016_all_case','Mullaitivu_2016_all_case_lag',
		  'Polonnaruwa_2016_all_case','Polonnaruwa_2016_all_case_lag',
		  'Puttalam_2016_all_case','Puttalam_2016_all_case_lag',
		  'Ratnapura_2016_all_case','Ratnapura_2016_all_case_lag',
		  'Trincomalee_2016_all_case','Trincomalee_2016_all_case_lag',
		  'Vavniya_2016_all_case','Vavniya_2016_all_case_lag'
                   */
                   );


$conn = pg_connect("host=localhost port=5432 dbname=sl user=postgres password=duallink"); 
if(!$conn)
   {
     echo "Connection to the database failed. ".PHP_EOL;
     die();
   }
foreach($districts as $district){
$result = pg_exec($conn, "SELECT cases ,tempr, rain, pop_den FROM $district"); 

$data_temp = array();
$data_rain = array();
$data_pop = array();
$data_temp_target = array();
$data_rain_target = array();
$data_pop_target = array();

//variables to hold max values to normalize;
$max_rain=0;
$max_temp=0;
$max_pop=0;


while ($row = pg_fetch_array($result)) 
{ 
     $data_temp[]=[$row['cases'],$row['tempr']];      
     $data_rain[]=[$row['cases'],$row['rain']];
     $data_pop[]=[$row['cases'],$row['pop_den']]; 
    
     if($max_temp < $row['tempr'])
          $max_temp = $row['tempr'];
     if($max_rain < $row['rain'])
          $max_rain = $row['rain'];
     if($max_pop < $row['pop_den'])
          $max_pop = $row['pop_den'];    
} 

//writeCSV($data_rain,"$district rainfall.csv");
//writeCSV($data_temp,"$district temparature.csv");
//writeCSV($data_pop,"$district population.csv");

$null = array_pop($data_temp);
$null = array_pop($data_temp_target);
$null = array_pop($data_rain);
$null = array_pop($data_rain_target);
$null = array_pop($data_pop);
$null = array_pop($data_pop_target);

$result = [[]];
$pd = getDataPartitioned($data_rain,10);
for($i=0; $i< count($cost_ar); $i++){
 for($j=0; $j< count($epsilon_ar); $j++){
	//rain

        $regression = NULL;
	$regression = new SVR(Kernel::RBF, $degree = 3, $epsilon = $epsilon_ar[$j], $cost = $cost_ar[$i]);
echo "cost = {$cost_ar[$i]} and epsilon = {$epsilon_ar[$j]}".PHP_EOL;	
        $accuracy = getAccuracy($regression , $pd, $alpha);
//print_r($regression);
       // print_r($accuracy);
//var_dump($accuracy);
	$mode_acc= getModeAccuracy($accuracy);
        $accuracy = array();
	
         $result[$i][$j] = $mode_acc;
         echo $mode_acc.PHP_EOL;
         $mode_acc=0;
	/*$regression = new SVR(Kernel::RBF);
	$pd = getDataPartitioned($data_temp,10);
	$accuracy = getAccuracy($regression , $pd, $alpha);
	$mode_acc= getModeAccuracy($accuracy);
	echo "Temp Accuracy for district $district".PHP_EOL;
	echo $mode_acc.PHP_EOL;*/
   }//epsilon
  }//cost

var_dump($result);

//$val = $regression->predict([200]);
}


function getAccuracy($svm, $partitioned_data, $alpha=10)
{
  $accuracy=array();
  for($i=0;$i<count($partitioned_data);$i++)
  {
   $test = $partitioned_data[$i];
   $train = array();

    for($j=0;$j<count($partitioned_data);$j++)
    {
     if($j==$i)
       continue;
     $train = array_merge($train, $partitioned_data[$j]);
    }

   $transformed_train = transform($train);
   $transformed_test = transform($test);
   $svm->train( $transformed_train[0], $transformed_train[1]);
   //$temp = $svm->predict([7.23]);
   // echo $temp.PHP_EOL;
   $accuracy[]= getAccuracyPerFold($svm, $transformed_test, $alpha);   
  }

  return $accuracy; 
}


function getAccuracyPerFold($model, $test, $alpha)
{
   $result = array();
   $correct=0;
   //var_dump($model);
   for($i=0;$i<count($test[0]);$i++)
     {
      $result[$i] = floatval($model->predict([$test[0][$i]]));
     
      if(($result[$i] > ($test[1][$i] - $alpha)) && ($result[$i] < ($test[1][$i] + $alpha)))
        {
          $correct++;
        }  
     }
   
   $accuracy = $correct/count($test[0])*100;
   return $accuracy;
}


function getDataPartitioned($data, $crossValidation)
{
  
   $partitioned_array=array();
   $data_count = count($data);
  
   $cross_size =  floor($data_count/$crossValidation);
  
   for($i=0;$i<$crossValidation;$i++)
   {
      $indices = array_rand($data, $cross_size);
      $partitioned_array[$i] = getSubArray($data,$indices);
      
   } 

  return $partitioned_array;
}

function getSubArray(&$data, $indices)
{
  $array = array();

  for($i=0;$i<count($indices);$i++){
  
   $array[]= $data[$indices[$i]];
   unset($data[$indices[$i]]);
  }
 $data = array_values($data);
 return $array;
}

function transform($data)
{
  //data comes as  array(array(0,1), array(0,1), array(0,1));
  //need to return as array(array of index 0, array of index 1); 

  $train = array();
  
  foreach($data as $d)
  {
    $train[0][]= [floatval($d[1])]; // sample data
    $train[1][]= floatval($d[0]); // target output
  }

 return $train;
}


function getModeAccuracy($accuracy)
{
  $cubby = array(10=>array(0,0),
                 20=>array(0,0),
                 30=>array(0,0),
                 40=>array(0,0),
                 50=>array(0,0),
		 60=>array(0,0),
                 70=>array(0,0),
                 80=>array(0,0),
                 90=>array(0,0),
                 100=>array(0,0)
                 );
  foreach($accuracy as $acc)
  {
    $max=0;
    $max_index=0;
  
    if($acc > 90)
        {
          $cubby[100][0] +=  $acc;
          $cubby[100][1]++;
          
          if($max < $cubby[100][1])
             {$max = $cubby[100][1]; $max_index = 100;}
        }
else if($acc > 80)
        {
          $cubby[90][0] +=  $acc;
          $cubby[90][1]++;
        if($max < $cubby[90][1])
             {$max = $cubby[90][1]; $max_index = 90;}
        }
else if($acc > 70)
        {
          $cubby[80][0] +=  $acc;
          $cubby[80][1]++;
          if($max < $cubby[80][1])
             {$max = $cubby[80][1]; $max_index = 80;}
        }
else if($acc > 60)
        {
          $cubby[70][0] +=  $acc;
          $cubby[70][1]++;
           if($max < $cubby[70][1])
             {$max = $cubby[70][1]; $max_index = 70;}
        }
else if($acc > 50)
        {
          $cubby[60][0] +=  $acc;
          $cubby[60][1]++;
         if($max < $cubby[60][1])
             {$max = $cubby[60][1]; $max_index = 60;}
        }
else if($acc > 40)
        {
          $cubby[50][0] +=  $acc;
          $cubby[50][1]++;
          if($max < $cubby[50][1])
             {$max = $cubby[50][1]; $max_index = 50;}
        }
else if($acc > 30)
        {
          $cubby[40][0] +=  $acc;
          $cubby[40][1]++;
	  if($max < $cubby[40][1])
             {$max = $cubby[40][1]; $max_index = 40;}
        }
else if($acc > 20)
        {
          $cubby[30][0] +=  $acc;
          $cubby[30][1]++;
	  if($max < $cubby[30][1])
             {$max = $cubby[30][1]; $max_index = 30;}
        }
else if($acc > 10)
        {
          $cubby[20][0] +=  $acc;
          $cubby[20][1]++;
	  if($max < $cubby[20][1])
             {$max = $cubby[20][1]; $max_index = 20;}
        }
else   {
          $cubby[10][0] +=  $acc;
          $cubby[10][1]++;
	  if($max < $cubby[10][1])
             {$max = $cubby[10][1]; $max_index = 10;}
        }
     
  }

  $a = $cubby[$max_index][0]/$cubby[$max_index][1];

  return $a;
}
