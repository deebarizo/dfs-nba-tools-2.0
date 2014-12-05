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
use App\SolverTopPlays;
use App\Lineup;
use App\LineupPlayer;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

date_default_timezone_set('America/Chicago');

class SolverFdNbaController {

    //// Solver with top plays

    public function solver_with_top_plays($date) {
        if ($date == 'today') {
            $date = date('Y-m-d', time());
        }

        $players = getTopPlays($date);

        $solverTopPlays = new SolverTopPlays;

        if (!$solverTopPlays->validateFdPositions($players)) {
            return 'You are missing one or more positions';
        }

        if (!$solverTopPlays->validateMinimumTotalSalary($players)) {
            return 'The least expensive lineup is more than $60000.';
        }

        if (!$solverTopPlays->validateMaximumTotalSalary($players)) {
            return 'The most expensive lineup is less than $59400.';
        }

        $lineups = $solverTopPlays->buildLineupsWithTopPlays($players);

        $timePeriod = $lineups[0]['roster_spots']['PG2']->time_period;
        $playerPoolId = $lineups[0]['roster_spots']['PG2']->player_pool_id;
        $buyIn = getBuyIn($playerPoolId);

        $lineups = $solverTopPlays->markActiveLineups($lineups, $playerPoolId, $buyIn);

        # ddAll($lineups);

        return view('solver_with_top_plays_fd_nba', 
                     compact('date', 'timePeriod', 'playerPoolId', 'buyIn', 'lineups'));
    }

    // Ajax

    public function updateBuyIn($playerPoolId, $buyIn) {
        DB::table('player_pools')
            ->where('id', $playerPoolId)
            ->update(array('buy_in' => $buyIn));
    }

    public function addOrRemoveLineup($playerPoolId, $hash, $totalSalary, $buyIn, $addOrRemove) {
        if ($addOrRemove == 'Add') {
            $lineups = $_REQUEST['lineups'];

            addLineup($playerPoolId, $hash, $totalSalary, $buyIn, $lineups);            
        }

        if ($addOrRemove == 'Remove') {
            removeLineup($playerPoolId, $hash);
        }
    }

    public function updateLineupBuyIn($playerPoolId, $hash, $lineupBuyIn) {
        DB::table('lineups')
            ->where('player_pool_id', $playerPoolId)
            ->where('hash', $hash)
            ->update(array('buy_in' => $lineupBuyIn));        
    }

    //// Solver

	public function solverFdNba($date = 'today', $numTopLineups = 5) {
		if ($date == 'today') {
			$date = date('Y-m-d', time());
		}

        $players = getPlayersByPostion($date);

        $solver = new Solver;

        $lineups = $solver->buildFdNbaLineups($players);

        $topLineups = array_slice($lineups, 0, $numTopLineups);

        list($playerPercentages, 
             $playersInTopLineups,
             $percentagesInTopLineups) = calculatePlayerPercentagesOfTopLineups($topLineups);

        $timePeriod = $lineups[0][1]->time_period;

        # ddAll($lineups);

        return view('solver_fd_nba', compact('date', 
                                             'timePeriod', 
                                             'lineups', 
                                             'topLineups',
                                             'playerPercentages',
                                             'playersInTopLineups',
                                             'percentagesInTopLineups'));
	}

}