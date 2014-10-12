<?php namespace App\Http\Controllers;

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

use App\Season;
use App\Team;
use App\Game;

use Illuminate\Http\Request;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

class ScrapersController {

	public function season_form() {
		return view('scrapers.season_form');
	}

	public function season_scraper(Request $request) {
		$endYear = $request->input('end_year');
		$gameType = $request->input('type');
		$indexOfStartingGameBR = $request->input('game_groups');

		switch ($gameType) {
			case 'regular':
				$gameTypeInMsg = 'regular season';
				break;
			
			case 'playoffs':
				$gameTypeInMsg = 'playoff';
				break;
		}

		$client = new Client();

		$crawlerBR = $client->request('GET', 'http://www.basketball-reference.com/leagues/NBA_'.$endYear.'_games.html');

		$season = Season::where('end_year', $endYear)->first();
		$teams = Team::all();
		$games = Game::where('type', $gameType)->get();

		$status_code = $client->getResponse()->getStatus();

		if ($status_code == 200) {
			$savedGameCount = 0;

			switch ($gameType) {
				case 'regular':
					$tableIDinBR = 'games';
					break;
				
				case 'playoffs':
					$tableIDinBR = 'games_playoffs';
					break;
			} 

			$rowCount = $crawlerBR->filter('table#'.$tableIDinBR.' > tbody > tr')->count();

			if ($rowCount == count($games)) {
				$message = 'All the '.$gameTypeInMsg.' games were already scraped and saved.';

				return redirect('scrapers/season_form')->with('message', $message);
			}

			$rowContents = array();

			$tableNames[1] = 'date';
			$tableNames[2] = 'link_br';
			$tableNames[3] = 'road_team_id';
			$tableNames[4] = 'road_team_score';
			$tableNames[5] = 'home_team_id';
			$tableNames[6] = 'home_team_score';
			$tableNames[7] = 'ot_periods';
			$tableNames[8] = 'notes';

			for ($i = $indexOfStartingGameBR; $i <= $rowCount; $i++) { // nth-child does not start with a zero index
				for ($n=1; $n <= 8; $n++) { // nth-child does not start with a zero index
					if ($n !== 2) {
						$rowContents[$i][$tableNames[$n]] = $crawlerBR->filter('table#'.$tableIDinBR.' > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();
					} else {
						$rowContents[$i][$tableNames[$n]] = $crawlerBR->filter('table#'.$tableIDinBR.' > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->selectLink('Box Score')->link()->getUri();
					}
				}

				$scrapedDate = $rowContents[$i]['date'];
				$scrapedDate = substr($scrapedDate, 5);
				$rowContents[$i]['date'] = date('Y-m-d', strtotime(str_replace('-', '/', $scrapedDate)));

				$roadTeam = $rowContents[$i]['road_team_id']; // this is for SAO scraping

				$twoTeams = [
					'home_team_id',
					'road_team_id'
				];

				foreach ($twoTeams as $row) {
					foreach ($teams as $team) {				    
					    if ($rowContents[$i][$row] == $team->name_br) {
					    	$rowContents[$i][$row] = $team->id;
					    	break;
					    }
					} 
				}

				$scrapedOTField = $rowContents[$i]['ot_periods'];
				if ($scrapedOTField == '') {
					$rowContents[$i]['ot_periods'] = 0;
				} elseif ($scrapedOTField == 'OT') {
					$rowContents[$i]['ot_periods'] = 1;
				} elseif ($scrapedOTField != 'OT' && $scrapedOTField != '') {
					$rowContents[$i]['ot_periods'] = substr($scrapedOTField, 0, 1);
				}

				$scrapedNotesField = $rowContents[$i]['notes'];
				if ($scrapedNotesField == '') {
					$rowContents[$i]['notes'] = null;
				}

				$rowContents[$i]['season_id'] = $season->id;

				$dateSAO = str_replace('-', '', $rowContents[$i]['date']);
				$linkSAO = "http://www.scoresandodds.com/grid_".$dateSAO.".html";

				$crawlerSAO = $client->request('GET', $linkSAO);

				$rowCountSAO = $crawlerSAO->filter('div#nba')->nextAll()->filter('tr.time')->count();

				for ($iSAO=0; $iSAO < $rowCountSAO; $iSAO++) { // nth-child does not start with a zero index
					$roadTeamsSAO[$iSAO] = $crawlerSAO->filter('div#nba')->nextAll()->filter('tr.odd > td.name')->eq($iSAO)->text();
				}

				foreach ($roadTeamsSAO as &$roadTeamSAO) {
					$roadTeamSAO = preg_replace("/^(\d* )(\D+)/", "$2", $roadTeamSAO);
					$roadTeamSAO = ucwords(strtolower($roadTeamSAO));
					$roadTeamSAO = trim($roadTeamSAO);

					if ($roadTeamSAO == 'Portland Trailblazers') {
						$roadTeamSAO = 'Portland Trail Blazers';
					}

					if ($roadTeamSAO == 'New Orleans Hornets') {
						$roadTeamSAO = 'New Orleans Pelicans';
					}
				}

				unset($roadTeamSAO);

				foreach ($roadTeamsSAO as $index => $roadTeamSAO) {
					if ($roadTeamSAO == $roadTeam) {
						$rowNumberSAO = $index;
						break;
					}

					$rowNumberSAO = "error: no team match in SAO";
				}

				if (is_numeric($rowNumberSAO) === false) {
					echo $rowNumberSAO; 
					dd($rowContents[$i]);
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

				if ($contentsSAO['road_team'][0] == '-') { // zero index of string is first character
					$rowContents[$i]['vegas_road_team_score'] = ($contentsSAO['home_team'] - $contentsSAO['road_team']) / 2;
					$rowContents[$i]['vegas_home_team_score'] = ($contentsSAO['home_team'] + $contentsSAO['road_team']) / 2;					
				} elseif (($contentsSAO['road_team'][0] == 'P') && ($contentsSAO['road_team'][1] == 'K')) {
					$rowContents[$i]['vegas_road_team_score'] = ($contentsSAO['home_team'] - 0) / 2;
					$rowContents[$i]['vegas_home_team_score'] = ($contentsSAO['home_team'] + 0) / 2;
				} elseif (($contentsSAO['home_team'][0] == 'P') && ($contentsSAO['home_team'][1] == 'K')) {
					$rowContents[$i]['vegas_road_team_score'] = ($contentsSAO['road_team'] - 0) / 2;
					$rowContents[$i]['vegas_home_team_score'] = ($contentsSAO['road_team'] + 0) / 2;			
				} else {
					$rowContents[$i]['vegas_road_team_score'] = ($contentsSAO['road_team'] + $contentsSAO['home_team']) / 2;
					$rowContents[$i]['vegas_home_team_score'] = ($contentsSAO['road_team'] - $contentsSAO['home_team']) / 2;	
				}

				# vegas team score equations
				## favorite = (total + spread) / 2
				## underdog = (total - spread) / 2

				$crawlerBoxScoreBR = $client->request('GET', $rowContents[$i]['link_br']);

				$contentsBoxScoreBR = $crawlerBoxScoreBR->filter('table#four_factors > tbody > tr > td')->eq(1)->text();
				$contentsBoxScoreBR = trim($contentsBoxScoreBR);

				$rowContents[$i]['pace'] = $contentsBoxScoreBR;

				$rowContents[$i]['type'] = $gameType;

				$duplicateGameToggle = false;

				foreach ($games as $game) {				    
				    if ($rowContents[$i]['link_br'] == $game->link_br) {
				    	$duplicateGameToggle = true;

				    	break;
				    }
				} 

				if ($duplicateGameToggle === false) {
					$game = new Game;

					$game->season_id = $rowContents[$i]['season_id'];
					$game->date = $rowContents[$i]['date'];
					$game->link_br = $rowContents[$i]['link_br'];
					$game->home_team_id = $rowContents[$i]['home_team_id'];
					$game->home_team_score = $rowContents[$i]['home_team_score'];
					$game->vegas_home_team_score = $rowContents[$i]['vegas_home_team_score'];
					$game->road_team_id = $rowContents[$i]['road_team_id'];
					$game->road_team_score = $rowContents[$i]['road_team_score'];
					$game->vegas_road_team_score = $rowContents[$i]['vegas_road_team_score'];
					$game->pace = $rowContents[$i]['pace'];
					$game->type = $rowContents[$i]['type'];
					$game->ot_periods = $rowContents[$i]['ot_periods'];
					$game->notes = $rowContents[$i]['notes'];

					$game->save();

					$savedGameCount++; 
				}

				if ($savedGameCount >= 105) { break; }
			}	

		} else {
			return 'Status Code is not 200.';
		}

		$message = $savedGameCount.' '.$gameTypeInMsg.' games were saved.';
					
		return redirect('scrapers/season_form')->with('message', $message);
	}

}
