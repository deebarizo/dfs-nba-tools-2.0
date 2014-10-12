<?php

function calculateCorrelation($xArray, $yArray, $xVar, $yVar) {

	$dataSets = [
		$xVar => $xArray,
		$yVar => $yArray
	];

	asort($dataSets[$xVar]);

	foreach ($dataSets as $dataName => $dataSet) {
		$mean[$dataName] = calculateMeanOfArray($dataSet);
	}

	$dataSetsAB = [
		$xVar => [],
		$yVar => [],
	];
	
	foreach ($dataSets as $dataName => $dataSet) {
		foreach ($dataSet as $index => $value) {
			$dataSetsAB[$dataName][$index] = $value - $mean[$dataName];
		}
	}

	$dataSetsAB2 = [
		'axb' => [],
		'aSquared' => [],
		'bSquared' => []	
	];

	foreach ($dataSetsAB['Scores'] as $index => $value) {
		$dataSetsAB2['axb'][] = $value * $dataSetsAB['Vegas Scores'][$index];
		$dataSetsAB2['aSquared'][] = $value * $value;
		$dataSetsAB2['bSquared'][] = $dataSetsAB['Vegas Scores'][$index] * $dataSetsAB['Vegas Scores'][$index];
	}

	$axbSum = array_sum($dataSetsAB2['axb']);
	$aSquaredSum = array_sum($dataSetsAB2['aSquared']);
	$bSquaredSum = array_sum($dataSetsAB2['bSquared']);

	$correlation = $axbSum / (sqrt($aSquaredSum * $bSquaredSum));

	$dataSetsJSON = [];

	foreach ($dataSets['Scores'] as $index => $dataSet) {
		$dataSetsJSON[] = [$dataSet, (float)($dataSets['Vegas Scores'][$index])];
	}

	$perfectLineJSON = [];

	for ($x=40; $x <= 150 ; $x++) { 
		$y = $x;
		$perfectLineJSON[] = [$x, $y];
	}

	// https://www.youtube.com/watch?v=JvS2triCgOY Line of Best Fit
	// http://www.mathopenref.com/coordequation.html Calculate Points on the Line of Best Fit

	$bOne = $axbSum / $aSquaredSum;
	$bNaught = $mean[$yVar] - ($mean[$xVar] * $bOne);

	for ($x=40; $x <= 150 ; $x++) { 
		$y = ($bOne * $x) + $bNaught;
		$lineOfBestFitJSON[] = [$x, $y];
	}

	$data = [
		'correlation' => $correlation,
		'dataSetsJSON' => $dataSetsJSON,
		'perfectLineJSON' => $perfectLineJSON,
		'lineOfBestFitJSON' => $lineOfBestFitJSON
	];

	return $data;
}

function calculateMeanOfArray($arr) {
	return array_sum($arr) / count($arr);
}

function ddAll($var) {
	echo '<pre>';
	print_r($var);
	echo '</pre>';

	exit();
}

