<?php namespace App\Http\Controllers;

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

use App\Models\Season;
use App\Models\Team;
use App\Models\Game;
use App\Models\Player;
use App\Models\BoxScoreLine;
use App\Models\PlayerPool;
use App\Models\PlayerFd;
use App\Models\MlbPlayer;
use App\Models\MlbTeam;
use App\Models\MlbPlayerTeam;

use App\Classes\Scraper;
use App\Classes\Validator;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

class ScrapersController {

	public function dkMlbOwnership(Request $request) {
		$date = $request->input('date');
		$contestName = $request->input('contest');
		$entryFee = $request->input('entry_fee');
		$timePeriod = $request->input('time_period');
		
		$validator = new Validator;
		$message = $validator->validateDkMlbOwnership($contestName, $entryFee, $timePeriod);

		if ($message != 'Valid') {
			Session::flash('alert', 'warning');

			return redirect('scrapers/dk_mlb_ownership')->with('message', $message);	 
		}

		$scraper = new Scraper;

		$csvFile = $scraper->uploadContestCsvFile($request, $date, $timePeriod, 'DK', 'MLB');

		$scraper->insertContest($date, $contestName, $entryFee, $timePeriod, $csvFile, 'DK', 'MLB');

		// check if contest already exists

		$message = 'Success!';
		Session::flash('alert', 'info');

		return redirect('scrapers/fg_mlb_box_score_lines')->with('message', $message);	 
	}

	public function dk_mlb_salaries(Request $request) {
		$scraper = new Scraper;

		$csvFile = $scraper->getCsvFile($request, 'DK', 'MLB');

		list($playerPoolExists, $playerPoolId) = $scraper->insertDataToPlayerPoolsTable($request, 'DK', 'MLB', 'csv file');

		if ($playerPoolExists) {
			$message = 'This player pool is already in the database.';
			Session::flash('alert', 'info');

			return redirect('scrapers/dk_mlb_salaries')->with('message', $message);				
		}

		$scraper->parseCsvFile($request, $csvFile, 'DK', 'MLB', $playerPoolId);

		$message = 'Success!';
		Session::flash('alert', 'info');

		return redirect('scrapers/dk_mlb_salaries')->with('message', $message);	 
	}

	public function bat_mlb_projections(Request $request) {
		$scraper = new Scraper;

		$scraper->getBatCsvFile($request, 'DK', 'MLB');

		$message = 'Success!';
		Session::flash('alert', 'info');

		return redirect('scrapers/bat_mlb_projections')->with('message', $message);	 
	}

	public function fg_mlb_box_score_lines(Request $request) {
		$scraper = new Scraper;
		$date = $request->input('date');

		$scraper->insertGames($date, 'DK', 'MLB');

		$message = 'Success!';
		Session::flash('alert', 'info');

		return redirect('scrapers/fg_mlb_box_score_lines')->with('message', $message);	 
	}

	public function br_nba_box_score_lines(Request $request) {
		$endYear = $request->input('season');
		$season = Season::where('end_year', $endYear)->first();

		$gamesWithBoxScoreLines = DB::table('games')
            ->join('box_score_lines', 'games.id', '=', 'box_score_lines.game_id')
            ->select('*')
            ->where('games.season_id', '=', $season->id)
            ->groupBy('game_id')
            ->get();
        $gamesWithBoxScoreLinesCount = count($gamesWithBoxScoreLines);

        $gamesCount = Game::where('season_id', '=', $season->id)->count();

        $unscrapedGamesCount = $gamesCount - $gamesWithBoxScoreLinesCount;

	    if ($unscrapedGamesCount > 0) {
	    	$indexStart = $gamesCount - $unscrapedGamesCount;

	    	$games = DB::table('games')->skip($indexStart)->take($unscrapedGamesCount)->where('games.season_id', $season->id)->get();

			$players = Player::all();
			$teams = Team::all();

			set_time_limit(0);

			$client = new Client();

			$gamesWithDataCount = 0;

			foreach ($games as $game) {
				$gamesWithDataCount++;

				$crawlerBR = $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_TIMEOUT, 50000);
				$crawlerBR = $client->request('GET', $game->link_br);

				$twoTeamsID = [
					'home_team' => 'home_team_id',
					'road_team' => 'road_team_id'
				];	

				foreach ($twoTeamsID as $location => $teamID) {
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

					$advStats[7] = 'orb_percent';
					$advStats[8] = 'drb_percent';
					$advStats[9] = 'trb_percent';
					$advStats[10] = 'ast_percent';
					$advStats[11] = 'stl_percent';
					$advStats[12] = 'blk_percent';
					$advStats[13] = 'tov_percent';
					$advStats[14] = 'usg';
					$advStats[15] = 'off_rating';
					$advStats[16] = 'def_rating';

					// Starters

					for ($i=1; $i <= 5; $i++) { 
						$rowContents[$location][$i]['role'] = 'starter';

						$rowContents = scrapeBoxLineScoreBR($rowContents, $players, $game, $location, $teamID, $crawlerBR, $abbrBR, $i, $basicStats, $advStats);
					}

					// Reserves

					$rowCount = $crawlerBR->filter('table#'.$abbrBR.'_basic > tbody > tr')->count();

					for ($i=7; $i <= $rowCount; $i++) { 
						$rowContents[$location][$i]['role'] = 'reserve';
						
						$rowContents = scrapeBoxLineScoreBR($rowContents, $players, $game, $location, $teamID, $crawlerBR, $abbrBR, $i, $basicStats, $advStats);
					}
				}

				foreach ($rowContents as $location => &$row) {
					foreach ($row as &$playerData) {
						$playerData['game_id'] = $game->id;
					}
				}

				unset($row);
				unset($playerData);

				$dataToSave[] = $rowContents;

				unset($rowContents);

				if ($gamesWithDataCount == 20) {
					break;
				} 
			}
	    } else {
			$message = 'All the box score lines have been scraped.';
			Session::flash('alert', 'info');

			return redirect('scrapers/br_nba_box_score_lines')->with('message', $message);	    	
	    }

	    unset($game);
	    unset($team);

	    foreach ($dataToSave as &$game) {
	    	foreach ($game as $location => &$team) {
	    		foreach ($team as &$playerData) {
	    			if (isset($playerData['player_id']) === false) {
						$player = new Player;

						$player->name = $playerData['name'];

						$player->save();	

						$playerData['player_id'] = $player->id;
					}
	    		}
	    	}
	    }

	    unset($game);
	    unset($team);
	    unset($playerData);

	    foreach ($dataToSave as $game) {
	    	foreach ($game as $location => $team) {
	    		foreach ($team as $playerData) {
					$boxScoreLine = new BoxScoreLine;

					$boxScoreLine->game_id = $playerData['game_id'];

					$boxScoreLine->team_id = $playerData['team_id'];
					$boxScoreLine->player_id = $playerData['player_id'];
					$boxScoreLine->role = $playerData['role'];
					$boxScoreLine->status = $playerData['status'];
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
					$boxScoreLine->orb_percent = $playerData['orb_percent'];
					$boxScoreLine->drb_percent = $playerData['drb_percent'];
					$boxScoreLine->trb_percent = $playerData['trb_percent'];
					$boxScoreLine->ast_percent = $playerData['ast_percent'];
					$boxScoreLine->stl_percent = $playerData['stl_percent'];
					$boxScoreLine->blk_percent = $playerData['blk_percent'];
					$boxScoreLine->tov_percent = $playerData['tov_percent'];
					$boxScoreLine->usg = $playerData['usg'];
					$boxScoreLine->off_rating = $playerData['off_rating'];
					$boxScoreLine->def_rating = $playerData['def_rating'];

					$boxScoreLine->save();
	    		}
	    	}
	    }

	    $noOppTeamIdBoxScoreLines = BoxScoreLine::where('opp_team_id', '=', 0)->get()->toArray();

	    foreach ($noOppTeamIdBoxScoreLines as $noOppTeamIdBoxScoreLine) {
            $boxScoreLineWithOppTeam = DB::table('box_score_lines')
                                            ->where('game_id', '=', $noOppTeamIdBoxScoreLine['game_id'])
                                            ->where('team_id', '!=', $noOppTeamIdBoxScoreLine['team_id'])
                                            ->first();

            $oppTeamId = $boxScoreLineWithOppTeam->team_id;

            DB::table('box_score_lines')->where('game_id', '=', $noOppTeamIdBoxScoreLine['game_id'])
                                        ->where('team_id', '!=', $oppTeamId)
                                        ->update(array('opp_team_id' => $oppTeamId));
	    }

		$message = 'Success! The box score lines of '.$gamesWithDataCount.' games were scraped and saved.';
		Session::flash('alert', 'info');

		return redirect('scrapers/br_nba_box_score_lines')->with('message', $message);
	}

	public function br_nba_games(Request $request) {
		$endYear = $request->input('season');
		$gameType = $request->input('game_type');

		switch ($gameType) {
			case 'regular':
				$tableIDinBR = 'games';
				break;
			
			case 'playoffs':
				$tableIDinBR = 'games_playoffs';
				break;
		}

		$teams = Team::all();

		$season = Season::where('end_year', $endYear)->first();
		$gamesCount = Game::where('season_id', '=', $season->id)->count();

		$client = new Client();
		$crawler = $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_TIMEOUT, 50000);
		$crawler = $client->request('GET', 'http://www.basketball-reference.com/leagues/NBA_'.$endYear.'_games.html');

		$status_code = $client->getResponse()->getStatus();

		if ($status_code == 200) {
			$rowCount = $crawler->filter('table#'.$tableIDinBR.' > tbody > tr > td:nth-child(2) > a')->count();

			if ($gamesCount == $rowCount) {
				$scrapeGamesToggle = false;
			} else {
				$scrapeGamesToggle = true;
			}

			if ($scrapeGamesToggle === true) {
				$rowContents = scrapeForGamesTable($client, $crawler, $tableIDinBR, $teams, $season->id, $gamesCount, $rowCount);

				foreach ($rowContents as $row) {
					$doesThisGameExist = Game::where('link_br', '=', $row['link_br'])->count();

					if ($doesThisGameExist == 0) {
						$game = new Game;

						$game->season_id = $season->id;
						$game->date = $row['date'];
						$game->link_br = $row['link_br'];
						$game->home_team_id = $row['home_team_id'];
						$game->home_team_score = $row['home_team_score'];
						$game->vegas_home_team_score = $row['vegas_home_team_score'];
						$game->road_team_id = $row['road_team_id'];
						$game->road_team_score = $row['road_team_score'];
						$game->vegas_road_team_score = $row['vegas_road_team_score'];
						$game->pace = $row['pace'];
						$game->type = $gameType;
						$game->ot_periods = $row['ot_periods'];
						$game->notes = $row['notes'];

						$game->save();						
					}
				}

				unset($row);

				$message = 'Success!';
				Session::flash('alert', 'info');

				return redirect('scrapers/br_nba_games')->with('message', $message);
			} else {
				$message = 'All the games have been scraped.';
				Session::flash('alert', 'info');

				return redirect('scrapers/br_nba_games')->with('message', $message);
			}
		} else {
			$message = 'The Basketball Reference page is not loading.';
			Session::flash('alert', 'danger');

			return redirect('scrapers/br_nba_games')->with('message', $message);
		}
	}

	public function fd_nba_salaries(RunFDNBASalariesScraperRequest $request) {
		$metadata['date'] = $request->input('date');
		$metadata['time_period'] = $request->input('time_period');
		$metadata['site'] = 'FD';
		$metadata['url'] = $request->input('url');

		$dupCheck = PlayerPool::whereRaw('date = "'.$metadata['date'].'" and time_period = "'.$metadata['time_period'].'" and site = "FD"')->first();

		if ($dupCheck !== null) {
			$message = 'This player pool has already been scraped and saved.';
			Session::flash('alert', 'info');

			return redirect('scrapers/fd_nba_salaries')->with('message', $message);			
		}

		$players = Player::all();
		$teams = Team::all();

		$client = new Client();
		$crawlerFD = $client->request('GET', $metadata['url']);		

		$rowCount = $crawlerFD->filter('table.player-list-table > tbody > tr')->count();

		for ($i = 1; $i <= $rowCount; $i++) { // nth-child does not start with a zero index
			$rowContents[$i]['position'] = $crawlerFD->filter('table.player-list-table > tbody > tr:nth-child('.$i.') > td.player-position')->text();
			$rawName = $crawlerFD->filter('table.player-list-table > tbody > tr:nth-child('.$i.') > td.player-name')->text();

			$rawName = preg_replace("/(O)$/", "", $rawName);
			$rawName = preg_replace("/(GTD)$/", "", $rawName);
			$name = fd_name_fix($rawName);

			foreach ($players as $player) {
				if ($player->name == $name) {
					$rowContents[$i]['player_id'] = $player->id;

					break;
				}
			}

			unset($player);

			if (isset($rowContents[$i]['player_id']) === false) {
				$player = new Player;

				$player->name = $name;

				$player->save();	

				$rowContents[$i]['player_id'] = $player->id;
			}

			$rawSalary = $crawlerFD->filter('table.player-list-table > tbody > tr:nth-child('.$i.') > td.player-salary')->text();

			$rawSalary = preg_replace("/\\$/", "", $rawSalary);
			$rowContents[$i]['salary'] = preg_replace("/,/", "", $rawSalary);			

			$abbrFD = $crawlerFD->filter('table.player-list-table > tbody > tr:nth-child('.$i.') > td.player-fixture > b')->text();

			foreach ($teams as $team) {
				if ($team->abbr_fd == $abbrFD) {
					$rowContents[$i]['team_id'] = $team->id;

					break;					
				}
			}

			if (isset($rowContents[$i]['team_id']) === false) {
				$message = 'No team ID match for '.$abbrFD.'.';
				Session::flash('alert', 'danger');

				return redirect('scrapers/fd_nba_salaries')->with('message', $message);	
			}			

			$rawOppAbbrFD = $crawlerFD->filter('table.player-list-table > tbody > tr:nth-child('.$i.') > td.player-fixture')->text();

			$rawOppAbbrFD = preg_replace("/@/", "", $rawOppAbbrFD);
			$OppAbbrFD = preg_replace("/".$abbrFD."/", "", $rawOppAbbrFD);

			if ($OppAbbrFD == 'C') {
				$OppAbbrFD = 'SAC';
			}

			foreach ($teams as $team) {
				if ($team->abbr_fd == $OppAbbrFD) {
					$rowContents[$i]['opp_team_id'] = $team->id;

					break;					
				}
			}

	/*		if (isset($rowContents[$i]['opp_team_id']) === false) {
				$message = 'No team ID match for '.$OppAbbrFD.'.';
				Session::flash('alert', 'danger');

				return redirect('scrapers/fd_nba_salaries')->with('message', $message);	
			}			*/

			$rowContents[$i]['top_play_index'] = null;
		}	

		$playerPool = new PlayerPool;

		$playerPool->date = $metadata['date'];
		$playerPool->sport = 'NBA';
		$playerPool->time_period = $metadata['time_period'];
		$playerPool->site = $metadata['site'];
		$playerPool->url = $metadata['url'];

		$playerPool->save();

		foreach ($rowContents as $row) {
			$playerFD = new PlayerFd;

			$playerFD->player_id = $row['player_id'];
			$playerFD->position = $row['position'];
			$playerFD->salary = $row['salary'];
			$playerFD->team_id = $row['team_id'];
			$playerFD->opp_team_id = $row['opp_team_id'];
			$playerFD->top_play_index = $row['top_play_index'];
			$playerFD->player_pool_id = $playerPool->id;

			$playerFD->save();
		}

		$message = 'Success!';
		Session::flash('alert', 'info');

		return redirect('scrapers/fd_nba_salaries')->with('message', $message);	 
	}

	public function player_scraper() {
		$teamsAbbrBR = Team::all(['abbr_br'])->toArray();

		$client = new Client();

		foreach ($teamsAbbrBR as $array) {
			$players = Player::all();

			$crawlerBR = $client->request('GET', 'http://www.basketball-reference.com/teams/'.$array['abbr_br'].'/2013.html');

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

}
