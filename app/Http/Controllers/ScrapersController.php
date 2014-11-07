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

			if (isset($rowContents[$i]['player_id']) === false) {
				$message = 'No player ID match for '.$name.'.';
				Session::flash('alert', 'danger');

				return redirect('scrapers/fd_nba_salaries')->with('message', $message);	

				# $player = new Player;
				# $player->name = $name;
				# $player->save();	
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

	public function box_score_line_scraper() {
		$games = Game::all();
		$teams = Team::all();
		$players = Player::all();

		$client = new Client();

		$savedGameCount = 0;

		foreach ($games as $index => $game) { 
			if ($game->id >= 2603) {
				$metadata = [];

				$crawlerBR = $client->request('GET', $game->link_br);

				$metadata['game_id'] = $game->id;

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

					// name change: Hornets Pelicans

					if ($abbrBR == 'NOP') {
						$abbrBR = 'NOH';
					}

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

				foreach ($rowContents as $location) {
					foreach ($location as $playerData) {
						$boxScoreLine = new BoxScoreLine;

						$boxScoreLine->game_id = $metadata['game_id'];

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

						$boxScoreLine->save();
					}
				}

				$savedGameCount++;

				echo 'Game ID: '.$game->id.'<br>';
				echo 'Saved Game Count: '.$savedGameCount.'<br><br>';

				unset($rowContents);

				if ($savedGameCount >= 75) {
					echo 'The box score lines of '.$savedGameCount.' games were saved.';
					exit();
				}				
			}	
		}
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
