<?php namespace App\Http\Controllers;

use App\Models\PlayerPool;
use App\Models\MlbPlayer;
use App\Models\MlbTeam;
use App\Models\MlbPlayerTeam;
use App\Models\DkMlbPlayer;

use App\Classes\SolverTopPlaysMlb;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

date_default_timezone_set('America/Chicago');

class SolverTopPlaysMlbController {

	public function solverTopPlaysMlb($siteInUrl, $timePeriodInUrl, $date, $sorter = '') {
		$solverTopPlaysMlb = new SolverTopPlaysMlb;

        # dd($sorter);

		$timePeriod = urlToUcWords($timePeriodInUrl);

        $activeLineups = $solverTopPlaysMlb->getActiveLineups($timePeriod, $date, $sorter);

		if ($siteInUrl == 'dk') {
			list($lineups, $players) = $solverTopPlaysMlb->generateLineups($timePeriod, $date, $activeLineups);
		}

        # ddAll($activeLineups);

		$playerPoolId = $lineups[0]['players'][0]->player_pool_id;
		$buyIn = $players[0]->buy_in;

		$unspentBuyIn = $solverTopPlaysMlb->calculateUnspentBuyIn($timePeriod, $date, $buyIn, $activeLineups);

        $defaultLineupBuyIn = getDefaultLineupBuyIn();

		# ddAll($lineups);

        if ($sorter == 'b') {
            $sorter = '(bFPTS)';
        }

        return view('solver_top_plays/dk/mlb', 
                     compact('date', 
                             'timePeriod', 
                             'timePeriodInUrl',
                             'playerPoolId', 
                             'buyIn', 
                             'unspentBuyIn',
                             'defaultLineupBuyIn',
                             'lineups', 
                             'players',
                             'sorter')); 
	}


    /****************************************************************************************
    AJAX
    ****************************************************************************************/

    public function addOrRemoveLineup(Request $request) {
        $playerPoolId = $request->input('playerPoolId');
        $hash = $request->input('hash');
        $totalSalary = $request->input('totalSalary');
        $buyIn = $request->input('buyIn');
        $addOrRemove = $request->input('addOrRemove');
        $players = $request->input('players');

        // prf($players);

        $solverTopPlaysMlb = new SolverTopPlaysMlb;

        if ($addOrRemove == 'Add') {
            $solverTopPlaysMlb->addLineup($playerPoolId, $hash, $totalSalary, $buyIn, $players);
        }

        if ($addOrRemove == 'Remove') {
            $solverTopPlaysMlb->removeLineup($playerPoolId, $hash);
        }
    }

}