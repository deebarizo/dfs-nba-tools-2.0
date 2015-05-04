<?php namespace App\Classes;

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
use App\Models\DkMlbPlayer;
use App\Models\MlbGame;
use App\Models\MlbGameLine;
use App\Models\MlbBoxScoreLine;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

class Scraper {

	/****************************************************************************************
	MLB
	****************************************************************************************/

	public function insertContest($date, $contestName, $entryFee, $timePeriod, $csvFile, $site, $sport) {
		$contest = [];

		$contest['date'] = $date;
		$contest['name'] = $contestName;
		$contest['entry_fee'] = $entryFee;
		$contest['time_period'] = $timePeriod;
		$contest['lineups'] = [];

		$dkMlbPlayers = DB::table('player_pools')
							->select('dk_mlb_players.id as dk_mlb_player_id',
									 'mlb_players.id as mlb_player_id', 
									 'mlb_players.name',  
									 'dk_mlb_players.position')
							->join('dk_mlb_players', 'dk_mlb_players.player_pool_id', '=', 'player_pools.id')
							->join('mlb_players', 'mlb_players.id', '=', 'dk_mlb_players.mlb_player_id')
							->where('player_pools.date', $date)
							->where('player_pools.time_period', $timePeriod)
							->where('player_pools.site', $site)
							->where('player_pools.sport', $sport)
							->get();

		foreach ($dkMlbPlayers as $dkMlbPlayer) {
			if ($dkMlbPlayer->position == 'SP' || $dkMlbPlayer->position == 'RP') {
				$dkMlbPlayer->position = 'P';
			}
		}

		# dd($dkMlbPlayers);

		if ($site == 'DK' && $sport == 'MLB') {
			if (($handle = fopen($csvFile, 'r')) !== false) {
				$row = 0;

				while (($csvData = fgetcsv($handle, 10000000, ',')) !== false) {
					if ($row != 0) {
					    $lineup = explode(',', $csvData[5]);

					    foreach ($lineup as $key => $rosterSpot) {
					    	$position = preg_replace('/(\()(\w+)(\).*)/', '$2', $rosterSpot);
					    	$playerName = trim(preg_replace('/(\(\w+\)\s)(.*)/', '$2', $rosterSpot));
					    	list($dkMlbPlayerId, $mlbPlayerId) = $this->getDkMlbPlayer($dkMlbPlayers, $position, $playerName);

					    	$contest['lineups'][$row][$key] = array(
					    		'dk_mlb_player_id' => $dkMlbPlayerId,
					    		'mlb_player_id' => $mlbPlayerId, 
					    		'player_name' => $playerName,
					    		'position' => $position
					    	);
					    }
					}

					$row++;
				}
			}
		}

		$numOfLineups = count($contest['lineups']);

		ddAll($contest);
	}

	private function getDkMlbPlayer($dkMlbPlayers, $position, $playerName) {
		foreach ($dkMlbPlayers as $dkMlbPlayer) {
			if ($position == $dkMlbPlayer->position && $playerName == $dkMlbPlayer->name) {
				return array($dkMlbPlayer->dk_mlb_player_id, $dkMlbPlayer->mlb_player_id);
			}
		}

		echo 'There was no match for '.$playerName.' in the player pool.';
		exit();
	}

	public function uploadContestCsvFile($request, $date, $timePeriod, $site, $sport) {
		$csvName = $request->file('csv')->getClientOriginalName();
		$contestId = preg_replace('/(\D*)(\d+)(.csv)/', '$2', $csvName);

		$timePeriodInUrl = strtolower($timePeriod);
		$timePeriodInUrl = preg_replace('/\s/', '-', $timePeriodInUrl);

		$csvDirectory = 'files/'.strtolower($site).'/'.strtolower($sport).'/'.$timePeriodInUrl.'/';
		$csvName = $date.'-'.$contestId.'.csv';
		$csvFile = $csvDirectory . $csvName;
 
		Input::file('csv')->move($csvDirectory, $csvName);

		return $csvFile;
	}

	public function getBatCsvFile($request, $site, $sport) {
		$playerTypes = ['hitters', 'pitchers'];

		foreach ($playerTypes as $playerType) {
			$csvDirectory = 'files/'.strtolower($site).'/'.strtolower($sport).'/bat/'.$playerType.'/';
			$csvName = $request->input('date').'.csv';
			$csvFile = $csvDirectory . $csvName;

			Input::file('csv_'.$playerType)->move($csvDirectory, $csvName);			
		}
	}

	public function insertGames($date, $site, $sport) {
		$client = new Client();

		$crawler = $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_TIMEOUT, 100000);
		$crawler = $client->request('GET', 'http://www.fangraphs.com/scoreboard.aspx?date='.$date);

		$numOfLinks = $crawler->filter('td > a')->count();

		$urls = [];

		for ($i = 0; $i < $numOfLinks; $i++) { 
			$link = $crawler->filter('td > a')->eq($i);

			$anchorText = $link->text();

			if ($anchorText == 'Box Score') {
				$urls[] = $link->link()->getUri();
			}
		}

		$seasonId = $this->getSeasonId($date, $sport);

		$games = [];

		# ddAll($urls);

		foreach ($urls as $key => $url) {
			$games[$key]['season_id'] = $seasonId;
			$games[$key]['date'] = $date;
			$games[$key]['link_fg'] = $url;

			list($games[$key]['game_lines'], $games[$key]['box_score_lines']) = $this->scrapeGame($url, $sport);
		}

		# ddAll($games);

		foreach ($games as $game) {
			$this->saveGame($sport, $date, $game);
		}
	}

	private function saveGame($sport, $date, $game) {
		if ($sport == 'MLB') {
			$gameExists = MlbGame::where('link_fg', $game['link_fg'])->count();

			if (!$gameExists) {
				$mlbGame = new MlbGame;

				$mlbGame->season_id = $game['season_id'];
				$mlbGame->date = $date;
				$mlbGame->link_fg = $game['link_fg'];

				$mlbGame->save();			
			} else {
				echo 'The game with this link '.$game['link_fg'].' is already in the database.';
				exit();
			}

			foreach ($game['game_lines'] as $gameLine) {
				$this->saveGameLine($sport, $gameLine, $mlbGame->id);
			}

			foreach ($game['box_score_lines'] as $boxScoreLine) {
				$this->saveBoxScoreLine($sport, $boxScoreLine, $mlbGame->id);
			}
		}
	}

	private function saveBoxScoreLine($sport, $boxScoreLine, $gameId) {
		if ($sport == 'MLB') {
			$mlbBoxScoreLine = new MlbBoxScoreLine;

			$mlbBoxScoreLine->mlb_game_id = $gameId;
			$mlbBoxScoreLine->mlb_team_id = $boxScoreLine['mlb_team_id'];
			$mlbBoxScoreLine->opp_mlb_team_id = $boxScoreLine['opp_mlb_team_id'];
			$mlbBoxScoreLine->mlb_player_id = $boxScoreLine['mlb_player_id'];
			
			$mlbBoxScoreLine->pa = $boxScoreLine['pa'];
			$mlbBoxScoreLine->singles = $boxScoreLine['singles'];
			$mlbBoxScoreLine->doubles = $boxScoreLine['doubles'];
			$mlbBoxScoreLine->triples = $boxScoreLine['triples'];
			$mlbBoxScoreLine->hr = $boxScoreLine['hr'];
			$mlbBoxScoreLine->rbi = $boxScoreLine['rbi'];
			$mlbBoxScoreLine->runs = $boxScoreLine['runs'];
			$mlbBoxScoreLine->bb = $boxScoreLine['bb'];
			$mlbBoxScoreLine->ibb = $boxScoreLine['ibb'];
			$mlbBoxScoreLine->hbp = $boxScoreLine['hbp'];
			$mlbBoxScoreLine->sf = $boxScoreLine['sf'];
			$mlbBoxScoreLine->sh = $boxScoreLine['sh'];
			$mlbBoxScoreLine->gdp = $boxScoreLine['gdp'];
			$mlbBoxScoreLine->sb = $boxScoreLine['sb'];
			$mlbBoxScoreLine->cs = $boxScoreLine['cs'];

			$mlbBoxScoreLine->ip = $boxScoreLine['ip'];
			$mlbBoxScoreLine->so = $boxScoreLine['so'];
			$mlbBoxScoreLine->win = $boxScoreLine['win'];
			$mlbBoxScoreLine->er = $boxScoreLine['er'];
			$mlbBoxScoreLine->runs_against = $boxScoreLine['runs_against'];
			$mlbBoxScoreLine->hits_against = $boxScoreLine['hits_against'];
			$mlbBoxScoreLine->bb_against = $boxScoreLine['bb_against'];
			$mlbBoxScoreLine->ibb_against = $boxScoreLine['ibb_against'];
			$mlbBoxScoreLine->hbp_against = $boxScoreLine['hbp_against'];
			$mlbBoxScoreLine->cg = $boxScoreLine['cg'];
			$mlbBoxScoreLine->cg_shutout = $boxScoreLine['cg_shutout'];
			$mlbBoxScoreLine->no_hitter = $boxScoreLine['no_hitter'];
			$mlbBoxScoreLine->fpts = $boxScoreLine['fpts'];

			$mlbBoxScoreLine->save();
		}
	}

	private function saveGameLine($sport, $gameLine, $gameId) {
		if ($sport == 'MLB') {
			$mlbGameLine = new MlbGameLine;

			$mlbGameLine->mlb_game_id = $gameId;
			$mlbGameLine->home = $gameLine['home'];
			$mlbGameLine->road = $gameLine['road'];
			$mlbGameLine->mlb_team_id = $gameLine['mlb_team_id'];
			$mlbGameLine->score = $gameLine['score'];

			$mlbGameLine->save();
		}
	}

	private function scrapeGame($url, $sport) {
		$client = new Client;

		$crawler = $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_TIMEOUT, 100000);
		$crawler = $client->request('GET', $url);

		$locations = ['home', 'away'];

		$gameLines = [];

		$boxScoreLines = [];
		$boxScoreLineCount = 0;

		foreach ($locations as $key => $location) {
			if ($location == 'home') {
				$gameLines[$key]['home'] = 1;
				$gameLines[$key]['road'] = 0;

				$cssId = 'h';

				$otherLocation = 'away';
			} else {
				$gameLines[$key]['home'] = 0;
				$gameLines[$key]['road'] = 1;

				$cssId = 'a';

				$otherLocation = 'home';
			}

			$teamFg = $crawler->filter('a[href="#'.$location.'"]')->text();
			$teamId = $this->getTeamId($teamFg, $sport);
			$gameLines[$key]['mlb_team_id'] = $teamId;

			$oppTeamFg = $crawler->filter('a[href="#'.$otherLocation.'"]')->text();
			$oppTeamId = $this->getTeamId($oppTeamFg, $sport);

			$hitterCount = $crawler->filter('table#WinsBox1_dg2'.$cssId.'b_ctl00 > tbody > tr')->count() - 1; // minus to take out total row (last row)

			$gameLines[$key]['score'] = $crawler->filter('tr#WinsBox1_dg'.$cssId.'b_ctl00__'.$hitterCount.' > td')->eq(5)->text();

			for ($i = 0; $i < $hitterCount; $i++) { 
				$playerRow = $crawler->filter('table#WinsBox1_dg2'.$cssId.'b_ctl00 > tbody > tr')->eq($i);

				$playerAndPosition = $playerRow->filter('td')->eq(0)->text();
				
				if (substr($playerAndPosition, -1) != 'P') { // if player is not a pitcher
					$boxScoreLineCount++;

					$boxScoreLines[$boxScoreLineCount]['mlb_team_id'] = $teamId;
					$boxScoreLines[$boxScoreLineCount]['opp_mlb_team_id'] = $oppTeamId;

					$playerNameFg = $playerRow->filter('td')->eq(0)->filter('a')->text();

					$boxScoreLines[$boxScoreLineCount]['mlb_player_id'] = $this->getPlayerId($playerNameFg, $sport);

					$boxScoreLines[$boxScoreLineCount]['pa'] = $playerRow->filter('td')->eq(2)->text();
					$boxScoreLines[$boxScoreLineCount]['singles'] = $playerRow->filter('td')->eq(4)->text();
					$boxScoreLines[$boxScoreLineCount]['doubles'] = $playerRow->filter('td')->eq(5)->text();
					$boxScoreLines[$boxScoreLineCount]['triples'] = $playerRow->filter('td')->eq(6)->text(); 
					$boxScoreLines[$boxScoreLineCount]['hr'] = $playerRow->filter('td')->eq(7)->text();
					$boxScoreLines[$boxScoreLineCount]['rbi'] = $playerRow->filter('td')->eq(9)->text();
					$boxScoreLines[$boxScoreLineCount]['runs'] = $playerRow->filter('td')->eq(8)->text();
					$boxScoreLines[$boxScoreLineCount]['bb'] = $playerRow->filter('td')->eq(10)->text();
					$boxScoreLines[$boxScoreLineCount]['ibb'] = $playerRow->filter('td')->eq(11)->text();
					$boxScoreLines[$boxScoreLineCount]['hbp'] = $playerRow->filter('td')->eq(13)->text();
					$boxScoreLines[$boxScoreLineCount]['sf'] = $playerRow->filter('td')->eq(14)->text();
					$boxScoreLines[$boxScoreLineCount]['sh'] = $playerRow->filter('td')->eq(15)->text();
					$boxScoreLines[$boxScoreLineCount]['gdp'] = $playerRow->filter('td')->eq(16)->text();
					$boxScoreLines[$boxScoreLineCount]['sb'] = $playerRow->filter('td')->eq(17)->text();
					$boxScoreLines[$boxScoreLineCount]['cs'] = $playerRow->filter('td')->eq(18)->text();

					$boxScoreLines[$boxScoreLineCount]['ip'] = 0;
					$boxScoreLines[$boxScoreLineCount]['so'] = 0;
					$boxScoreLines[$boxScoreLineCount]['win'] = 0;
					$boxScoreLines[$boxScoreLineCount]['er'] = 0;
					$boxScoreLines[$boxScoreLineCount]['runs_against'] = 0;
					$boxScoreLines[$boxScoreLineCount]['hits_against'] = 0;
					$boxScoreLines[$boxScoreLineCount]['bb_against'] = 0;
					$boxScoreLines[$boxScoreLineCount]['ibb_against'] = 0;
					$boxScoreLines[$boxScoreLineCount]['hbp_against'] = 0;
					$boxScoreLines[$boxScoreLineCount]['cg'] = 0;
					$boxScoreLines[$boxScoreLineCount]['cg_shutout'] = 0;
					$boxScoreLines[$boxScoreLineCount]['no_hitter'] = 0;

					$boxScoreLines[$boxScoreLineCount]['fpts'] = ($boxScoreLines[$boxScoreLineCount]['singles'] * 3) + 
												 ($boxScoreLines[$boxScoreLineCount]['doubles'] * 5) + 
												 ($boxScoreLines[$boxScoreLineCount]['triples'] * 8) + 
												 ($boxScoreLines[$boxScoreLineCount]['hr'] * 10) + 
												 ($boxScoreLines[$boxScoreLineCount]['rbi'] * 2) + 
												 ($boxScoreLines[$boxScoreLineCount]['runs'] * 2) + 
												 ($boxScoreLines[$boxScoreLineCount]['bb'] * 2) + 
												 ($boxScoreLines[$boxScoreLineCount]['hbp'] * 2) + 
												 ($boxScoreLines[$boxScoreLineCount]['sb'] * 5) + 
												 ($boxScoreLines[$boxScoreLineCount]['cs'] * -2);
				}
			}

			$pitcherCount = $crawler->filter('table#WinsBox1_dg2'.$cssId.'p_ctl00 > tbody > tr')->count() - 1; // minus to take out total row (last row)

			for ($i = 0; $i < $pitcherCount; $i++) { 
				$playerRow = $crawler->filter('table#WinsBox1_dg2'.$cssId.'p_ctl00 > tbody > tr')->eq($i);

				$boxScoreLineCount++;

				$boxScoreLines[$boxScoreLineCount]['mlb_team_id'] = $teamId;
				$boxScoreLines[$boxScoreLineCount]['opp_mlb_team_id'] = $oppTeamId;

				$playerNameFg = $playerRow->filter('td')->eq(0)->filter('a')->text();

				$boxScoreLines[$boxScoreLineCount]['mlb_player_id'] = $this->getPlayerId($playerNameFg, $sport);

				$boxScoreLines[$boxScoreLineCount]['pa'] = 0;
				$boxScoreLines[$boxScoreLineCount]['singles'] = 0;
				$boxScoreLines[$boxScoreLineCount]['doubles'] = 0;
				$boxScoreLines[$boxScoreLineCount]['triples'] = 0;
				$boxScoreLines[$boxScoreLineCount]['hr'] = 0;
				$boxScoreLines[$boxScoreLineCount]['rbi'] = 0;
				$boxScoreLines[$boxScoreLineCount]['runs'] = 0;
				$boxScoreLines[$boxScoreLineCount]['bb'] = 0;
				$boxScoreLines[$boxScoreLineCount]['ibb'] = 0;
				$boxScoreLines[$boxScoreLineCount]['hbp'] = 0;
				$boxScoreLines[$boxScoreLineCount]['sf'] = 0;
				$boxScoreLines[$boxScoreLineCount]['sh'] = 0;
				$boxScoreLines[$boxScoreLineCount]['gdp'] = 0;
				$boxScoreLines[$boxScoreLineCount]['sb'] = 0;
				$boxScoreLines[$boxScoreLineCount]['cs'] = 0;

				$boxScoreLines[$boxScoreLineCount]['ip'] = $playerRow->filter('td')->eq(11)->text();
				$boxScoreLines[$boxScoreLineCount]['so'] = $playerRow->filter('td')->eq(22)->text();
				$boxScoreLines[$boxScoreLineCount]['win'] = $playerRow->filter('td')->eq(1)->text();
				$boxScoreLines[$boxScoreLineCount]['er'] = $playerRow->filter('td')->eq(15)->text();
				$boxScoreLines[$boxScoreLineCount]['runs_against'] = $playerRow->filter('td')->eq(14)->text();
				$boxScoreLines[$boxScoreLineCount]['hits_against'] = $playerRow->filter('td')->eq(13)->text();
				$boxScoreLines[$boxScoreLineCount]['bb_against'] = $playerRow->filter('td')->eq(17)->text();
				$boxScoreLines[$boxScoreLineCount]['ibb_against'] = $playerRow->filter('td')->eq(18)->text();
				$boxScoreLines[$boxScoreLineCount]['hbp_against'] = $playerRow->filter('td')->eq(19)->text();
				$boxScoreLines[$boxScoreLineCount]['cg'] = $playerRow->filter('td')->eq(6)->text();
				$boxScoreLines[$boxScoreLineCount]['cg_shutout'] = $playerRow->filter('td')->eq(7)->text();

				if ($boxScoreLines[$boxScoreLineCount]['cg'] == 1 && $boxScoreLines[$boxScoreLineCount]['hits_against'] == 0) {
					$boxScoreLines[$boxScoreLineCount]['no_hitter'] = 1;
				} else {
					$boxScoreLines[$boxScoreLineCount]['no_hitter'] = 0;
				}

				$ipWithCorrectDecimals = (intval($boxScoreLines[$boxScoreLineCount]['ip'])) + (substr($boxScoreLines[$boxScoreLineCount]['ip'], -1) / 3);

				$boxScoreLines[$boxScoreLineCount]['fpts'] = ($ipWithCorrectDecimals * 2.25) + 
															 ($boxScoreLines[$boxScoreLineCount]['so'] * 2) + 
															 ($boxScoreLines[$boxScoreLineCount]['win'] * 4) + 
															 ($boxScoreLines[$boxScoreLineCount]['er'] * -2) + 
															 ($boxScoreLines[$boxScoreLineCount]['hits_against'] * -0.6) + 
															 ($boxScoreLines[$boxScoreLineCount]['bb_against'] * -0.6) + 
															 ($boxScoreLines[$boxScoreLineCount]['hbp_against'] * -0.6) + 
															 ($boxScoreLines[$boxScoreLineCount]['cg'] * 2.5) + 
															 ($boxScoreLines[$boxScoreLineCount]['cg_shutout'] * 2.5) + 
															 ($boxScoreLines[$boxScoreLineCount]['no_hitter'] * 5); 
			}
		}

		return array($gameLines, $boxScoreLines);
	}

	private function getPlayerId($playerNameFg, $sport) {
		if ($sport == 'MLB') {
			$mlbPlayers = MlbPlayer::all();

			$playerName = fgNameFix($playerNameFg);

			foreach ($mlbPlayers as $mlbPlayer) {
				if ($mlbPlayer->name == $playerName) {
					return $mlbPlayer->id;
				}
			}

			echo 'Error: could not match this player name from Fangraphs, '.$playerNameFg;
			exit();
		}
	}

	private function getTeamId($teamFg, $sport) {
		if ($sport == 'MLB') {
			$mlbTeams =  MlbTeam::all();

			foreach ($mlbTeams as $mlbTeam) {
				if ($mlbTeam->name_fg == $teamFg) {
					return $mlbTeam->id;
				}
			}

			echo 'error: could not find Mlb Team Id for this Team FG, '.$teamFg; 
			exit();		
		}
	}

	private function getSeasonId($date, $sport) {
		$seasons = Season::all();

		$partsOfDate = explode('-', $date);

		$year = $partsOfDate[0];

		if ($sport == 'MLB') {
			foreach ($seasons as $season) {
				if ($year == $season->end_year) {
					return $season->id; // MLB starts and ends in the same year
				}
			}
		}
	}

	public function getCsvFile($request, $site, $sport) {
		$timePeriodInUrl = strtolower($request->input('time_period'));
		$timePeriodInUrl = preg_replace('/\s/', '-', $timePeriodInUrl);

		$csvDirectory = 'files/'.strtolower($site).'/'.strtolower($sport).'/'.$timePeriodInUrl.'/';
		$csvName = $request->input('date').'.csv';
		$csvFile = $csvDirectory . $csvName;
 
		Input::file('csv')->move($csvDirectory, $csvName);

		return $csvFile;
	}

	public function insertDataToPlayerPoolsTable($request, $site, $sport, $url) {
		$date = $request->input('date');
		
		$timePeriod = $request->input('time_period');

		$url = 'csv file';

		$playerPoolExists = PlayerPool::where('date', $date)
										 ->where('sport', $sport)
										 ->where('time_period', $timePeriod)
										 ->where('site', $site)
										 ->where('url', $url)
										 ->count();

		if ($playerPoolExists) {
			return array(true, 'Player pool already exists.');
		}

		$playerPool = new PlayerPool;

		$playerPool->date = $date;
		$playerPool->sport = $sport;
		$playerPool->time_period = $timePeriod;
		$playerPool->site = $site;
		$playerPool->url = $url;
		$playerPool->buy_in = 100;

		$playerPool->save();

		return array(false, $playerPool->id);
	}

	public function parseCsvFile($request, $csvFile, $site, $sport, $playerPoolId) {
		if ($site == 'DK' && $sport == 'MLB') {
			$this->parseCsvFileDkMlb($request, $csvFile, $playerPoolId);

			return;
		}
	}

	private function parseCsvFileDkMlb($request, $csvFile, $playerPoolId) {
		if (($handle = fopen($csvFile, 'r')) !== false) {
			$row = 0;

			while (($csvData = fgetcsv($handle, 5000, ',')) !== false) {
				if ($row != 0) {
			    	$time = preg_replace("/(\w+@\w+\s)(\d\d:\d\d\w\w)(\s.+)/", "$2", $csvData[3]);
			    	$time = date('g:i A', strtotime('-1 hour', strtotime($time)));
			    	
				    $player[$row] = array(
				    	'player_pool_id' => $playerPoolId,
				       	'position' => $csvData[0],
				       	'name' => $csvData[1],
				       	'name_espn' => $csvData[1],
				       	'salary' => $csvData[2],
				       	'game_info' => $csvData[3],
				       	'home_team_abbr_dk' => preg_replace("/(.+@)(\w+)(\s.+)/", "$2", $csvData[3]),
				       	'road_team_abbr_dk' => preg_replace("/(@.+)/", "", $csvData[3]),
				       	'time' => $time
				    );

				    $playerExists = MlbPlayer::where('name', $player[$row]['name'])->count();

				    if (!$playerExists) {
				    	$mlbPlayer = new MlbPlayer;

				    	$mlbPlayer->name = $player[$row]['name'];
				    	$mlbPlayer->name_espn = $player[$row]['name'];

				    	$mlbPlayer->save();
				    } elseif ($playerExists) {
				    	$player[$row]['name_espn'] = MlbPlayer::where('name', $player[$row]['name'])->pluck('name_espn');
				    }

				    $locations = ['home', 'road'];

				    foreach ($locations as $location) {
				    	$teamExists[$location] = MlbTeam::where('abbr_dk', $player[$row][$location.'_team_abbr_dk'])->count();

					    if (!$teamExists[$location]) {
					    	$mlbTeam = new MlbTeam;

					    	$mlbTeam->abbr_dk = $player[$row][$location.'_team_abbr_dk'];

					    	$mlbTeam->save();
					    }
				    }

				    $playerId = MlbPlayer::where('name', $player[$row]['name'])->pluck('id');

				    if (!$this->playerTeamExists($playerId, $request)) {
				    	$this->addPlayerTeam($locations, $player, $row, $playerId, $request);
				    }

				    $teamId = MlbPlayerTeam::where('mlb_player_id', $playerId)->where('end_date', '>', $request->input('date'))->pluck('mlb_team_id');

				    $oppTeamId = $this->getOppTeamId($teamId, 
				    								 $player[$row]['home_team_abbr_dk'], 
				    								 $player[$row]['road_team_abbr_dk'],
				    								 $player[$row]['name'],
				    								 $player[$row]['position']);

				    if (!is_int($teamId) || is_null($teamId)) {
				    	prf('The following player\'s team id is null');
					    
					    prf($player[$row]);		

					    $teamId = 30;	    	
				    }

				    if (!is_int($oppTeamId) || is_null($oppTeamId)) {
				    	prf('The following player\'s opp team id is null');
					    
					    prf($player[$row]);		

					    $oppTeamId = 30;	    	
				    }

				    $numOfTimesToSave = 1;

				    if (strpos($player[$row]['position'], '/')) {
				    	$numOfTimesToSave = 2;
				    }

				    for ($i = 1; $i <= $numOfTimesToSave; $i++) { 
				    	if ($numOfTimesToSave == 1) {
				    		$positionToSave = $player[$row]['position'];

				    		if ($positionToSave == 'RP') {
				    			$positionToSave = 'SP';
				    		}
				    	}

				    	if ($numOfTimesToSave == 2 && $i == 1) {
				    		$positionToSave = preg_replace('/(\w+)(\/)(\w+)/', '$1', $player[$row]['position']);
				    	}

				    	if ($numOfTimesToSave == 2 && $i == 2) {
				    		$positionToSave = preg_replace('/(\w+)(\/)(\w+)/', '$3', $player[$row]['position']);
				    	}

				    	$dkMlbPlayer = new DkMlbPlayer;

					    $dkMlbPlayer->player_pool_id = $playerPoolId;
					    $dkMlbPlayer->mlb_player_id = $playerId;
					    $dkMlbPlayer->target_percentage = 0;
					    $dkMlbPlayer->mlb_team_id = $teamId;
					    $dkMlbPlayer->mlb_opp_team_id = $oppTeamId;
					    $dkMlbPlayer->position = $positionToSave;
					    $dkMlbPlayer->salary = $player[$row]['salary'];

					    $dkMlbPlayer->save();
				    }
				}

				$row++;
			}
		}	
	}

	private function getOppTeamId($teamId, $homeTeamAbbrDk, $roadTeamAbbrDk, $name, $position) {
		$teamAbbrDk = MlbTeam::where('id', $teamId)->pluck('abbr_dk');

		if ($name == 'Jose Ramirez' && $position == 'SP') {
			$teamAbbrDk = 'NYY';
		}

		if ($teamAbbrDk == $homeTeamAbbrDk) {
			return MlbTeam::where('abbr_dk', $roadTeamAbbrDk)->pluck('id');
		}

		if ($teamAbbrDk == $roadTeamAbbrDk) {
			return MlbTeam::where('abbr_dk', $homeTeamAbbrDk)->pluck('id');
		}
	}

	private function playerTeamExists($playerId, $request) {
		return MlbPlayerTeam::where('mlb_player_id', $playerId)
				    								 ->where('end_date', '>=', $request->input('date'))
				    								 ->count();
	}

	private function addPlayerTeam($locations, $player, $row, $playerId, $request) {
    	foreach ($locations as $location) {
    		$mlbTeam = MlbTeam::where('abbr_dk', $player[$row][$location.'_team_abbr_dk'])->first()->toArray();

    		$client = new Client();

			$crawler = $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_TIMEOUT, 100000);
			$crawler = $client->request('GET', 'http://espn.go.com/mlb/team/roster/_/name/'.$mlbTeam['abbr_espn']);

			if ($this->playerTeamExists($playerId, $request)) {
				return;					
			}

			$crawler->filter('tr')->each(function ($node) use($player, $row, $playerId, $mlbTeam, $request) {
				if ($node->filter('td')->eq(1)->count()) {
					$espnPlayerName = $node->filter('td')->eq(1)->text();
					$espnPlayerName = preg_replace('/DL\d+/', '', $espnPlayerName);

					if ($espnPlayerName == $player[$row]['name']) {
						// Insert to mlb_players_teams

						$mlbPlayerTeam = new MlbPlayerTeam;

						$mlbPlayerTeam->mlb_player_id = $playerId;
						$mlbPlayerTeam->mlb_team_id = $mlbTeam['id'];
						$mlbPlayerTeam->start_date = $request->input('date');
						$mlbPlayerTeam->end_date = '3000-01-01';

						$mlbPlayerTeam->save();

						// Insert to mlb_players

						$mlbPlayer = MlbPlayer::where('name', $espnPlayerName)->first();

						$mlbPlayer->name_espn = $espnPlayerName;

						$mlbPlayer->save();

						return;
					}
				}
			});
    	}

		if ($this->playerTeamExists($playerId, $request)) {
			return;					
		}

		echo 'This player is not matched with a team:</br></br>';

    	prf($player[$row]);

    	return;
	}

}