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

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

class OneOfController {

	public function matchMlbPlayersToTeams() {
		
		if (($handle = fopen('files/dk/mlb/all-day/2015-04-06.csv', 'r')) !== false) {
			$row = 0;

			while (($csvData = fgetcsv($handle, 5000, ',')) !== false) {
			    if ($row != 0) {
			    	$time = preg_replace("/(\w+@\w+\s)(\d\d:\d\d\w\w)(\s.+)/", "$2", $csvData[3]);
			    	$time = date('g:i A', strtotime('-1 hour', strtotime($time)));
			    	
				    $player[$row] = array(
				       	'position' => $csvData[0],
				       	'name' => $csvData[1],
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

				    	$mlbPlayer->save();
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

				    prf($player[$row]);
				}

				$row++;
			}
		}	

	}

}