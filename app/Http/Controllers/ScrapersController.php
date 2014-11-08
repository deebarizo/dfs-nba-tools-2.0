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

		$boxScoreLines = DB::table('games')
            ->join('box_score_lines', 'games.id', '=', 'box_score_lines.game_id')
            ->select('*')
            ->where('games.season_id', '=', $season->id)
            ->groupBy('game_id')
            ->get();
        $boxScoreLinesCount = count($boxScoreLines);

	    dd($boxScoreLinesCount);


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

			if ($gamesCount == 0) {
				$scrapeGamesToggle = true;
			} elseif ($gamesCount == $rowCount) {
				$scrapeGamesToggle = false;
			} else {
				dd('Figure out how not to double save games.');
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
