<?php namespace App\Classes;

use App\Models\Season;
use App\Models\Team;
use App\Models\Game;
use App\Models\Player;
use App\Models\BoxScoreLine;
use App\Models\PlayerPool;
use App\Models\PlayerFd;
use App\Models\DailyFdFilter;
use App\Models\TeamFilter;
use App\Classes\Solver;
use App\Classes\SolverTopPlays;
use App\Models\Lineup;
use App\Models\LineupPlayer;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Session;

class StatBuilder {

	/****************************************************************************************
	DAILY
	****************************************************************************************/

	public function getPlayersInPlayerPool($date) {
		$players = DB::table('player_pools')
            ->join('players_fd', 'player_pools.id', '=', 'players_fd.player_pool_id')
            ->join('players', 'players_fd.player_id', '=', 'players.id')
            ->select('*', 'players_fd.id as player_fd_index')
            ->where('player_pools.date', '=', $date)
            ->get();

        return $players;
	}

	public function getTimePeriodOfPlayerPool($players) {
		return $players[0]->time_period;
	}

	public function matchPlayersToTeams($players, $teams) {
        foreach ($players as &$player) {
        	$player = $this->matchPlayerToTeam($player, $teams);
        } unset($player);

        return $players;
	}

	private function matchPlayerToTeam($player, $teams) {
        foreach ($teams as $team) {
            if ($player->team_id == $team->id) {
                $player->team_name = $team->name_br;
                $player->team_abbr = $team->abbr_br;

                continue;
            }

            if ($player->opp_team_id == $team->id) {
                $player->opp_team_name = $team->name_br;
                $player->opp_team_abbr = $team->abbr_br;

                continue;
            }

            if (isset($player->team_name) && isset($player->opp_team_name)) {
                break;
            }
        }

        return $player;
	}

	public function getTeamsToday($players, $teams) {
        $teamsToday = [];

        foreach ($players as $player) {
        	$teamsToday[] = $player->team_abbr;
        }

        $teamsToday = array_unique($teamsToday);
        sort($teamsToday);

        return $teamsToday;
	}

	public function matchPlayersToFilters($players) {
        $dailyFdFilters = DB::select('SELECT t1.* FROM daily_fd_filters AS t1
                                         JOIN (
                                            SELECT player_id, MAX(created_at) AS latest FROM daily_fd_filters GROUP BY player_id
                                         ) AS t2
                                         ON t1.player_id = t2.player_id AND t1.created_at = t2.latest');

        foreach ($players as &$player) {
            foreach ($dailyFdFilters as $filter) {
                if ($player->player_id == $filter->player_id) {
                    $player->filter = $filter;

                    break;
                }
            }
        } unset($player);

        return $players;
	}

}