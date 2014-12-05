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

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

date_default_timezone_set('America/Chicago');

class SolverFdNbaController {

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

    /*    $lineup = DB::table('lineup_players')
            ->join('lineups', 'lineups.id', '=', 'lineup_players.lineup_id')
            ->join('player_pools', 'player_pools.id', '=', 'lineups.player_pool_id')
            ->join('players_fd', 'players_fd.player_id', '=', 'lineups_players.player_fd_id')
            ->select('*')
            ->whereRaw('player_pools.date = "'.$date.'" AND lineups.hash = 63475')
            ->get(); */

        # ddAll($lineups);

        return view('solver_with_top_plays_fd_nba', compact('date', 'timePeriod', 'lineups'));
    }

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