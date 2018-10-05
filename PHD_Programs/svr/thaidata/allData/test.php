<?php

$data = [
  [1,2,3],
  [2,3,4],
  [5,6,7]
];

print_r($data);

$d = shiftData($data, 2);

print_r($d);

function shiftData($data, $numPushes)
{
	$firstColumn = array_column($data, 0);
    $firstColumn = array_reverse($firstColumn);
	

    for($i=0; $j< $numPushes; $j++)
    {
	   array_push($firstColumn, array_shift($firstColumn));
	}

	 $firstColumn = array_reverse($firstColumn);
     print_r($firstColumn);

	for ($i=0; $i < count($firstColumn); $i++)
	{
		$data[$i][0] = $firstColumn[$i];
	}

	return $data;
}