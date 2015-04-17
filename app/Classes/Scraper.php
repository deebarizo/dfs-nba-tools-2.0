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

		ddAll($urls);
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