<?php

function scrapeBoxLineScoreBR($y, $rowContents, $players, $games, $location, $teamID, $crawlerBR, $abbrBR, $i, $basicStats, $advStats) {

	$rowContents[$location][$i]['team_id'] = $games[$y]->$teamID;	
	
	$dnpCheck = $crawlerBR->filter('table#'.$abbrBR.'_basic > tbody > tr:nth-child('.$i.') > td:nth-child(2)')->text();

	if (is_numeric($dnpCheck[0]) === false) {
		$rowContents[$location][$i]['status'] = $dnpCheck;

		for ($n=1; $n <= 21; $n++) { 
			if (isset($basicStats[$n]) && $n != 1) {
				$rowContents[$location][$i][$basicStats[$n]] = 0;
			} elseif ($n === 1) { // player name
				$rowContents[$location][$i][$basicStats[$n]] = $crawlerBR->filter('table#'.$abbrBR.'_basic > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();
			}
		}

		for ($n=5; $n <= 14; $n++) { 
			$rowContents[$location][$i][$advStats[$n]] = 0;
		}
	} else {
		$rowContents[$location][$i]['status'] = 'Played';

		for ($n=1; $n <= 21; $n++) { 
			if (isset($basicStats[$n])) {
				$rowContents[$location][$i][$basicStats[$n]] = $crawlerBR->filter('table#'.$abbrBR.'_basic > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();
			}
		}

		for ($n=5; $n <= 14; $n++) { 
			$rowContents[$location][$i][$advStats[$n]] = $crawlerBR->filter('table#'.$abbrBR.'_advanced > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();
		}				
	}

	foreach ($players as $player) {
		if ($player->name == $rowContents[$location][$i]['name']) {
			$rowContents[$location][$i]['player_id'] = $player->id;

			break;
		}
	}

	return $rowContents;
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

	$calculatePredictedScore = '(Vegas Score - '.$bNaught.') / '.$bOne; 

	$data = [
		'correlation' => $correlation,
		'dataSetsJSON' => $dataSetsJSON,
		'perfectLineJSON' => $perfectLineJSON,
		'lineOfBestFitJSON' => $lineOfBestFitJSON,
		'calculatePredictedScore' => $calculatePredictedScore
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

