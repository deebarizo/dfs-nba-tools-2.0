<?php namespace App\Http\Controllers;

use App\Season;
use App\Team;
use App\Game;
use App\Player;
use App\BoxScoreLine;
use App\PlayerPool;
use App\PlayerFd;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

date_default_timezone_set('America/Chicago');

class DailyController {

	public function daily_fd_nba($date = 'today') {
		if ($date == 'today') {
			$date = date('Y-m-d', time());
		}

		$players = DB::table('player_pools')
            ->join('players_fd', 'player_pools.id', '=', 'players_fd.player_pool_id')
            ->join('players', 'players_fd.player_id', '=', 'players.id')
            ->select('*')
            ->where('player_pools.date', '=', $date)
            ->orderBy('players_fd.id', 'asc')
            ->get();	

        foreach ($players as $player) {
			$playerStats[$player->player_id] = DB::table('box_score_lines')
	            ->join('games', 'box_score_lines.game_id', '=', 'games.id')
	            ->join('seasons', 'games.season_id', '=', 'seasons.id')
	            ->select(DB::raw('*'))
	            ->whereRaw('box_score_lines.status = "Played" AND seasons.id >= 10 AND player_id = '.$player->player_id)
	            ->get();	        	
        }

        foreach ($playerStats as &$gameLogs) {
        	foreach ($gameLogs as &$gameLog) {
	        	$gameLog->fd_score = 
	        		$gameLog->pts +
	        		($gameLog->trb * 1.2) +
	        		($gameLog->ast * 1.5) +
					($gameLog->blk * 2) +
					($gameLog->stl * 2) +
					($gameLog->tov * -1);   
					
				if ($gameLog->mp > 0) {
					$gameLog->fppm = $gameLog->fd_score / $gameLog->mp;     
				} else {
					$gameLog->fppm = 0;
				}
        	}
        }

        unset($gameLogs);
        unset($gameLog);

        foreach ($players as &$player) {
        	$gamesPlayed = count($playerStats[$player->player_id]);

        	$totalFp = 0;

        	foreach ($playerStats[$player->player_id] as $gameLog) {
        		$totalFp += $gameLog->fd_score;
        	}

        	if ($gamesPlayed > 0) {
        		$player->fppg = $totalFp / $gamesPlayed;
        	} else {
        		$player->fppg = 0;
        	}

        	$totalSquaredDiff = 0; // For SD

        	foreach ($playerStats[$player->player_id] as $gameLog) {
        		$totalSquaredDiff = $totalSquaredDiff + pow($gameLog->fd_score - $player->fppg, 2);
        	}

        	if ($player->fppg != 0) {
        		$player->sd = sqrt($totalSquaredDiff / $gamesPlayed);
        		$player->cv = ($player->sd / $player->fppg) * 100;
        	} else {
        		$player->sd = 0;
        		$player->cv = 0;
        	}
        }

        unset($player);

        ddAll($players);

		return view('daily_fd_nba');
	}

}