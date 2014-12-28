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
    LINEUP BUILDER
    ****************************************************************************************/

    public function lineupBuilder($date = 'default') {
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

}