<?php namespace App\Classes;

use App\Models\Season;

use App\Models\MlbTeam;
use App\Models\MlbPlayer;
use App\Models\MlbPlayerTeam;

use App\Models\PlayerPool;
use App\Models\DkMlbPlayer;

use App\Classes\SolverTopPlaysMlb;
use App\Classes\LineupBuilderMlb;

use App\Models\Lineup;
use App\Models\LineupDkMlbPlayer;

use Illuminate\Support\Facades\DB;

class LineupBuilderMlb {

    /****************************************************************************************
    CREATE LINEUPS
    ****************************************************************************************/   

    public function addHtmlToPlayersInPlayerPool($players, $lineup) {
        # ddAll($lineup);

        foreach ($players as &$player) {
            $player = $this->checkLineupForPlayer($lineup, $player);

            # ddAll($player);
        }
        unset($player);

        # ddAll($players);

        return $players;
    } 

    private function checkLineupForPlayer($lineup, $player) {
        foreach ($lineup['players'] as $lineupPlayer) {
            if ($lineupPlayer->mlb_player_id == $player->mlb_player_id) {
                $player->strikethrough_css_class = 'available-player-row-strikethrough';
                $player->update_icon = '<div class="circle-minus-icon"><span class="glyphicon glyphicon-minus"></span></div>';

                return $player;
            }
        }

        $player->strikethrough_css_class = '';
        $player->update_icon = '<div class="circle-plus-icon"><span class="glyphicon glyphicon-plus"></span></div>';

        return $player;  
    }

    public function getLineup($siteInUrl, $timePeriodInUrl, $date, $hash) {
        $lineup = [];

        $lineup['h2_tag'] = $this->createH2Tag($siteInUrl, $timePeriodInUrl, $date, $hash);

        $lineup['metadata'] = DB::table('lineups')
            ->join('player_pools', 'player_pools.id', '=', 'lineups.player_pool_id')
            ->select('lineups.id', 'hash', 'total_salary', 'lineups.buy_in as lineup_buy_in', 'active', 'money', 'player_pools.buy_in', 'player_pool_id')
            ->where('player_pools.site', strtoupper($siteInUrl))
            ->where('player_pools.time_period', ucfirst($timePeriodInUrl))
            ->where('player_pools.date', $date)
            ->where('lineups.hash', $hash)
            ->first();

        # ddAll($lineup);

        $lineup['players'] = $this->getPlayersInLineup($lineup['metadata']->id, $lineup['metadata']->player_pool_id);

        foreach ($lineup['players'] as $lineupPlayer) {
            $lineupPlayer->remove_player_icon = '<div class="circle-minus-icon"><span class="glyphicon glyphicon-minus"></span></div>';
        }

        ddAll($lineup);

        return $lineup;
    }

    private function getPlayersInLineup($lineupId, $playerPoolId) {
        return DB::table('lineups')
            ->join('lineup_dk_mlb_players', 'lineup_dk_mlb_players.lineup_id', '=', 'lineups.id')
            ->join('dk_mlb_players', 'dk_mlb_players.mlb_player_id', '=', 'lineup_dk_mlb_players.mlb_player_id')
            ->join('mlb_players', 'mlb_players.id', '=', 'dk_mlb_players.mlb_player_id')
            ->join('mlb_teams', 'mlb_teams.id', '=', 'dk_mlb_players.mlb_team_id')
            ->select('*')
            ->where('lineups.id', $lineupId)
            ->where('dk_mlb_players.player_pool_id', $playerPoolId)
            ->where('lineups.active', 1)
            ->get();
    }

    public function createEmptyLineup($siteInUrl, $timePeriodInUrl, $date) {
        $lineup = [];

        $lineup['h2_tag'] = $this->createH2Tag($siteInUrl, $timePeriodInUrl, $date, $hash = 'null');

        $lineup['metadata'] = new \stdClass();
        $lineup['metadata']->total_salary = 0;
        $lineup['metadata']->lineup_buy_in = getDefaultLineupBuyIn();

        if ($siteInUrl == 'dk') {
            $dkPositions = ['SP', 'SP', 'C', '1B', '2B', '3B', 'SS', 'OF', 'OF', 'OF'];

            for ($i = 0; $i < 10; $i++) { 
                $lineup['players'][$i] = new \stdClass();
                $lineup['players'][$i]->position = $dkPositions[$i];
                $lineup['players'][$i]->player_pool_id = '';
                $lineup['players'][$i]->player_id = '';
                $lineup['players'][$i]->name = '';
                $lineup['players'][$i]->salary = '';
                $lineup['players'][$i]->remove_player_icon = '';
            }

            return $lineup;            
        }
    }

    private function createH2Tag($siteInUrl, $timePeriodInUrl, $date, $hash) {
        if (is_null($hash)) {
            $phrase = ' ';
        }

        if (!is_null($hash)) {
            $phrase = ' From Import ';
        }

        return 'Create Lineup'.$phrase.'| '.strtoupper($siteInUrl).' MLB | '.ucfirst($timePeriodInUrl).' '.$date;
    }


    /****************************************************************************************
    PLAYERS IN PLAYER POOL
    ****************************************************************************************/

    public function getPlayersInPlayerPool($site, $timePeriod, $date) {
    	return DB::table($site.'_mlb_players')
            ->join('mlb_players', 'mlb_players.id', '=', 'dk_mlb_players.mlb_player_id')
            ->join('player_pools', 'player_pools.id', '=', 'dk_mlb_players.player_pool_id')
            ->select('*')
            ->where('player_pools.time_period', $timePeriod)
            ->where('player_pools.date', $date)
            ->get();
    }

}