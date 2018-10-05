<?php

require_once 'vendor/autoload.php';
use Phpml\Classification\KNearestNeighbors;
use Phpml\Regression\LeastSquares;
use Phpml\Regression\SVR;
use Phpml\SupportVectorMachine\Kernel;

//error_reporting(0);

$districts = [
    'Amnat Charoen',
    'Bangkok Metropolis',
    'Bueng Kan',
    'Mae Hong Son',
    'Phetchabun',
    'Ranong',
    'Sukhothai',
    'Udon Thani',
    'Uttaradit',
    'Yala',
];

//php svr_fromCSV.php alpha filename fold dir filename_starting_pattern

if (!isset($argv[1]) && !isset($argv[2])) {
    echo 'Please provide alpha value and file name' . PHP_EOL;
    echo 'Usage: php svr_fromCSV.php alpha filename fold dir filepattern' . PHP_EOL;
    exit();
}

if (isset($argv[1])) {
    $alpha = $argv[1];
} else {
    $alpha = 10;
}

$fileName = $argv[2];

if (isset($argv[3])) {
    $fold = $argv[3];
} else {
    $fold = 10;
}

if (isset($argv[4])) {
    $dir = $argv[4];
} else {
    echo "Directory needed";
    die();
}

if (isset($argv[5])) {
    $pattern = $argv[5];
} else {
    $pattern = "";
}

echo "Running the model with confidence boundary $alpha on the dataset $fileName" . PHP_EOL;

$data = array();

$cost_ar    = array(100, 1000, 10000);
$epsilon_ar = array(0.001, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9);

/*
//read data files all at once

$cases = getCSVData('combined\\'.$year.'_cases_interpolated.csv');
$rain = getCSVData('combined\\'.$year.'_rain_interpolated.csv');
$temp = getCSVData('combined\\'.$year.'_temp_interpolated.csv');
$ndvi = getCSVData('combined\\'.$year.'_ndvi.csv');

$count = 0;
foreach ($districts as $district) {

$districtFile = [];

$districtFile[0] = $cases[$count];
$districtFile[1] = $rain[$count];
$districtFile[2] = $temp[$count];
$districtFile[3] = $ndvi[$count];

$transposedDistrictFile = transposeData($districtFile);
writeCSVData($year.'_'.$district.'.csv', $transposedDistrictFile);
$count++;
}

die();

 */
$numDistricts = count($districts);
$count        = 0;

$final_results          = [];
$final_results_ensemble = [];

$file_list = glob($dir . '/' . $pattern . '*.csv*');
echo $dir . '/' . $pattern . '*.csv';

foreach ($file_list as $file) {
    $count++;
    $file_name = end(explode('/', $file));
    echo "Runnign SVR for $file " . PHP_EOL;

    $file = fopen($file, "r");

    if (!$file) {
        echo "File $fileName could not be opened..exiting.." . PHP_EOL;
        die();
    }

    $data      = [];
    $data_temp = [];
    $data_rain = [];
    $data_full = [];

    do {

        $line    = fgets($file);
        $ar_line = explode(",", $line);
        if (count($ar_line) == 0) {
            continue;
        }

        $data[] = array(floatval($ar_line[0]), 1 => floatval($ar_line[1]));

        $data_temp[]    = [floatval($ar_line[0]), floatval($ar_line[2])];
        $data_rain[]    = [floatval($ar_line[0]), floatval($ar_line[1])];
        $data_full[]    = [floatval($ar_line[0]), floatval($ar_line[1]), floatval($ar_line[2])];
        $previous_cases = floatval($ar_line[0]);

    } while (!feof($file));

    // $shiftedData = shiftData($data_full, 1);

//shifting cases 3 weeks

//variables to hold max values to normalize;
    $max_rain = 0;
    $max_temp = 0;

    $previous_cases = 0;

    $null = array_pop($data_temp);
    $null = array_pop($data_rain);
    $null = array_pop($data_full);

    //if ($argv[5] == 1) {
    $pd = getDataPartitioned($data_full, $fold);
    // } else {
    //   $pd = getDataPartitioned($shiftedData, $fold);

    // }

/*
$regression1 = new SVR(Kernel::RBF, $degree = 3, $epsilon = 0.001, $cost = 10);
$accuracy = getAccuracy($regression1 , $pd, $alpha);
$mode_acc= getModeAccuracy($accuracy);
echo "0.001 acc = $mode_acc".PHP_EOL; */

    $svr      = new SVR(Kernel::RBF, $degree = 3, $epsilon = 0.1, $cost = 10);
    $accuracy = getAccuracy($svr, $pd, $alpha);
    writeCSVData($dir . '//results//svr_' . $file_name . '.csv', $accuracy);

    $svr_acc = getAverageAccuracy($accuracy);

    $knn      = new KNearestNeighbors();
    $accuracy = getAccuracy($knn, $pd, $alpha);
    writeCSVData($dir . '//results//knn_' . $file_name . '.csv', $accuracy);
    $knn_acc = getAverageAccuracy($accuracy);

    $ls       = new LeastSquares();
    $accuracy = getAccuracy($ls, $pd, $alpha);
    writeCSVData($dir . '//results//ls_' . $file_name . '.csv', $accuracy);
    $nb_acc = getAverageAccuracy($accuracy);

    $final_results[] = [$district, $svr_acc[0], $svr_acc[1], $knn_acc[0], $knn_acc[1], $nb_acc[0], $nb_acc[1]];

    $accuracyEnsemble = getAccuracyEnsemble($svr, $knn, $ls, $pd, $alpha);
    $ensemble_acc     = getAverageAccuracy($accuracyEnsemble);

    $final_results_ensemble[] = [$district, $ensemble_acc[0], $ensemble_acc[1]];

    writeCSVData($dir . '//results//ensemble_' . $file_name . '.csv', $accuracyEnsemble);

//writeCSVPlotData($accuracy, "plot/".$fileName); // this goes with plot generation
    //uncomment related section in getAccuracy function. Comment it when not used. mode accuracy should not be called
    //$mode_acc= getModeAccuracy($accuracy);
    //echo "0.9 acc = $mode_acc".PHP_EOL;

    echo "Data points = " . count($data_full) . PHP_EOL;

}

writeCSVData($dir . '//results//Final.csv', $final_results);
writeCSVData($dir . '//results//FinalEnsemble.csv', $final_results_ensemble);

function getAverageAccuracy($acc)
{
    $avg_acc   = [];
    $count     = 0;
    $sum_error = 0;
    $sum_acc   = 0;

    foreach ($acc as $value) {
        $sum_error += $value[1];
        $sum_acc += $value[0];
        $count++;
    }

    $avg_acc[0] = $sum_acc / $count;
    $avg_acc[1] = $sum_error / $count;

    return $avg_acc;
}

function getAccuracy($svm, $partitioned_data, $alpha = 10)
{
    $accuracy  = array();
    $data_plot = array();
    $points    = 0;

    for ($i = 0; $i < count($partitioned_data); $i++) {
        $test  = $partitioned_data[$i];
        $train = array();

        for ($j = 0; $j < count($partitioned_data); $j++) {
            if ($j == $i) {
                continue;
            }

            $train = array_merge($train, $partitioned_data[$j]);
        }

        $transformed_train = transform($train, $full = true);

//var_dump( $transformed_train);
        $transformed_test = transform($test, $full = true);
//var_dump( $transformed_test);
        $svm->train($transformed_train[0], $transformed_train[1]);
        $accuracy[] = getAccuracyPerFold($svm, $transformed_test, $alpha);

        //for paper testing only
        for ($k = 0; $k < count($transformed_test[0]); $k++) {

            $data_plot[$points][0] = $transformed_test[0][$k][0];
            $data_plot[$points][1] = $transformed_test[1][$k];
            $data_plot[$points][2] = floatval($svm->predict($transformed_test[0][$k]));
            $points++;

        }
    }
    //return $data_plot;
    return $accuracy;
}

function getAccuracyEnsemble($model1, $model2, $model3, $partitioned_data, $alpha = 10)
{
    $accuracy  = array();
    $data_plot = array();
    $points    = 0;

    for ($i = 0; $i < count($partitioned_data); $i++) {
        $test  = $partitioned_data[$i];
        $train = array();

        for ($j = 0; $j < count($partitioned_data); $j++) {
            if ($j == $i) {
                continue;
            }

            $train = array_merge($train, $partitioned_data[$j]);
        }

        $transformed_train = transform($train, $full = false);

//var_dump( $transformed_train);
        $transformed_test = transform($test, $full = false);
//var_dump( $transformed_test);
        $model1->train($transformed_train[0], $transformed_train[1]);
        $model2->train($transformed_train[0], $transformed_train[1]);
        $model3->train($transformed_train[0], $transformed_train[1]);
        $accuracy[] = getAccuracyPerFoldEnsemble($model1, $model2, $model3, $transformed_test, $alpha);

        //for paper testing only
        /*  for($k=0;$k<count($transformed_test[0]);$k++)
    {

    $data_plot[$points][0] = $transformed_test[0][$k][0];
    $data_plot[$points][1]=$transformed_test[1][$k];
    $data_plot[$points][2] = floatval($svm->predict($transformed_test[0][$k]));
    $points++;

    }
     */
    }
    //return $data_plot;
    return $accuracy;
}

function getAccuracyPerFold($model, $test, $alpha)
{
    $result  = array();
    $correct = 0;
    $error   = 0;

    for ($i = 0; $i < count($test[0]); $i++) {
        $result[] = floatval($model->predict($test[0][$i]));
        $error += abs($result[$i] - $test[1][$i]);

        if (abs($result[$i] - $test[1][$i]) < $alpha) {
            $correct++;
        }
    }
    // echo "average error = ".($error/count($test[0])).PHP_EOL;
    $accuracy[0] = $correct / count($test[0]) * 100;
    $accuracy[1] = ($error / count($test[0]));
    return $accuracy;
}

function getAccuracyPerFoldEnsemble($model1, $model2, $model3, $test, $alpha)
{
    $result  = array();
    $correct = 0;
    $error   = 0;

    for ($i = 0; $i < count($test[0]); $i++) {
        //$result[] = (floatval($model1->predict($test[0][$i])) + floatval($model2->predict($test[0][$i])) + floatval($model3->predict($test[0][$i]))) / 3;
        $result[] = (floatval($model1->predict($test[0][$i])) + floatval($model3->predict($test[0][$i]))) / 2;

        $error += abs($result[$i] - $test[1][$i]);

        if (abs($result[$i] - $test[1][$i]) < $alpha) {
            $correct++;
        }
    }
    // echo "average error = ".($error/count($test[0])).PHP_EOL;
    $accuracy[0] = $correct / count($test[0]) * 100;
    $accuracy[1] = ($error / count($test[0]));
    return $accuracy;
}

function getDataPartitioned($data, $crossValidation)
{

    $partitioned_array = array();
    $data_count        = count($data);

    $cross_size = floor($data_count / $crossValidation);

    for ($i = 0; $i < $crossValidation; $i++) {
        $indices               = array_rand($data, $cross_size);
        $partitioned_array[$i] = getSubArray($data, $indices);

    }

    return $partitioned_array;
}

function getSubArray(&$data, $indices)
{
    $array = array();

    for ($i = 0; $i < count($indices); $i++) {

        $array[] = $data[$indices[$i]];
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

    if ($full) {
        foreach ($data as $d) {
            $train[0][] = [floatval($d[1]), floatval($d[2])]; // sample data
            //var_dump($train[0][]);
            $train[1][] = floatval($d[0]); // target output
        }

        return $train;
    }

    foreach ($data as $d) {
        $train[0][] = [floatval($d[1])]; // sample data
        $train[1][] = floatval($d[0]); // target output
    }

    return $train;
}

function writeCSV($data, $filename)
{
    $file = fopen("$filename", "w");

    if ($file == null) {
        echo "Error opening $filename\n";
        return;
    }

    for ($i = 0; $i < count($data); $i++) {
        fprintf($file, "%d,%f\n", $data[$i][0], $data[$i][1]);
    }
    fclose($file);
}

function writeCSVPlotData($data, $filename)
{
    $file = fopen("$filename", "w");

    if ($file == null) {
        echo "Error opening $filename\n";
        return;
    }

    for ($i = 0; $i < count($data); $i++) {
        fprintf($file, "%f,%f,%f\n", $data[$i][0], $data[$i][1], $data[$i][2]);
    }
    fclose($file);
}

function getCSVData($file)
{
    $dataArray = [];
    if (($handle = fopen($file, "r")) !== false) {
        while (($data = fgetcsv($handle, 5000, ",")) !== false) {
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

function transposeData($data)
{
    $retData = array();
    foreach ($data as $row => $columns) {
        foreach ($columns as $row2 => $column2) {
            $retData[$row2][$row] = $column2;
        }
    }
    return $retData;
}

function shiftData($data, $numPushes)
{
    $firstColumn = array_column($data, 0);
    $firstColumn = array_reverse($firstColumn);

    for ($j = 0; $j < $numPushes; $j++) {
        array_push($firstColumn, array_shift($firstColumn));
    }

    $firstColumn = array_reverse($firstColumn);

    for ($i = 0; $i < count($firstColumn); $i++) {
        $data[$i][0] = $firstColumn[$i];
    }

    return $data;
}
