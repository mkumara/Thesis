<?php

require_once 'vendor/autoload.php';
use Phpml\Classification\KNearestNeighbors;

$knn = new KNearestNeighbors($k = 8);

$samples = [[1, 3], [1, 4], [2, 4], [3, 1], [4, 1], [4, 2]];
$labels  = [8, 10, 5, 30, 5, 2];

$knn->train($samples, $labels);

$r = $knn->predict([3, 3]);

echo $r;
