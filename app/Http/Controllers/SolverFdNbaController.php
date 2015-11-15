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

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

date_default_timezone_set('America/Chicago');

class SolverFdNbaController {

    /****************************************************************************************
    SOLVER TOP PLAYS
    ****************************************************************************************/

    public function solver_with_top_plays($date = 'default') {
        if ($date == 'default') {
            $date = getDefaultDate();

            return redirect('solver_with_top_plays_fd_nba/'.$date);
        }

        $timePeriod = 'All Day';
        $playerPoolId = getPlayerPoolId($date);
        $buyIn = getBuyIn($playerPoolId);
        $players = getTopPlays($date);

        $solverTopPlays = new SolverTopPlays;

        $activeLineups = $solverTopPlays->getActiveLineups($timePeriod, $date);

        # ddAll($activeLineups);

        $unspentPlayers = $solverTopPlays->filterUnspentPlayers($players, $activeLineups, $buyIn);

        $solverLineups = $solverTopPlays->buildLineupsWithTopPlays($unspentPlayers);

        $lineups = $solverTopPlays->markAndAppendActiveLineups($solverLineups, $playerPoolId, $buyIn);

        $players = $solverTopPlays->getPlayers($timePeriod, $date, $activeLineups);

        # ddAll($lineups);

        $unspentBuyIn = $solverTopPlays->calculateUnspentBuyIn($timePeriod, $date, $buyIn, $activeLineups);

        $defaultLineupBuyIn = getDefaultLineupBuyIn();

        $name = 'Solver NBA'; // title tag

        return view('solver_with_top_plays_fd_nba', 
                     compact('date', 
                             'timePeriod', 
                             'playerPoolId', 
                             'buyIn', 
                             'unspentBuyIn',
                             'lineups', 
                             'buyInPercentage',
                             'defaultLineupBuyIn',
                             'players',
                             'name'));
    }


    /********************************************
    AJAX
    ********************************************/

    public function updateBuyIn($playerPoolId, $buyIn) {
        DB::table('player_pools')
            ->where('id', $playerPoolId)
            ->update(array('buy_in' => $buyIn));
    }

    public function addDefaultLineupBuyIn($defaultLineupBuyInDollarAmount) {
        $defaultLineupBuyIn = new DefaultLineupBuyIn; 

        $defaultLineupBuyIn->dollar_amount = $defaultLineupBuyInDollarAmount;

        $defaultLineupBuyIn->save();    
    }

    public function addOrRemoveLineup(Request $request) {
        $playerPoolId = $request->input('playerPoolId');
        $hash = $request->input('hash');
        $totalSalary = $request->input('totalSalary');
        $buyIn = $request->input('buyIn');
        $addOrRemove = $request->input('addOrRemove');
        $playerIdsOfLineup = $request->input('playerIdsOfLineup');

        if ($addOrRemove == 'Add') {
            addLineup($playerPoolId, $hash, $totalSalary, $buyIn, $playerIdsOfLineup);            
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

    public function playOrUnplayLineup(Request $request) {
        $playerPoolId = $request->input('playerPoolId');
        $hash = $request->input('hash');   
        $playOrUnplay = $request->input('playOrUnplay');     

        if ($playOrUnplay == 'Play') {
            DB::table('lineups')
                ->where('player_pool_id', $playerPoolId)
                ->where('hash', $hash)
                ->update(array('money' => 1));             
        }

        if ($playOrUnplay == 'Unplay') {
            DB::table('lineups')
                ->where('player_pool_id', $playerPoolId)
                ->where('hash', $hash)
                ->update(array('money' => 0));             
        }
    }


    /****************************************************************************************
    SOLVER
    ****************************************************************************************/

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