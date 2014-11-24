<?php

use Illuminate\Support\Facades\DB;

function getBoxScoreLinesForPlayer($startingSeasonId, $playerId, $endDate) {
	$result = DB::table('box_score_lines')
		->join('games', 'box_score_lines.game_id', '=', 'games.id')
	    ->join('seasons', 'games.season_id', '=', 'seasons.id')
	    ->select(DB::raw('*, pts+trb*1.2+ast*1.5+blk*2+stl*2-tov as fd_score, (pts+trb*1.2+ast*1.5+blk*2+stl*2-tov) / mp as fppm'))
	    ->whereRaw('box_score_lines.status = "Played" AND seasons.id >= '.$startingSeasonId.' AND player_id = '.$playerId.' AND games.date <= "'.$endDate.'"')
	    ->get();    

	return $result;
}