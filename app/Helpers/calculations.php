<?php

// Top Lineups

function calculatePlayerPercentagesOfTopLineups($topLineups) {
	$playerPercentages = array();

	foreach ($topLineups as $topLineup) {
		foreach ($topLineup as $key => $player) {
			if (is_numeric($key)) {
				if (!isset($playerPercentages[$player->name])) {
					$playerPercentages[$player->name] = new stdClass();

					$playerPercentages[$player->name]->dollars = 10;

					$playerPercentages[$player->name]->position = $player->position;
					$playerPercentages[$player->name]->position_number = $player->position_number;
					$playerPercentages[$player->name]->salary = $player->salary;
					$playerPercentages[$player->name]->fppg_minus1 = $player->fppg_minus1;
					$playerPercentages[$player->name]->vr_minus1 = $player->vr_minus1;
					if (isset($player->filter)) {
						$playerPercentages[$player->name]->filter = $player->filter;
					}
					$playerPercentages[$player->name]->dollars = 10;

					continue;
				}

				if (isset($playerPercentages[$player->name])) {
					$playerPercentages[$player->name]->dollars += 10;
				}
			}
		}
	}

	$totalSpent = count($topLineups) * 10;

	foreach ($playerPercentages as $player => &$values) {
		$values->percentage = numFormat(($values->dollars / $totalSpent) * 100, 2); 
	}

	unset($values);

	foreach ($playerPercentages as $key => $row) {
	    $percentage[$key] = $row->percentage;
	}

	array_multisort($percentage, SORT_DESC, $playerPercentages);

	foreach ($playerPercentages as $playerName => $values) {
		$playersInTopLineups[] = $playerName.' ('.$values->position.')';
	}

	foreach ($playerPercentages as $playerName => $values) {
		$percentagesInTopLineups[] = (float)$values->percentage;
	}

	# ddAll($playerPercentages);

	return array($playerPercentages, $playersInTopLineups, $percentagesInTopLineups);
}

// Solver

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

function calculateFppm($player, $gameLogs) {
	$gamesPlayed = count($gameLogs);
    
    $totalFp = 0;
    $totalMp = 0;

    foreach ($gameLogs as $gameLog) {
        $totalFp += $gameLog->pts +
        			($gameLog->trb * 1.2) +
        			($gameLog->ast * 1.5) +
        			($gameLog->stl * 2) +
        			($gameLog->blk * 2) +
        			($gameLog->tov * -1);

        $totalMp += $gameLog->mp;
    }

    if ($gamesPlayed > 0) {
        $player->fppm = numFormat($totalFp / $totalMp);
    } else {
        $player->fppm = number_format(0, 2);
    }

    return $player;
}

function calculateMpMod($gameLogs, $mpOtFilter) {
	$totalGames = 0;
	$totalMinutes = 0;

	foreach ($gameLogs as $gameLog) {
		$totalGames++;
		$totalMinutes += $gameLog->mp;			
	}

	# ddAll($gameLogs[0]->player_id);

	if ($totalGames == 0) {
		return 0;
	}

	return ($totalMinutes - $mpOtFilter) / $totalGames;
}

// Studies

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

	foreach ($dataSetsAB[$xVar] as $index => $value) {
		$dataSetsAB2['axb'][] = $value * $dataSetsAB[$yVar][$index];
		$dataSetsAB2['aSquared'][] = $value * $value;
		$dataSetsAB2['bSquared'][] = $dataSetsAB[$yVar][$index] * $dataSetsAB[$yVar][$index];
	}

	$axbSum = array_sum($dataSetsAB2['axb']);
	$aSquaredSum = array_sum($dataSetsAB2['aSquared']);
	$bSquaredSum = array_sum($dataSetsAB2['bSquared']);

	$correlation = $axbSum / (sqrt($aSquaredSum * $bSquaredSum));

	$dataSetsJSON = [];

	foreach ($dataSets[$xVar] as $index => $dataSet) {
		$dataSetsJSON[] = [(float)$dataSet, (float)$dataSets[$yVar][$index]];
	}

	// https://www.youtube.com/watch?v=JvS2triCgOY Line of Best Fit
	// http://www.mathopenref.com/coordequation.html Calculate Points on the Line of Best Fit

	$bOne = $axbSum / $aSquaredSum;
	$bNaught = $mean[$yVar] - ($mean[$xVar] * $bOne);

	$data = [
		'correlation' => $correlation,
		'dataSetsJSON' => $dataSetsJSON,
		'bOne' => $bOne,
		'bNaught' => $bNaught,
		'xVar' => $xVar,
		'yVar' => $yVar
	];

	return $data;
}

function calculateMeanOfArray($arr) {
	return array_sum($arr) / count($arr);
}