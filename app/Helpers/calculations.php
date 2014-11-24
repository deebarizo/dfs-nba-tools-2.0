<?php

function testYo() {
	dd('bob');
}

function calculateMpCs($gameLogs, $date) {
	$totalGames = 0;
	$totalMinutes = 0;

	foreach ($gameLogs as $gameLog) {
		if ($gameLog->season_id != 11) {
			continue;
		}

		if ($gameLog->status == 'Played') {
			$totalGames++;
			$totalMinutes += $gameLog->mp;			
		}
	}

	return $totalMinutes / $totalGames;
}

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
		$dataSetsAB2['axb'][] = $value * $dataSetsAB[$yVar][$index];
		$dataSetsAB2['aSquared'][] = $value * $value;
		$dataSetsAB2['bSquared'][] = $dataSetsAB[$yVar][$index] * $dataSetsAB[$yVar][$index];
	}

	$axbSum = array_sum($dataSetsAB2['axb']);
	$aSquaredSum = array_sum($dataSetsAB2['aSquared']);
	$bSquaredSum = array_sum($dataSetsAB2['bSquared']);

	$correlation = $axbSum / (sqrt($aSquaredSum * $bSquaredSum));

	$dataSetsJSON = [];

	foreach ($dataSets['Scores'] as $index => $dataSet) {
		$dataSetsJSON[] = [$dataSet, (float)($dataSets[$yVar][$index])];
	}

	// https://www.youtube.com/watch?v=JvS2triCgOY Line of Best Fit
	// http://www.mathopenref.com/coordequation.html Calculate Points on the Line of Best Fit

	$bOne = $axbSum / $aSquaredSum;
	$bNaught = $mean[$yVar] - ($mean[$xVar] * $bOne);

	$data = [
		'correlation' => $correlation,
		'dataSetsJSON' => $dataSetsJSON,
		'bOne' => $bOne,
		'bNaught' => $bNaught
	];

	return $data;
}

function calculateMeanOfArray($arr) {
	return array_sum($arr) / count($arr);
}