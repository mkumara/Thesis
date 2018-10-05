<?php 

require_once 'vendor/autoload.php';
use Phpml\Regression\SVR;
use Phpml\Classification\KNearestNeighbors;
use Phpml\SupportVectorMachine\Kernel;

//error_reporting(0);

if(!isset($argv[1]) && !isset($argv[2])){
echo 'Please provide alpha value and file name'.PHP_EOL;
   echo 'Usage: php rest.php 10 thani.csv'.PHP_EOL;
   exit();
 }

if(isset($argv[1]))

   $alpha = $argv[1];
else
   $alpha = 10;

$fileName = $argv[2];

echo "Running the model with confidence boundary $alpha on the dataset $fileName".PHP_EOL;

$data = array();
//$district='colombo';

$cost_ar = array(100, 1000, 10000);
$epsilon_ar = array(0.001, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8,0.9);

$file = fopen($fileName,"r");

if(!$file)
{
  echo "File $fileName could not be opened..exiting..".PHP_EOL;
  die();
}

if(isset($argv[3]))
{
  $fold = $argv[3];
}
else
{
  $fold = 10;
}


do{

  $line = fgets($file);
  $ar_line = explode(",",$line);
  $data[] = array(floatval($ar_line[0]), 1=>floatval($ar_line[1])); 

     $data_temp[]=[floatval($ar_line[0]),floatval($ar_line[2])];      
     $data_rain[]=[floatval($ar_line[0]),floatval($ar_line[1])];
     $data_pop[]=[floatval($ar_line[0]),floatval($ar_line[3])]; 
     $data_full[] = [floatval($ar_line[0]),floatval($ar_line[1]),floatval($ar_line[2]),floatval($ar_line[3])];
     $previous_cases = floatval($ar_line[0]);
    
   
}while(!feof($file));



//variables to hold max values to normalize;
$max_rain=0;
$max_temp=0;
$max_pop=0;

$previous_cases = 0;

$null = array_pop($data_temp);
$null = array_pop($data_rain);
$null = array_pop($data_pop);
$null = array_pop($data_full);

$pd = getDataPartitioned($data_full,$fold);


/*
$regression1 = new SVR(Kernel::RBF, $degree = 3, $epsilon = 0.001, $cost = 10);
$accuracy = getAccuracy($regression1 , $pd, $alpha);
$mode_acc= getModeAccuracy($accuracy);
echo "0.001 acc = $mode_acc".PHP_EOL; */



$regression2 = new SVR(Kernel::RBF, $degree=3, $epsilon=0.001, $cost=1000);
//$regression2 = new KNearestNeighbors();
$accuracy = getAccuracy($regression2 , $pd, $alpha);
var_dump($accuracy);
writeCSVPlotData($accuracy, "plot/".$fileName); // this goes with plot generation
//uncomment related section in getAccuracy function. Comment it when not used. mode accuracy should not be called
//$mode_acc= getModeAccuracy($accuracy);
//echo "0.9 acc = $mode_acc".PHP_EOL;






function getAccuracy($svm, $partitioned_data, $alpha=10)
{
  $accuracy=array();
  $data_plot = array();
  $points=0;

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

   $transformed_train = transform($train, $full = true);
//var_dump( $transformed_train);
   $transformed_test = transform($test, $full = true);
//var_dump( $transformed_test);
   $svm->train( $transformed_train[0], $transformed_train[1]);
   $accuracy[]= getAccuracyPerFold($svm, $transformed_test, $alpha); 

             //for paper testing only 
	    for($k=0;$k<count($transformed_test[0]);$k++)
	     {
              
              $data_plot[$points][0] = $transformed_test[0][$k][0];
              $data_plot[$points][1]=$transformed_test[1][$k];
	      $data_plot[$points][2] = floatval($svm->predict($transformed_test[0][$k]));
              $points++;
	      
	     }
  }
  //return $data_plot;
  return $accuracy; 
}


function getAccuracyPerFold($model, $test, $alpha)
{
   $result = array();
   $correct=0;
   $error = 0;
   
   for($i=0;$i<count($test[0]);$i++)
     {
      $result[] = floatval($model->predict($test[0][$i]));
      $error += abs($result[$i] - $test[1][$i]);
      
      if(abs($result[$i] - $test[1][$i]) < $alpha)
        {
          $correct++;
        }  
     }
   echo "average error = ".($error/count($test[0])).PHP_EOL;
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

function transform($data, $full = false)
{
  //data comes as  array(array(0,1), array(0,1), array(0,1));
  //need to return as array(array of index 0, array of index 1); 

  $train = array();

     if($full)
      {
	  foreach($data as $d)
	  {
	    $train[0][]= [floatval($d[1]), floatval($d[2]), floatval($d[3])]; // sample data
            //var_dump($train[0][]);
	    $train[1][]= floatval($d[0]); // target output
	  }

       return $train;
      }
  
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

function writeCSVPlotData($data, $filename)
{
   $file = fopen("$filename","w");

   if($file==NULL)
    {
     echo "Error opening $filename\n";
     return;
    }
   
   for($i=0;$i<count($data);$i++)
    {
      fprintf($file,"%f,%f,%f\n",$data[$i][0], $data[$i][1], $data[$i][2]);
    }
  fclose($file);
}

