<?php

function scrapeForOdds($client, $date) {
	$dateSAO = str_replace('-', '', $date);
	$linkSAO = "http://www.scoresandodds.com/grid_".$dateSAO.".html";

	$crawlerSAO = $client->request('GET', $linkSAO);

	$rowCountSAO = $crawlerSAO->filter('div#nba')->nextAll()->filter('tr.team')->count();

	for ($i = 0; $i < $rowCountSAO; $i++) { // nth-child does not start with a zero index
		$vegasScores[$i]['team'] = $crawlerSAO->filter('div#nba')->nextAll()->filter('tr.team > td.name')->eq($i)->text();

		$vegasScores[$i]['line'] = $crawlerSAO->filter('div#nba')->nextAll()->filter('tr.team > td.currentline')->eq($i)->text();

		if ($i % 2 == 0) { // even or odd check
			$timeIndex = $i / 2;
		} else {
			$timeIndex = ($i / 2) - 0.5;
		}

		$time = $crawlerSAO->filter('div#nba')->nextAll()->filter('tr.time > td')->eq($timeIndex)->text();
		$time = preg_replace("/ EST/", "", $time);
		$time = preg_replace("/ EDT/", "", $time);
		$vegasScores[$i]['time'] = date('g:i A', strtotime('-65 minutes', strtotime($time)));

		if ($vegasScores[$i]['line'] == '') {
			return 'No lines yet.';
		}
	}

	# dd($vegasScores);

	foreach ($vegasScores as &$vegasScore) {
		$vegasScore['team'] = preg_replace("/^(\d* )(\D+)/", "$2", $vegasScore['team']);
		$vegasScore['team'] = ucwords(strtolower($vegasScore['team']));
		$vegasScore['team'] = trim($vegasScore['team']);

		if ($vegasScore['team'] == 'Portland Trailblazers') {
			$vegasScore['team'] = 'Portland Trail Blazers'; // to match BR team name
		}
	}

	unset($vegasScore);

	foreach ($vegasScores as &$vegasScore) {
		$vegasScore['line'] = trim($vegasScore['line']);
		$vegasScore['line'] = preg_replace("/(-\S*)( -\S*)$/", "$1", $vegasScore['line']);
		$vegasScore['line'] = preg_replace("/(o\S*)$/", "", $vegasScore['line']);
		$vegasScore['line'] = preg_replace("/(u\S*)$/", "", $vegasScore['line']);
	}

	unset($vegasScore);

	# vegas team score equations
	## favorite = (total + spread) / 2
	## underdog = (total - spread) / 2

	foreach ($vegasScores as $index => &$vegasScore) {
		if ($index % 2 == 0) { // check to see if number is even
			if ($vegasScore['line'][0] == '-') {
				$vegasScore['score'] = ($vegasScores[$index + 1]['line'] - $vegasScore['line']) / 2; // not plus because spread is negative
			} elseif ($vegasScore['line'][0] == 'P' && $vegasScore['line'][1] == 'K') {
				$vegasScore['score'] = ($vegasScores[$index + 1]['line'] + 0) / 2;
			} else {
				$vegasScore['score'] = ($vegasScore['line'] + $vegasScores[$index + 1]['line']) / 2;
			}
		} else {
			if ($vegasScore['line'][0] == '-') {
				$vegasScore['score'] = ($vegasScores[$index - 1]['line'] - $vegasScore['line']) / 2; // not plus because spread is negative
			} elseif ($vegasScore['line'][0] == 'P' && $vegasScore['line'][1] == 'K') {
				$vegasScore['score'] = ($vegasScores[$index - 1]['line'] + 0) / 2;
			} else {
				$vegasScore['score'] = ($vegasScore['line'] + $vegasScores[$index - 1]['line']) / 2;
			}
		}		
	}

	unset($vegasScore);

	# ddAll($vegasScores);

	return $vegasScores;
}

function scrapeForGamesTable($client, $crawler, $tableIDinBR, $teams, $seasonId, $gamesCount, $rowCount) {
	$rowContents = array();

	$tableNames[1] = 'date';
	$tableNames[2] = 'time';
	$tableNames[3] = 'link_br';
	$tableNames[4] = 'road_team_id';
	$tableNames[5] = 'road_team_score';
	$tableNames[6] = 'home_team_id';
	$tableNames[7] = 'home_team_score';
	$tableNames[8] = 'ot_periods';
	$tableNames[9] = 'notes';

	$startingGame = $gamesCount + 1;
	$i = $startingGame;

	$rowCount += 5; // number = number of month rows on basketball reference

	do {
		$isGameRow = $crawler->filter('table#'.$tableIDinBR.' > tbody > tr:nth-child('.$i.') > td:nth-child(3)')->count(); // to filter month rows

		# dd($isGameRow);

		if ($isGameRow) {
			$gameRowAnchorText = $crawler->filter('table#'.$tableIDinBR.' > tbody > tr:nth-child('.$i.') > td:nth-child(3)')->text();

			if ($gameRowAnchorText == '') {
				$rowCount++;
				// prf('bob');
			} 

			if ($gameRowAnchorText != '') {
				for ($n=1; $n <= 9; $n++) { // nth-child does not start with a zero index
					switch ($n) {
						case 1: // Date
							$scrapedDate = $crawler->filter('table#'.$tableIDinBR.' > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();
							$scrapedDate = substr($scrapedDate, 5);
							$rowContents[$i]['date'] = date('Y-m-d', strtotime(str_replace('-', '/', $scrapedDate)));
							break;

						case 3: // URL
							$rowContents[$i][$tableNames[$n]] = $crawler->filter('table#'.$tableIDinBR.' > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->selectLink('Box Score')->link()->getUri();
							break;

						case 4: // Road Team
							$roadTeam = $crawler->filter('table#'.$tableIDinBR.' > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();
							foreach ($teams as $team) {
								if ($team->name_br == $roadTeam) {
									$rowContents[$i][$tableNames[$n]] = $team->id;
								}
							}
							break;

						case 6: // Home Team
							$homeTeam = $crawler->filter('table#'.$tableIDinBR.' > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();
							foreach ($teams as $team) {
								if ($team->name_br == $homeTeam) {
									$rowContents[$i][$tableNames[$n]] = $team->id;
								}
							}
							break;

						case 8: // OT Periods
							$scrapedOTField = $crawler->filter('table#'.$tableIDinBR.' > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();
							if ($scrapedOTField == '') {
								$rowContents[$i]['ot_periods'] = 0;
							} elseif ($scrapedOTField == 'OT') {
								$rowContents[$i]['ot_periods'] = 1;
							} elseif ($scrapedOTField != 'OT' && $scrapedOTField != '') {
								$rowContents[$i]['ot_periods'] = substr($scrapedOTField, 0, 1);
							}
							break;

						case 9: // Notes
							$scrapedNotesField = $crawler->filter('table#'.$tableIDinBR.' > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();
							if ($scrapedNotesField == '') {
								$rowContents[$i]['notes'] = null;
							} else {
								$rowContents[$i]['notes'] = $scrapedNotesField;
							}
							break;

						default:
							$rowContents[$i][$tableNames[$n]] = $crawler->filter('table#'.$tableIDinBR.' > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();
							break;
					}
				}
			} 
		} 

		$startingGame++;

		$i++;	
	} while ($startingGame <= $rowCount);

	# dd($rowContents);

	foreach ($rowContents as &$row) {
		$dateSAO = str_replace('-', '', $row['date']);
		$linkSAO = "http://www.scoresandodds.com/grid_".$dateSAO.".html";

		$crawlerSAO = $client->request('GET', $linkSAO);

		$rowCountSAO = $crawlerSAO->filter('div#nba')->nextAll()->filter('tr.time')->count();

		for ($i = 0; $i < $rowCountSAO; $i++) { // nth-child does not start with a zero index
			$roadTeamsSAO[$i] = $crawlerSAO->filter('div#nba')->nextAll()->filter('tr.odd > td.name')->eq($i)->text();
		}

		foreach ($roadTeamsSAO as &$roadTeamSAO) {
			$roadTeamSAO = preg_replace("/^(\d* )(\D+)/", "$2", $roadTeamSAO);
			$roadTeamSAO = ucwords(strtolower($roadTeamSAO));
			$roadTeamSAO = trim($roadTeamSAO);

			if ($roadTeamSAO == 'Portland Trailblazers') {
				$roadTeamSAO = 'Portland Trail Blazers'; // to match BR team name
			}
		}

		unset($roadTeamSAO);

		foreach ($teams as $team) {
			if ($team->id == $row['road_team_id']) {
				$roadTeam = $team->name_br;
			}
		}

		foreach ($roadTeamsSAO as $index => $roadTeamSAO) {
			if ($roadTeamSAO == $roadTeam) {
				$rowNumberSAO = $index;
				break;
			}
		}

		if (isset($rowNumberSAO) === false) {
			echo 'error: no team match in SAO';
			dd($row);
		}

		$contentsSAO['road_team'] = $crawlerSAO->filter('div#nba')->nextAll()->filter('tr.odd > td.currentline')->eq($rowNumberSAO)->text();

		$contentsSAO['home_team'] = $crawlerSAO->filter('div#nba')->nextAll()->filter('tr.even > td.currentline')->eq($rowNumberSAO)->text();

		foreach ($contentsSAO as &$contentSAO) {
			$contentSAO = trim($contentSAO);
			$contentSAO = preg_replace("/(-\S*)( -\S*)$/", "$1", $contentSAO);
			$contentSAO = preg_replace("/(o\S*)$/", "", $contentSAO);
			$contentSAO = preg_replace("/(u\S*)$/", "", $contentSAO);
		}

		unset($contentSAO);

		# vegas team score equations
		## favorite = (total + spread) / 2
		## underdog = (total - spread) / 2

		if ($contentsSAO['road_team'][0] == '-') { // zero index of string is first character
			$row['vegas_road_team_score'] = ($contentsSAO['home_team'] - $contentsSAO['road_team']) / 2;
			$row['vegas_home_team_score'] = ($contentsSAO['home_team'] + $contentsSAO['road_team']) / 2;	
		} elseif ($contentsSAO['road_team'][0] == 'P' && $contentsSAO['road_team'][1] == 'K') {
			$row['vegas_road_team_score'] = ($contentsSAO['home_team'] - 0) / 2;
			$row['vegas_home_team_score'] = ($contentsSAO['home_team'] + 0) / 2;
		} elseif ($contentsSAO['home_team'][0] == 'P' && $contentsSAO['home_team'][1] == 'K') {
			$row['vegas_road_team_score'] = ($contentsSAO['road_team'] - 0) / 2;
			$row['vegas_home_team_score'] = ($contentsSAO['road_team'] + 0) / 2;			
		} else {
			$row['vegas_road_team_score'] = ($contentsSAO['road_team'] + $contentsSAO['home_team']) / 2;
			$row['vegas_home_team_score'] = ($contentsSAO['road_team'] - $contentsSAO['home_team']) / 2;	
		}

		$crawlerBoxScoreBR = $client->request('GET', $row['link_br']);

		$contentsBoxScoreBR = $crawlerBoxScoreBR->filter('table#four_factors > tbody > tr > td')->eq(1)->text();
		$contentsBoxScoreBR = trim($contentsBoxScoreBR);

		$row['pace'] = $contentsBoxScoreBR;		
	}

	unset($row);

	return $rowContents;
}

function scrapeBoxLineScoreBR($rowContents, $players, $game, $location, $teamID, $crawlerBR, $abbrBR, $i, $basicStats, $advStats) {

	$rowContents[$location][$i]['team_id'] = $game->$teamID;	

	$dnpCheck = $crawlerBR->filter('table#'.$abbrBR.'_basic > tbody > tr:nth-child('.$i.') > td:nth-child(2)')->text();

	if (is_numeric(substr($dnpCheck, 0, 1))) {
		$status = 'Played';
	} else {
		$status = $dnpCheck;
	}

	if ($status != 'Played') {
		$rowContents[$location][$i]['status'] = $status;

		for ($n=1; $n <= 20; $n++) { 
			if (isset($basicStats[$n]) && $n != 1) {
				$rowContents[$location][$i][$basicStats[$n]] = 0;
			} elseif ($n === 1) { // player name
				$rowContents[$location][$i][$basicStats[$n]] = $crawlerBR->filter('table#'.$abbrBR.'_basic > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();
			}
		}

		for ($n=7; $n <= 16; $n++) { 
			$rowContents[$location][$i][$advStats[$n]] = 0;
		}
	} else {
		$rowContents[$location][$i]['status'] = $status;

		for ($n=1; $n <= 20; $n++) { 
			if (isset($basicStats[$n]) and $n != 2) {
				$rowContents[$location][$i][$basicStats[$n]] = $crawlerBR->filter('table#'.$abbrBR.'_basic > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();
			} elseif (isset($basicStats[$n]) and $n == 2) {
				$mpRawData = $crawlerBR->filter('table#'.$abbrBR.'_basic > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();

				$minutes = preg_replace("/(\d*)(:)(\d\d)/", "$1", $mpRawData);
				$seconds = preg_replace("/(\d*)(:)(\d\d)/", "$3", $mpRawData);

				$rowContents[$location][$i][$basicStats[$n]] = $minutes + ($seconds / 60);
			}
		}

		for ($n=7; $n <= 16; $n++) { 
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