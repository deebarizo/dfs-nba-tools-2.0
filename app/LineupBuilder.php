<?php namespace App;

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

use Illuminate\Support\Facades\DB;

class LineupBuilder {

	/****************************************************************************************
	GET LINEUPS
	****************************************************************************************/	

	public function getLineups($date) {
		$metadataOfLineups = DB::table('lineups')
            ->join('player_pools', 'player_pools.id', '=', 'lineups.player_pool_id')
            ->select('lineups.id', 'hash', 'total_salary', 'lineups.buy_in as lineup_buy_in', 'active', 'money', 'player_pools.buy_in', 'player_pool_id')
            ->where('player_pools.date', '=', $date)
            ->get();

        $lineups = [];

        foreach ($metadataOfLineups as $key => $metadataOfLineup) {
            $lineups[$key]['metadata'] = $metadataOfLineup;

            $lineups[$key]['players'] = $this->getPlayersInLineup($metadataOfLineup->id, $metadataOfLineup->player_pool_id);
        }

        foreach ($lineups as &$lineup) {
            $lineup['metadata']->lineup_buy_in_percentage = numFormat($lineup['metadata']->lineup_buy_in / $lineup['metadata']->buy_in * 100, 2);
        }

        unset($lineup);

        # ddAll($lineups);

        return $lineups;
	}

    private function getPlayersInLineup($lineupId, $playerPoolId) {
        $playersInActiveLineups = DB::table('lineups')
            ->join('lineup_players', 'lineup_players.lineup_id', '=', 'lineups.id')
            ->join('players_fd', 'players_fd.player_id', '=', 'lineup_players.player_fd_id')
            ->join('players', 'players.id', '=', 'players_fd.player_id')
            ->join('teams', 'teams.id', '=', 'players_fd.team_id')
            ->select('*')
            ->whereRaw('lineups.id = '.$lineupId.' AND players_fd.player_pool_id = '.$playerPoolId.' AND lineups.active = 1')
            ->orderByRaw(DB::raw('lineup_id, FIELD(position, "PG", "SG", "SF", "PF", "C"), salary DESC'))
            ->get();

        # ddAll($playerPoolId);

        return $playersInActiveLineups;    
    }

}