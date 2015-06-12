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

        $lineup['h2_tag'] = $this->createH2Tag($siteInUrl, $hash);
        $lineup['sub_heading'] = $this->createSubHeading($timePeriodInUrl, $date);

        $lineup['metadata'] = DB::table('lineups')
            ->join('player_pools', 'player_pools.id', '=', 'lineups.player_pool_id')
            ->select('lineups.id', 'hash', 'total_salary', 'lineups.buy_in as lineup_buy_in', 'active', 'money', 'player_pools.buy_in', 'player_pool_id')
            ->where('player_pools.site', strtoupper($siteInUrl))
            ->where('player_pools.time_period', urlToUcWords($timePeriodInUrl))
            ->where('player_pools.date', $date)
            ->where('lineups.hash', $hash)
            ->first();

        # ddAll($lineup);

        $lineup['players'] = $this->getPlayersInLineup($lineup['metadata']->id, $lineup['metadata']->player_pool_id);

        $lineup['metadata']->total_bat_fpts = 0;

        foreach ($lineup['players'] as $lineupPlayer) {
            $lineupPlayer->remove_player_icon = '<div class="circle-minus-icon"><span class="glyphicon glyphicon-minus"></span></div>';

            $lineup['metadata']->total_bat_fpts += $lineupPlayer->bat_fpts;
        }

        # ddAll($lineup);

        return $lineup;
    }

    private function getPlayersInLineup($lineupId, $playerPoolId) {
        if (!is_int($lineupId)) {
            echo 'Lineup ID must be an integer.'; exit();
        }

        if (!is_int($playerPoolId)) {
            echo 'Player Pool ID must be an integer.'; exit();
        }

        $lineupPlayers = DB::select(DB::raw('select * FROM lineups 
            JOIN lineup_dk_mlb_players ON lineup_dk_mlb_players.lineup_id = lineups.id
            JOIN dk_mlb_players ON dk_mlb_players.mlb_player_id = lineup_dk_mlb_players.mlb_player_id 
                AND dk_mlb_players.position = lineup_dk_mlb_players.position
            JOIN mlb_players ON mlb_players.id = dk_mlb_players.mlb_player_id
            JOIN mlb_teams ON mlb_teams.id = dk_mlb_players.mlb_team_id
            WHERE lineups.id = '.$lineupId.'
            AND dk_mlb_players.player_pool_id = '.$playerPoolId.'
            AND lineups.active = 1'));

        # ddAll($lineupPlayers);

        $teams = MlbTeam::all();

        foreach ($lineupPlayers as $player) {
            foreach ($teams as $team) {
                if ($team->id == $player->mlb_opp_team_id) {
                    $player->opp_abbr_dk = $team->abbr_dk;
                }
            }
        }

        return $lineupPlayers;
    }

    public function createEmptyLineup($siteInUrl, $timePeriodInUrl, $date) {
        $lineup = [];

        $lineup['h2_tag'] = $this->createH2Tag($siteInUrl, $hash = 'null');
        $lineup['sub_heading'] = $this->createSubHeading($timePeriodInUrl, $date);

        $lineup['metadata'] = new \stdClass();
        $lineup['metadata']->total_salary = 0;
        $lineup['metadata']->total_bat_fpts = '0.00';
        $lineup['metadata']->lineup_buy_in = getDefaultLineupBuyIn();

        if ($siteInUrl == 'dk') {
            $dkPositions = ['SP', 'SP', 'C', '1B', '2B', '3B', 'SS', 'OF', 'OF', 'OF'];

            for ($i = 0; $i < 10; $i++) { 
                $lineup['players'][$i] = new \stdClass();
                $lineup['players'][$i]->position = $dkPositions[$i];
                $lineup['players'][$i]->player_pool_id = '';
                $lineup['players'][$i]->mlb_player_id = '';
                $lineup['players'][$i]->name = '';
                $lineup['players'][$i]->abbr_dk = '';
                $lineup['players'][$i]->opp_abbr_dk = '';
                $lineup['players'][$i]->salary = '';
                $lineup['players'][$i]->bat_fpts = '';
                $lineup['players'][$i]->remove_player_icon = '';
            }

            return $lineup;            
        }
    }

    private function createH2Tag($siteInUrl, $hash) {
        if (is_null($hash)) {
            $phrase = ' ';
        }

        if (!is_null($hash)) {
            $phrase = ' From Import ';
        }

        return 'Create Lineup'.$phrase.'| '.strtoupper($siteInUrl).' MLB';
    }

    private function createSubHeading($timePeriodInUrl, $date) {
        return urlToUcWords($timePeriodInUrl).' '.$date;
    }


    /****************************************************************************************
    PLAYERS IN PLAYER POOL
    ****************************************************************************************/

    public function getPlayersInPlayerPool($siteInUrl, $timePeriodInUrl, $date) {
    	$players = DB::table($siteInUrl.'_mlb_players')
            ->leftJoin('mlb_players', 'mlb_players.id', '=', 'dk_mlb_players.mlb_player_id')
            ->join('player_pools', 'player_pools.id', '=', 'dk_mlb_players.player_pool_id')
            ->leftJoin('mlb_players_teams', 'mlb_players_teams.mlb_player_id', '=', 'mlb_players.id')
            ->leftJoin('mlb_teams', 'mlb_teams.id', '=', 'mlb_players_teams.mlb_team_id')
            ->select('*')
            ->where('player_pools.time_period', urlToUcWords($timePeriodInUrl))
            ->where('player_pools.date', $date)
            ->where('mlb_players_teams.end_date', '>', $date)
            ->get();

        # ddAll($players);

        $teams = MlbTeam::all();

        foreach ($players as $player) {
            foreach ($teams as $team) {
                if ($team->id == $player->mlb_opp_team_id) {
                    $player->opp_abbr_dk = $team->abbr_dk;
                }
            }
        }

        return $players;
    }

}