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

		if ($contestExists > 0) {
			return 'This contest is already in the database.';
		}

		return 'Valid';
	}

}