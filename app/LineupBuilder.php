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
            ->select('lineups.id', 'hash', 'total_salary', 'lineups.buy_in as lineup_buy_in', 'active', 'money', 'player_pools.buy_in')
            ->where('player_pools.date', '=', $date)
            ->get();

        $lineupPlayers = DB::table('lineups')
            ->join('lineup_players', 'lineup_players.lineup_id', '=', 'lineups.id')
            ->join('player_pools', 'player_pools.id', '=', 'lineups.player_pool_id')
            ->select('*')
            ->where('player_pools.date', '=', $date)
            ->get();

        $lineups = [];

        foreach ($metadataOfLineups as $key => $metadataOfLineup) {
            $lineups[$key]['metadata'] = $metadataOfLineup;

            $lineups[$key]['players'] = $this->matchPlayersWithMetadataOfLineups($metadataOfLineup->id, $lineupPlayers);
        }

        return $lineups;
	}

    private function matchPlayersWithMetadataOfLineups($lineupId, $lineupPlayers) {
        $matchedPlayers = [];

        foreach ($lineupPlayers as $lineupPlayer) {
            $matchedPlayers = $this->matchPlayersWithMetadataOfLineup($matchedPlayers, $lineupPlayer, $lineupId);
        }

        return $matchedPlayers;
    }

    private function matchPlayersWithMetadataOfLineup($matchedPlayers, $lineupPlayer, $lineupId) {
        if ($lineupPlayer->lineup_id == $lineupId) {
            $matchedPlayers[] = $lineupPlayer;
        }

        return $matchedPlayers;
    }

}