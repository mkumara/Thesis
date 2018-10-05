<?php 

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

$data = array();
//$district='colombo';

$districts =array('Ampara','Anuradhapura','Badulla','Batticaloa','Colombo','Galle','Gampaha','Hambantota','Jaffna','Kalutara','Kandy','Kegalle','Kilinochchi','Kurunegala','Mannar','Matale','Matara','Moneragala','Mullaitivu','Nuwara Eliya','Polonnaruwa','Puttalam','Ratnapura','Trincomalee','Vavniya');


$conn = pg_connect("host=localhost dbname=sL user=postgres password=duallink"); 

foreach($districts as $district){
$result = pg_exec($conn, "SELECT cases ,tempr, rain, pop_den FROM $district"."_2016_all_case"); 

$data_temp = array();
$data_rain = array();
$data_pop = array();

//variables to hold max values to normalize;
$max_rain=0;
$max_temp=0;
$max_pop=0;

echo "Data loading....".PHP_EOL;

while ($row = pg_fetch_array($result)) 
{ 
     $data_temp[]=array($row["cases"],1=>$row['tempr']);      
     $data_rain[]=array($row["cases"],1=>$row['rain']);
     $data_pop[]=array($row["cases"],1=>$row['pop_den']); 

     if($max_temp < $row['tempr'])
          $max_temp = $row['tempr'];
     if($max_rain < $row['rain'])
          $max_rain = $row['rain'];
     if($max_pop < $row['pop_den'])
          $max_pop = $row['pop_den'];    
} 

writeCSV($data_rain,"$district rainfall.csv");
writeCSV($data_temp,"$district temparature.csv");
writeCSV($data_pop,"$district population.csv");
}
die();

normalize($data_temp,  $max_temp);
normalize($data_rain,  $max_rain);
normalize($data_pop,  $max_pop);



/*$file = fopen("data.txt","r");
do{

  $line = fgets($file);
  $ar_line = explode(",",$line);
  $data[] = array(floatval($ar_line[0]), 1=>floatval($ar_line[1])); 
   
}while(!feof($file));


$null = array_pop($data);
//var_export($data);

*/


$svm = new SVM();
$options = $svm->getOptions();
//var_dump($options);
$svm->setOptions(array(SVM::OPT_KERNEL_TYPE=>SVM::KERNEL_RBF,
                       SVM::OPT_TYPE=>SVM::EPSILON_SVR));

//for rainfall
echo "Testing for Rainfall \n";
$pd = getDataPartitioned($data_rain,2);
$accuracy = getAccuracy($svm, $pd, $alpha);
$a_1 = getModeAccuracy($accuracy);
echo "\n";

//for temp
echo "Testing for Temparature \n";
$pd = getDataPartitioned($data_temp,2);
$accuracy = getAccuracy($svm, $pd, $alpha);
echo "\n";
$a_2 = getModeAccuracy($accuracy);

//for population den
echo "Testing for Population Density \n";
$pd = getDataPartitioned($data_pop,2);
$accuracy = getAccuracy($svm, $pd, $alpha);
echo "\n";
$a_3 = getModeAccuracy($accuracy);


var_dump(array($a_1,$a_2,$a_3));
//var_dump($accuracy);

//$model = $svm->train($data);
//$cros = $svm->crossvalidate($data, 5);
//$res = $model->predict(array(1=>25));
//var_dump($res);
//var_dump($cros);

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

 
   $model = $svm->train($train);
  
  $accuracy[]= getAccuracyPerFold($model, $test, $alpha); 
  }

  return $accuracy; 
}


function getAccuracyPerFold($model, $test, $alpha)
{
   $result = array();
   $correct=0;
   
   for($i=0;$i<count($test);$i++)
     {
      $result[] = $model->predict(array(1=>$test[$i][1]));
      if(($result[$i] > ($test[$i][0] - $alpha)) && ($result[$i] < ($test[$i][0] + $alpha)))
        {
          $correct++;
        }  
     }
   
   $accuracy = $correct/count($test)*100;
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


function normalize(&$data, $max)
{
  for($i=0;$i<count($data);$i++)
    {
      $data[$i][1] = $data[$i][1]/$max;
    }
}

function print_accuracy($accuracy)
{
   $count=0;
   foreach($accuracy as $acc)
   { $count++;
     print("$count = > $acc\n");
    }
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





