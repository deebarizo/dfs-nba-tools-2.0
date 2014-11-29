<?php namespace App\Http\Controllers;

use App\Season;
use App\Team;
use App\Game;
use App\Player;
use App\BoxScoreLine;
use App\PlayerPool;
use App\PlayerFd;
use App\DailyFdFilter;
use App\TeamFilter;
use App\Solver;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

date_default_timezone_set('America/Chicago');

class SolverFdNbaController {

	public function solverFdNba($date = 'today') {
		if ($date == 'today') {
			$date = date('Y-m-d', time());
		}

        $players = getPlayersByPostion($date);

        $solver = new Solver;

        $lineups = $solver->buildFdNbaLineups($players);

        $topLineups = array_slice($lineups, 0, 10);

        list($playerPercentages, 
             $playersInTopLineups,
             $percentagesInTopLineups) = calculatePlayerPercentagesOfTopLineups($topLineups);

        $timePeriod = $lineups[0][1]->time_period;

        # ddAll($first10Lineups);

        return view('solver_fd_nba', compact('date', 
                                             'timePeriod', 
                                             'lineups', 
                                             'playerPercentages',
                                             'playersInTopLineups',
                                             'percentagesInTopLineups'));
	}

}