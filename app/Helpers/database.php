<?php

use Illuminate\Support\Facades\DB;

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