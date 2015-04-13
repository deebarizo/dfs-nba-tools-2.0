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

use App\Models\Lineup;
use App\Models\LineupPlayer;
use App\Models\DefaultLineupBuyIn;

use App\Classes\Solver;
use App\Classes\SolverTopPlays;
use App\Classes\LineupBuilder;
use App\Classes\LineupBuilderMlb;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

date_default_timezone_set('America/Chicago');

class LineupBuilderController {

    /****************************************************************************************
    CREATE LINEUP MLB
    ****************************************************************************************/

    public function createLineupMlb($siteInUrl, $timePeriodInUrl, $date, $hash = null) {
        $lineupBuilderMlb = new LineupBuilderMlb;

        $players = $lineupBuilderMlb->getPlayersInPlayerPool($siteInUrl, $timePeriodInUrl, $date);

        # ddAll($players);

        if (is_null($hash)) {
            $lineup = $lineupBuilderMlb->createEmptyLineup($siteInUrl, $timePeriodInUrl, $date);
        }

        if (!is_null($hash)) {
            $lineup = $lineupBuilderMlb->getLineup($siteInUrl, $timePeriodInUrl, $date, $hash);
        }

        $players = $lineupBuilderMlb->addHtmlToPlayersInPlayerPool($players, $lineup);

        # ddAll($players);

        return view('lineup_builder/dk/mlb/create_lineup', compact('date', 
                                                            'lineup', 
                                                            'players'));
    }


    /****************************************************************************************
    CREATE LINEUP NBA
    ****************************************************************************************/

    public function createLineup($date, $hash = null) {
        $lineupBuilder = new LineupBuilder;

        $players = $lineupBuilder->getPlayersInPlayerPool($date);

        # ddAll($players);

        if (is_null($hash)) {
            $name = 'Create Lineup';
            $lineup = $this->createEmptyLineup();
        }

        if (!is_null($hash)) {
            $name = 'Create Lineup From Import';
            $lineup = $lineupBuilder->getLineup($hash);

            $lineup = $this->addHtmlToImportedLineup($lineup);
        }

        $players = $this->addHtmlToEachAvailablePlayer($players, $lineup);

        # ddAll($players);

        return view('lineup_builder/create_lineup', compact('date', 
                                                            'lineup', 
                                                            'players', 
                                                            'name'));
    } 

    private function createEmptyLineup() {
        $lineup = [];

        $lineup['metadata'] = new \stdClass();
        $lineup['metadata']->total_salary = 0;
        $lineup['metadata']->lineup_buy_in = getDefaultLineupBuyIn();

        $fdPositions = ['PG', 'PG', 'SG', 'SG', 'SF', 'SF', 'PF', 'PF', 'C'];

        for ($i = 0; $i < 9; $i++) { 
            $lineup['players'][$i] = new \stdClass();
            $lineup['players'][$i]->position = $fdPositions[$i];
            $lineup['players'][$i]->player_pool_id = '';
            $lineup['players'][$i]->player_id = '';
            $lineup['players'][$i]->name = '';
            $lineup['players'][$i]->salary = '';
            $lineup['players'][$i]->remove_player_icon = '';
        }

        return $lineup;
    }

    private function addHtmlToImportedLineup($lineup) {
        foreach ($lineup['players'] as $lineupPlayer) {
            $lineupPlayer->remove_player_icon = '<div class="circle-minus-icon"><span class="glyphicon glyphicon-minus"></span></div>';
        }

        return $lineup;
    }

    private function addHtmlToEachAvailablePlayer($players, $lineup) {
        foreach ($players as &$player) {
            $player = $this->checkLineupForPlayer($lineup, $player);
        }

        unset($player);

        return $players;
    }

    private function checkLineupForPlayer($lineup, $availablePlayer) {
        foreach ($lineup['players'] as $lineupPlayer) {
            if ($lineupPlayer->player_id == $availablePlayer->player_id) {
                $availablePlayer->strikethrough_css_class = 'available-player-row-strikethrough';
                $availablePlayer->update_icon = '<div class="circle-minus-icon"><span class="glyphicon glyphicon-minus"></span></div>';

                return $availablePlayer;
            }
        }

        $availablePlayer->strikethrough_css_class = '';
        $availablePlayer->update_icon = '<div class="circle-plus-icon"><span class="glyphicon glyphicon-plus"></span></div>';

        return $availablePlayer;  
    }


    /****************************************************************************************
    SHOW ACTIVE LINEUPS
    ****************************************************************************************/

    public function showActiveLineups($date = 'default') {
        if ($date == 'default') {
            $date = getDefaultDate();

            return redirect('lineup_builder/'.$date.'/');
        }

        $lineupBuilder = new LineupBuilder;

        $lineups = $lineupBuilder->getLineups($date);

        $name = 'Lineup Builder';

        # ddAll($lineups);

        return view('lineup_builder', compact('date', 'lineups', 'name'));
    }

}