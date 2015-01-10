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
use App\DefaultLineupBuyIn;

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

        $metadataOfActiveLineups = getMetadataOfActiveLineups($playerPoolId);

        $solverTopPlays = new SolverTopPlays;

        $metadataOfActiveLineups = $solverTopPlays->appendMoreMetadataToActiveLineups($metadataOfActiveLineups, $buyIn);

        $activeLineups = $solverTopPlays->getActiveLineups($metadataOfActiveLineups, $playerPoolId);

        $unspentPlayers = $solverTopPlays->filterUnspentPlayers($players, $activeLineups, $buyIn);

        $solverTopPlays->validateTopPlays($unspentPlayers, $metadataOfActiveLineups);

        $lineups = $solverTopPlays->buildLineupsWithTopPlays($unspentPlayers);

        $lineups = $solverTopPlays->markAndAppendActiveLineups($lineups, $playerPoolId, $buyIn);

        $areThereActiveLineups = $solverTopPlays->areThereActiveLineups($lineups);

        $unspentBuyIn = $solverTopPlays->calculateUnspentBuyIn($areThereActiveLineups, $lineups, $buyIn);

        $defaultLineupBuyIn = getDefaultLineupBuyIn();

        $players = $solverTopPlays->sortPlayers($players); // for select options

        # ddAll($lineups);

        return view('solver_with_top_plays_fd_nba', 
                     compact('date', 
                             'timePeriod', 
                             'playerPoolId', 
                             'buyIn', 
                             'unspentBuyIn',
                             'lineups', 
                             'areThereActiveLineups',
                             'buyInPercentage',
                             'defaultLineupBuyIn',
                             'players'));
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