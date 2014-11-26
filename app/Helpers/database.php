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

    $players['pg'] = DB::table('player_pools')
        ->join('players_fd', 'player_pools.id', '=', 'players_fd.player_pool_id')
        ->join('players', 'players_fd.player_id', '=', 'players.id')
        ->select('*')
        ->whereRaw('player_pools.date = "'.$date.'" AND players_fd.position = "pg"')
        ->orderBy('vr_minus1', 'desc')
        ->get();

    $players['sg'] = DB::table('player_pools')
        ->join('players_fd', 'player_pools.id', '=', 'players_fd.player_pool_id')
        ->join('players', 'players_fd.player_id', '=', 'players.id')
        ->select('*')
        ->whereRaw('player_pools.date = "'.$date.'" AND players_fd.position = "sg"')
        ->orderBy('vr_minus1', 'desc')
        ->get();

    $players['sf'] = DB::table('player_pools')
        ->join('players_fd', 'player_pools.id', '=', 'players_fd.player_pool_id')
        ->join('players', 'players_fd.player_id', '=', 'players.id')
        ->select('*')
        ->whereRaw('player_pools.date = "'.$date.'" AND players_fd.position = "sf"')
        ->orderBy('vr_minus1', 'desc')
        ->get();

    $players['pf'] = DB::table('player_pools')
        ->join('players_fd', 'player_pools.id', '=', 'players_fd.player_pool_id')
        ->join('players', 'players_fd.player_id', '=', 'players.id')
        ->select('*')
        ->whereRaw('player_pools.date = "'.$date.'" AND players_fd.position = "pf"')
        ->orderBy('vr_minus1', 'desc')
        ->get();

    $players['c'] = DB::table('player_pools')
        ->join('players_fd', 'player_pools.id', '=', 'players_fd.player_pool_id')
        ->join('players', 'players_fd.player_id', '=', 'players.id')
        ->select('*')
        ->whereRaw('player_pools.date = "'.$date.'" AND players_fd.position = "c"')
        ->orderBy('vr_minus1', 'desc')
        ->get();	

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