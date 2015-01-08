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
use App\LineupBuilder;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

date_default_timezone_set('America/Chicago');

class LineupBuilderController {

    /****************************************************************************************
    SHOW ACTIVE LINEUPS
    ****************************************************************************************/

    public function showActiveLineups($date = 'default') {
        if ($date == 'default') {
            $date = getDefaultDate();

            return redirect('lineup_builder/'.$date);
        }

        $lineupBuilder = new LineupBuilder;

        $lineups = $lineupBuilder->getLineups($date);

        $name = 'Lineup Builder';

        # ddAll($lineups);

        return view('lineup_builder', compact('date', 'lineups', 'name'));
    }


    /****************************************************************************************
    CREATE LINEUP
    ****************************************************************************************/

    public function createLineup($date) {
        $lineupBuilder = new LineupBuilder;

        $players = $lineupBuilder->getPlayersInPlayerPool($date);

        $name = 'Create Lineup';
        $defaultLineupBuyIn = getDefaultLineupBuyIn();

        # ddAll($players);

        return view('lineup_builder/create_lineup', compact('date', 
                                                            'lineup', 
                                                            'players', 
                                                            'name', 
                                                            'defaultLineupBuyIn'));
    } 


    /****************************************************************************************
    EDIT ACTIVE LINEUP
    ****************************************************************************************/

    public function editActiveLineup($date, $hash) {
        $lineupBuilder = new LineupBuilder;

        $lineup = $lineupBuilder->getLineup($hash);

        $players = $lineupBuilder->getPlayersInPlayerPool($date);

        $name = 'Edit Lineup';

        ddAll($players);

        return view('lineup_builder/edit_lineup', compact('date', 'lineup', 'players', 'name'));
    } 

}