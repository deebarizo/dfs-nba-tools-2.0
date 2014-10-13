<?php namespace App\Http\Controllers;

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

use App\Season;
use App\Team;
use App\Game;
use App\Player;
use App\BoxScoreLine;

use Illuminate\Http\Request;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

class ScrapersController {

	public function box_score_line_scraper() {
		$games = Game::all();
		$teams = Team::all();
		$players = Player::all();

		$client = new Client();

		foreach ($games as $key => $game) {
			$duplicateGameToggle = false;

			if (BoxScoreLine::exists()) {
				$dupCheck = BoxScoreLine::where('game_id', '=', $game->id)->firstOrFail();
			}

			$metadata = [];

			$crawlerBR = $client->request('GET', $game->link_br);

			$metadata['game_id'] = $game->id;

			$twoTeamsID = [
				'home_team' => 'home_team_id',
				'road_team' => 'road_team_id'
			];			

			foreach ($twoTeamsID as $location => $teamID) {
				$metadata['team_id'] = $game->$teamID;

				$abbrBR = '';

				foreach ($teams as $team) {
					if ($team->id == $game->$teamID) {
						$abbrBR = $team->abbr_br;

						break;
					}
				}

				$basicStats[1] = 'name';
				$basicStats[2] = 'mp';
				$basicStats[3] = 'fg';
				$basicStats[4] = 'fga';
				$basicStats[6] = 'threep';
				$basicStats[7] = 'threepa';
				$basicStats[9] = 'ft';
				$basicStats[10] = 'fta';
				$basicStats[12] = 'orb';
				$basicStats[13] = 'drb';
				$basicStats[14] = 'trb';
				$basicStats[15] = 'ast';
				$basicStats[16] = 'stl';
				$basicStats[17] = 'blk';
				$basicStats[18] = 'tov';
				$basicStats[19] = 'pf';
				$basicStats[20] = 'pts';
				$basicStats[21] = 'plus_minus';

				$advStats[5] = 'orb_percent';
				$advStats[6] = 'drb_percent';
				$advStats[7] = 'trb_percent';
				$advStats[8] = 'ast_percent';
				$advStats[9] = 'stl_percent';
				$advStats[10] = 'blk_percent';
				$advStats[11] = 'tov_percent';
				$advStats[12] = 'usg';
				$advStats[13] = 'off_rating';
				$advStats[14] = 'def_rating';

				// Starters

				for ($i=1; $i <= 5; $i++) { 
					$rowContents[$location][$i]['role'] = 'starter';

					for ($n=1; $n <= 21; $n++) { 
						if (isset($basicStats[$n])) {
							$rowContents[$location][$i][$basicStats[$n]] = $crawlerBR->filter('table#'.$abbrBR.'_basic > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();
						}
					}

					for ($n=5; $n <= 14; $n++) { 
						$rowContents[$location][$i][$advStats[$n]] = $crawlerBR->filter('table#'.$abbrBR.'_advanced > tbody > tr:nth-child('.$i.') > td:nth-child('.$n.')')->text();
					}

					foreach ($players as $player) {
						if ($player->name == $rowContents[$location][$i]['name']) {
							$rowContents[$location][$i]['player_id'] = $player->id;

							break;
						}
					}
				}

				// Reserves

				$rowCount = $crawlerBR->filter('table#'.$abbrBR.'_basic > tbody > tr')->count();

				for ($i=7; $i <= $rowCount; $i++) { 
					$rowContents[$location][$i]['role'] = 'reserve';
					
					$dnpCheck = $crawlerBR->filter('table#'.$abbrBR.'_basic > tbody > tr:nth-child('.$i.') > td:nth-child(2)')->text();

					if ($dnpCheck == 'Did Not Play') {
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
				}

				$boxScoreLine = new BoxScoreLine;

				$boxScoreLine->game_id = $metadata['game_id'];
				$boxScoreLine->team_id = $metadata['team_id'];

				foreach ($rowContents as $location) {
					foreach ($location as $playerData) {
						$boxScoreLine->player_id = $playerData['player_id'];
						$boxScoreLine->role = $playerData['role'];
						$boxScoreLine->mp = $playerData['mp'];
						$boxScoreLine->fg = $playerData['fg'];
						$boxScoreLine->fga = $playerData['fga'];
						$boxScoreLine->threep = $playerData['threep'];
						$boxScoreLine->threepa = $playerData['threepa'];
						$boxScoreLine->ft = $playerData['ft'];
						$boxScoreLine->fta = $playerData['fta'];
						$boxScoreLine->orb = $playerData['orb'];
						$boxScoreLine->drb = $playerData['drb'];
						$boxScoreLine->trb = $playerData['trb'];
						$boxScoreLine->ast = $playerData['ast'];
						$boxScoreLine->stl = $playerData['stl'];
						$boxScoreLine->blk = $playerData['blk'];
						$boxScoreLine->tov = $playerData['tov'];
						$boxScoreLine->pf = $playerData['pf'];
						$boxScoreLine->pts = $playerData['pts'];
						$boxScoreLine->plus_minus = $playerData['plus_minus'];
						$boxScoreLine->orb_percent = $playerData['orb_percent'];
						$boxScoreLine->drb_percent = $playerData['drb_percent'];
						$boxScoreLine->trb_percent = $playerData['trb_percent'];
						$boxScoreLine->ast_percent = $playerData['ast_percent'];
						$boxScoreLine->stl_percent = $playerData['stl_percent'];
						$boxScoreLine->blk_percent = $playerData['blk_percent'];
						$boxScoreLine->tov_percent = $playerData['tov_percent'];
						$boxScoreLine->off_rating = $playerData['off_rating'];
						$boxScoreLine->def_rating = $playerData['def_rating'];
					}
				}

				# $boxScoreLine->save();
			}

			dd($rowContents);
		}
	}

	public function player_scraper() {
		$teamsAbbrBR = Team::all(['abbr_br'])->toArray();

		$client = new Client();

		foreach ($teamsAbbrBR as $array) {
			$players = Player::all();

			$crawlerBR = $client->request('GET', 'http://www.basketball-reference.com/teams/'.$array['abbr_br'].'/2014.html');

			$rowCount = $crawlerBR->filter('table#roster > tbody > tr')->count();

			for ($n=1; $n <= $rowCount; $n++) { // nth-child does not start with a zero index
				$name = $crawlerBR->filter('table#roster > tbody > tr:nth-child('.$n.') > td:nth-child(2)')->text();
				$name = trim($name);

				$duplicate = false;

				foreach ($players as $player) {
					if ($player->name == $name) {
						$duplicate = true;
					}
				}

				if ($duplicate === false) {
					$player = new Player;

					$player->name = $name;

					$player->save();						
				}
			}			
		}
	}

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
		$games = Game::whereRaw('type = "'.$gameType.'" and season_id = '.$season->id)->get();

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



				$twoTeams = [
					'home_team_id',
					'road_team_id'
				];

				foreach ($twoTeams as $row) {
					if ($rowContents[$i][$row] == 'New Orleans Hornets') {
							$rowContents[$i][$row] = 'New Orleans Pelicans';
					}

					$roadTeam = $rowContents[$i]['road_team_id']; // this is for SAO scraping

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
