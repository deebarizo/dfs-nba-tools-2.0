<?php

function scrapeBoxLineScoreBR($rowContents, $players, $game, $location, $teamID, $crawlerBR, $abbrBR, $i, $basicStats, $advStats) {

	$rowContents[$location][$i]['team_id'] = $game->$teamID;	

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
			if (isset($basicStats[$n]) and $n != 2) {
				$rowContents[$location][$i][$basicStats[$n]] = $crawlerBR->filter('table#'.$abbrBR.'_basic > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();
			} elseif (isset($basicStats[$n]) and $n == 2) {
				$mpRawData = $crawlerBR->filter('table#'.$abbrBR.'_basic > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();

				$minutes = preg_replace("/(\d*)(:)(\d\d)/", "$1", $mpRawData);
				$seconds = preg_replace("/(\d*)(:)(\d\d)/", "$3", $mpRawData);

				$rowContents[$location][$i][$basicStats[$n]] = $minutes + ($seconds / 60);
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