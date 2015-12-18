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
use App\Models\DkMlbContest;
use App\Models\DkMlbContestLineup;
use App\Models\DkMlbContestLineupPlayer;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

class Validator {

	/****************************************************************************************
	VALIDATE CSV FILE
	****************************************************************************************/	

	public function validateCsvFile($request, $csvFile, $site, $sport) {
		if ($site == 'FD' && $sport == 'NBA') {

			$message = $this->validateCsvFileFdNba($request, $csvFile);

			return $message;
		}

		if ($site == 'DK' && $sport == 'NBA') {

			$message = $this->validateCsvFileDkNba($request, $csvFile);

			return $message;
		}
	}


	/****************************************************************************************
	VALIDATE CSV FILE (DK NBA)
	****************************************************************************************/	

	private function validateCsvFileDkNba($request, $csvFile) {
		if (($handle = fopen($csvFile, 'r')) !== false) {
			$row = 0;

			while (($csvData = fgetcsv($handle, 5000, ',')) !== false) {
				if ($row != 0) {
				    $player[$row] = array(
				       	'name' => $csvData[1],
				       	'abbr_dk' => $csvData[5]
				    );

				    $gameInfo = $csvData[3];
				    $gameInfo = preg_replace("/(\w+@\w+)(\s)(.*)/", "$1", $gameInfo);
				    $gameInfo = preg_replace("/@/", "", $gameInfo);
				    $player[$row]['opp_abbr_dk'] = preg_replace("/".$player[$row]['abbr_dk']."/", "", $gameInfo);

				    # dd($player[$row]);

				    $player[$row]['name'] = dk_name_fix($player[$row]['name']);

				    $playerId = Player::where('name', $player[$row]['name'])->pluck('id');

				    # dd($playerId);

				    if (is_null($playerId)) {
						return 'The player name, <strong>'.$player[$row]['name'].'</strong>, does not exist in the database. You can add him <a target="_blank" href="http://dfstools.dev:8000/admin/nba/add_player">here</a>.'; 
				    } 

				    $teamExists = Team::where('abbr_dk', $player[$row]['abbr_dk'])->count();

				    if (!$teamExists) {
						return 'The team DK abbreviation, <strong>'.$player[$row]['abbr_dk'].'</strong>, does not exist in the database.'; 
				    }				    
				}

				$row++;
			}
		}

		return 'Valid';
	}


	/****************************************************************************************
	VALIDATE CSV FILE (FD NBA)
	****************************************************************************************/	

	private function validateCsvFileFdNba($request, $csvFile) {
		if (($handle = fopen($csvFile, 'r')) !== false) {
			$row = 0;

			while (($csvData = fgetcsv($handle, 5000, ',')) !== false) {
				if ($row != 0) {
				    $player[$row] = array(
				       	'name' => $csvData[2].' '.$csvData[3],
				       	'abbr_fd' => $csvData[8],
				       	'opp_abbr_fd' => $csvData[9]
				    );

				    $player[$row]['name'] = fd_name_fix($player[$row]['name']);

				    $playerExists = Player::where('name', $player[$row]['name'])->count();

				    if (!$playerExists) {
						return 'The player name, <strong>'.$player[$row]['name'].'</strong>, does not exist in the database. You can add him <a target="_blank" href="http://dfstools.dev:8000/admin/nba/add_player">here</a>.'; 
				    } 

				    $teamExists = Team::where('abbr_fd', $player[$row]['abbr_fd'])->count();

				    if (!$teamExists) {
						return 'The team FD abbreviation, <strong>'.$player[$row]['abbr_fd'].'</strong>, does not exist in the database.'; 
				    }				    
				}

				$row++;
			}
		}

		return 'Valid';
	}


	/****************************************************************************************
	VALIDATE DK MLB CONTEST
	****************************************************************************************/	

	public function validateDkMlbContest($date, $contestName, $entryFee, $timePeriod) {
		if ($contestName == '') {
			return 'Please enter the contest name.';
		}

		if (!is_numeric($entryFee)) {
			return 'Please enter a number for the entry fee.';
		}

		if ($entryFee <= 0) {
			return 'The entry fee must be greater than zero.';
		}

		if ($timePeriod == '-') {
			return 'Please select a valid time period.';
		}

		$contestExists = DkMlbContest::where('name', $contestName)
									 ->where('entry_fee', $entryFee)
									 ->where('time_period', $timePeriod)
									 ->where('date', $date)
									 ->count();

		if ($contestExists) {
			return 'This contest is already in the database.';
		}

		$playerPoolExists = PlayerPool::where('sport', 'MLB')
									->where('site', 'DK')
									->where('time_period', $timePeriod)
									->where('date', $date)
									->count();

		if (!$playerPoolExists) {
			return 'This contest does not match a player pool in the database.';
		}

		return 'Valid';
	}

}