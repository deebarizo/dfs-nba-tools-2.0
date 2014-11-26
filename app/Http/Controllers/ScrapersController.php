<?php namespace App\Http\Controllers;

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

use App\Season;
use App\Team;
use App\Game;
use App\Player;
use App\BoxScoreLine;
use App\PlayerPool;
use App\PlayerFd;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

class ScrapersController {

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

			$client = new Client();

			$gamesWithDataCount = 0;

			foreach ($games as $game) {
				$gamesWithDataCount++;

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
					# $boxScoreLine->plus_minus = $playerData['plus_minus'];
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

			foreach ($teams as $team) {
				if ($team->abbr_fd == $OppAbbrFD) {
					$rowContents[$i]['opp_team_id'] = $team->id;

					break;					
				}
			}

			if (isset($rowContents[$i]['opp_team_id']) === false) {
				$message = 'No team ID match for '.$OppAbbrFD.'.';
				Session::flash('alert', 'danger');

				return redirect('scrapers/fd_nba_salaries')->with('message', $message);	
			}			

			$rowContents[$i]['top_play_index'] = null;
		}	

		$playerPool = new PlayerPool;

		$playerPool->date = $metadata['date'];
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
