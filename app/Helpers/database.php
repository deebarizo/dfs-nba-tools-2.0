<?php

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

function getBuyIn($playerPoolId) {
    $buyIn = DB::table('player_pools')
        ->where('id', $playerPoolId)
        ->pluck('buy_in');

    if (is_null($buyIn)) {
        return 0;
    }

    return $buyIn;
}

function getActiveLineups($playerPoolId) {
    $activeLineups = DB::table('lineups')
        ->select('*')
        ->whereRaw('player_pool_id = '.$playerPoolId.' AND active = 1')
        ->get(); 

    return $activeLineups;
}

function addLineup($playerPoolId, $hash, $totalSalary, $lineups) {
    $lineup = new Lineup; 

    $lineup->player_pool_id = $playerPoolId;
    $lineup->hash = $hash;
    $lineup->total_salary = $totalSalary; 
    $lineup->active = 1;

    $lineup->save();    

    $players = getPlayersOfLineup($lineups, $hash);

    foreach ($players as $player) {
        $lineupPlayer = new LineupPlayer;

        $lineupPlayer->lineup_id = $lineup->id;
        $lineupPlayer->player_fd_id = $player['player_id'];

        $lineupPlayer->save();
    }
}

function getPlayersOfLineup($lineups, $hash) {
    foreach ($lineups as $lineup) {
        if ($lineup['hash'] == $hash) {
            return $lineup['roster_spots'];
        }
    }
}

function removeLineup($playerPoolId, $hash) {
    $lineupId = DB::table('lineups')
        ->where('player_pool_id', $playerPoolId)
        ->where('hash', $hash)
        ->pluck('id');

    DB::table('lineup_players')
        ->where('lineup_id', $lineupId)
        ->delete();

    DB::table('lineups')
        ->where('id', $lineupId)
        ->delete();
}

function getTopPlays($date) {
    $players = DB::table('player_pools')
        ->join('players_fd', 'player_pools.id', '=', 'players_fd.player_pool_id')
        ->join('players', 'players_fd.player_id', '=', 'players.id')
        ->select('*')
        ->whereRaw('player_pools.date = "'.$date.'" AND players_fd.top_play_index = 1')
        ->orderBy('position')
        ->get();

    return $players;
}

function getPlayersByPostion($date) {
	$players['all'] = DB::table('player_pools')
        ->join('players_fd', 'player_pools.id', '=', 'players_fd.player_pool_id')
        ->join('players', 'players_fd.player_id', '=', 'players.id')
        ->select('*')
        ->whereRaw('player_pools.date = "'.$date.'"')
        ->orderBy('vr_minus1', 'desc')
        ->get();

    $players['PG'] = DB::table('player_pools')
        ->join('players_fd', 'player_pools.id', '=', 'players_fd.player_pool_id')
        ->join('players', 'players_fd.player_id', '=', 'players.id')
        ->select('*')
        ->whereRaw('player_pools.date = "'.$date.'" AND players_fd.position = "PG"')
        ->orderBy('fppg_minus1', 'desc')
        ->get();

    $players['SG'] = DB::table('player_pools')
        ->join('players_fd', 'player_pools.id', '=', 'players_fd.player_pool_id')
        ->join('players', 'players_fd.player_id', '=', 'players.id')
        ->select('*')
        ->whereRaw('player_pools.date = "'.$date.'" AND players_fd.position = "SG"')
        ->orderBy('fppg_minus1', 'desc')
        ->get();

    $players['SF'] = DB::table('player_pools')
        ->join('players_fd', 'player_pools.id', '=', 'players_fd.player_pool_id')
        ->join('players', 'players_fd.player_id', '=', 'players.id')
        ->select('*')
        ->whereRaw('player_pools.date = "'.$date.'" AND players_fd.position = "SF"')
        ->orderBy('fppg_minus1', 'desc')
        ->get();

    $players['PF'] = DB::table('player_pools')
        ->join('players_fd', 'player_pools.id', '=', 'players_fd.player_pool_id')
        ->join('players', 'players_fd.player_id', '=', 'players.id')
        ->select('*')
        ->whereRaw('player_pools.date = "'.$date.'" AND players_fd.position = "PF"')
        ->orderBy('fppg_minus1', 'desc')
        ->get();

    $players['C'] = DB::table('player_pools')
        ->join('players_fd', 'player_pools.id', '=', 'players_fd.player_pool_id')
        ->join('players', 'players_fd.player_id', '=', 'players.id')
        ->select('*')
        ->whereRaw('player_pools.date = "'.$date.'" AND players_fd.position = "C"')
        ->orderBy('fppg_minus1', 'desc')
        ->get();

    $dailyFdFilters = DB::select('SELECT t1.* FROM daily_fd_filters AS t1
                                     JOIN (
                                        SELECT player_id, MAX(created_at) AS latest FROM daily_fd_filters GROUP BY player_id
                                     ) AS t2
                                     ON t1.player_id = t2.player_id AND t1.created_at = t2.latest');

    foreach ($players as &$position) {
        foreach ($position as $key => &$player) {
            foreach ($dailyFdFilters as $filter) {
                if ($player->player_id == $filter->player_id) {
                    if ($filter->playing == 0) {
                        unset($position[$key]);

                        break;
                    }

                    if ($filter->notes == 'DTD') {
                        unset($position[$key]);

                        break;
                    }

                    $player->filter = $filter;

                    break;
                }
            }
        }
    }

    unset($position);
    unset($player);	

    foreach ($players as &$position) {
        $position = array_values($position);    
    }

    unset($position);

    # ddAll($players);

    return $players;
}

function getBoxScoreLinesForPlayer($startingSeasonId, $playerId, $endDate) {
	$result = DB::table('box_score_lines')
		->join('games', 'box_score_lines.game_id', '=', 'games.id')
	    ->join('seasons', 'games.season_id', '=', 'seasons.id')
	    ->select(DB::raw('*, pts+trb*1.2+ast*1.5+blk*2+stl*2-tov as fd_score, (pts+trb*1.2+ast*1.5+blk*2+stl*2-tov) / mp as fppm'))
	    ->whereRaw('box_score_lines.status = "Played" AND seasons.id >= '.$startingSeasonId.' AND player_id = '.$playerId.' AND games.date < "'.$endDate.'"')
	    ->get();    

	return $result;
}