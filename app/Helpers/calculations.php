<?php

function calculateFppg($player, $gameLogs) {
	$gamesPlayed = count($gameLogs) - ( isset($player->filter->dnp_games) ? $player->filter->dnp_games : 0 );

	$totalFp = 0;

	foreach ($gameLogs as $gameLog) {
	    $totalFp += $gameLog->fd_score;
	}

    if ( isset($player->filter->mp_ot_filter) && $player->filter->mp_ot_filter > 0 ) {
	    $totalFppm = 0;

	    foreach ($gameLogs as $gameLog) {
	        $totalFppm += $gameLog->fppm;
	    }

	    if ($gamesPlayed > 0) {
	    	$fppmPerGame = numFormat($totalFppm / $gamesPlayed);
	        $fppmPerGameWithVegasFilter = numFormat(($fppmPerGame * $player->vegas_filter) + $fppmPerGame);
	    } else {
	        $fppmPerGameWithVegasFilter = number_format(0, 2);
	    }

        $totalFp -= $fppmPerGameWithVegasFilter * $player->filter->mp_ot_filter;
    }

	if ($gamesPlayed > 0) {
	    $player->fppg = numFormat($totalFp / $gamesPlayed);
	    $player->fppgWithVegasFilter = numFormat( ($player->fppg * $player->vegas_filter) + $player->fppg );
	} else {
	    $player->fppg = numFormat(0, 2);
	    $player->fppgWithVegasFilter = numFormat(0, 2);
	}

	return $player;
}

function calculateCvForFppg($player, $gameLogs) {
	$gamesPlayed = count($gameLogs) - ( isset($player->filter->dnp_games) ? $player->filter->dnp_games : 0 );

	$totalFp = 0;

	foreach ($gameLogs as $gameLog) {
	    $totalFp += $gameLog->fd_score;
	}

	if ($gamesPlayed > 0) {
	    $fppg = numFormat($totalFp / $gamesPlayed);
	} else {
	    $fppg = numFormat(0, 2);
	}

	$totalSquaredDiff = 0; // For SD

	foreach ($gameLogs as $gameLog) {
	    $totalSquaredDiff = $totalSquaredDiff + pow($gameLog->fd_score - $fppg, 2);
	}

	if ($fppg != 0) {
	    $player->sd = sqrt($totalSquaredDiff / $gamesPlayed);
	    $player->cv = numFormat( ($player->sd / $fppg) * 100 );
	} else {
	    $player->sd = number_format(0, 2);
	    $player->cv = number_format(0, 2);
	}

	return $player;
}

function calculateFppm($player, $gameLogs) {
	$gamesPlayed = count($gameLogs) - ( isset($player->filter->dnp_games) ? $player->filter->dnp_games : 0 );
    
    $totalFppm = 0;

    foreach ($gameLogs as $gameLog) {
        $totalFppm += $gameLog->fppm;
    }

    if ($gamesPlayed > 0) {
        $player->fppmPerGame = numFormat($totalFppm / $gamesPlayed);
        $player->fppmPerGameWithVegasFilter = numFormat(($player->fppmPerGame * $player->vegas_filter) + $player->fppmPerGame);
    } else {
        $player->fppmPerGame = number_format(0, 2);
        $player->fppmPerGameWithVegasFilter = number_format(0, 2);
    }

    return $player;
}

function calculateCvForFppm($player, $gameLogs) {
	$gamesPlayed = count($gameLogs) - ( isset($player->filter->dnp_games) ? $player->filter->dnp_games : 0 );
    
    $totalFppm = 0;

    foreach ($gameLogs as $gameLog) {
        $totalFppm += $gameLog->fppm;
    }

    if ($gamesPlayed > 0) {
        $fppmPerGame = numFormat($totalFppm / $gamesPlayed);
    } else {
        $fppmPerGame = number_format(0, 2);
    }

    $totalSquaredDiff = 0; // For SD

    foreach ($gameLogs as $gameLog) {
        $totalSquaredDiff = $totalSquaredDiff + pow($gameLog->fppm - $fppmPerGame, 2);
    }

    if ($fppmPerGame != 0) {
        $player->sdFppm = sqrt($totalSquaredDiff / $gamesPlayed);
        $player->cvFppm = number_format(round(($player->sdFppm / $fppmPerGame) * 100, 2), 2);
    } else {
        $player->sdFppm = 0;
        $player->cvFppm = number_format(0, 2);
    }   

    return $player;
}

function calculateMpMod($gameLogs, $date) {
	$totalGames = 0;
	$totalMinutes = 0;

	foreach ($gameLogs as $gameLog) {
		$totalGames++;
		$totalMinutes += $gameLog->mp;			
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