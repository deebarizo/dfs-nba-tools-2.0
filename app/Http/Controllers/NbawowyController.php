<?php namespace App\Http\Controllers;

use App\Models\Season;
use App\Models\Team;
use App\Models\Game;
use App\Models\Player;
use App\Models\BoxScoreLine;
use App\Models\PlayerPool;
use App\Models\PlayerFd;
use App\Models\DailyFdFilter;
use App\Models\TeamFilter;
use App\Classes\Solver;
use App\Classes\SolverTopPlays;
use App\Models\Lineup;
use App\Models\LineupPlayer;
use App\Models\DefaultLineupBuyIn;
use App\Classes\LineupBuilder;
use App\Classes\NbawowyBuilder;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

date_default_timezone_set('America/Chicago');

class NbawowyController {

    public function nbawowy_form() {
        $beginningOfSeasonDate = '2014-10-28';
        $yesterdayDate = date('Y-m-d', strtotime('-1 day', strtotime(date('Y-m-d'))));

        return view('nbawowy/form', compact('beginningOfSeasonDate', 'yesterdayDate'));
    }

	public function nbawowy($name, $startDate, $endDate, $playerOff) {
        $name = preg_replace("/_/", " ", $name);
        $playerOffInUrl = preg_replace("/_/", "%20", $playerOff);
        $playerOffInView = preg_replace("/_/", " ", $playerOff);

        $nbawowyBuilder = new NbawowyBuilder;

        $stats = $nbawowyBuilder->getStats($name, $startDate, $endDate, $playerOffInUrl);

        # ddAll($stats);

        return view('nbawowy/results', compact('name', 'startDate', 'endDate', 'playerOffInView', 'stats'));
	}

}