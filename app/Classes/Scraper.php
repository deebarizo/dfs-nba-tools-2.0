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

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

class Scraper {

	public function getCsvFile($request, $site, $sport) {
		$timePeriodInUrl = strtolower($request->input('time_period'));
		$timePeriodInUrl = preg_replace('/\s/', '-', $timePeriodInUrl);

		$csvDirectory = 'files/'.strtolower($site).'/'.strtolower($sport).'/'.$timePeriodInUrl.'/';
		$csvName = $request->input('date').'.csv';
		$csvFile = $csvDirectory.$csvName;

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
			return true;
		}

		$playerPool = new PlayerPool;

		$playerPool->date = $date;
		$playerPool->sport = $sport;
		$playerPool->time_period = $timePeriod;
		$playerPool->site = $site;
		$playerPool->url = $url;

		$playerPool->save();

		return false;
	}

	public function parseCsvFile($request, $csvFile, $site, $sport) {
		if ($site == 'DK' && $sport == 'MLB') {
			$this->parseCsvFileDkMlb($request, $csvFile);

			return;
		}
	}

	private function parseCsvFileDkMlb($request, $csvFile) {
		if (($handle = fopen($csvFile, 'r')) !== false) {
			$row = 0;

			while (($csvData = fgetcsv($handle, 5000, ',')) !== false) {
				if ($row != 0) {
			    	$time = preg_replace("/(\w+@\w+\s)(\d\d:\d\d\w\w)(\s.+)/", "$2", $csvData[3]);
			    	$time = date('g:i A', strtotime('-1 hour', strtotime($time)));
			    	
				    $player[$row] = array(
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
				    	$player[$row]['name_espn'] = $mlbPlayer = MlbPlayer::where('name', $player[$row]['name'])->pluck('name_espn');
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
				}

				$row++;
			}
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