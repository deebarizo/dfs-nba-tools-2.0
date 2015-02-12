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
        $teamsAbbr = [];
        $teamsId = [];

        foreach ($players as $player) {
        	$teamsAbbr[] = $player->team_abbr;
            $teamsId[] = $player->team_id;
        }

        $teamsAbbr = array_unique($teamsAbbr);
        sort($teamsAbbr);
        $teamsToday['abbr'] = $teamsAbbr;

        $teamsId = array_unique($teamsId);
        sort($teamsId);
        $teamsToday['id'] = $teamsId;

        # dd($teamsToday);

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

    public function addVegasInfoToPlayers($players, $vegasScores) {
        foreach ($players as &$player) {
            foreach ($vegasScores as $vegasScore) {
                if ($player->team_name == $vegasScore['team']) {
                    $player->vegas_score_team = number_format(round($vegasScore['score'], 2), 2);
                }

                if ($player->opp_team_name == $vegasScore['team']) {
                    $player->vegas_score_opp_team = number_format(round($vegasScore['score'], 2), 2);
                }                
            }

            if (isset($player->vegas_score_team) === false || isset($player->vegas_score_opp_team) === false) {
                echo 'error: no team match in SAO<br>';
                echo $player->team_name.' vs '.$player->opp_team_name;
                exit();
            }
        } unset($player);

        foreach ($players as $player) {
            $player->line = $player->vegas_score_opp_team - $player->vegas_score_team;
        } unset($player);

        return $players;
    }

    public function getTeamFilters($teamsToday, $date) {
        foreach ($teamsToday['id'] as $key => $teamId) {
            $gamesInCurrentSeason = Game::where('season_id', '=', 11)->get();

            $gamesCount = 0;
            $totalPoints = 0;

            foreach ($gamesInCurrentSeason as $game) {
                if (strtotime($game->date) < strtotime($date)) {
                    if ($game->home_team_id == $teamId) {
                        $gamesCount++;
                        $totalPoints += $game->home_team_score;

                        continue;
                    }

                    if ($game->road_team_id == $teamId) {
                        $gamesCount++;
                        $totalPoints += $game->road_team_score;

                        continue;
                    }
                }
            }

            $teamPPG = $totalPoints / $gamesCount;

            $teamFilters[$key] = new \stdClass();
            $teamFilters[$key]->team_id = $teamId;
            $teamFilters[$key]->ppg = $teamPPG;
        }

        ddAll($teamFilters);

        foreach ($players as &$player) {
            foreach ($teamFilters as $teamFilter) {
                if ($player->team_id == $teamFilter->team_id) {
                    $player->team_ppg = $teamFilter->ppg;

                    $player->vegas_filter = ($player->vegas_score_team - $player->team_ppg) / $player->team_ppg;

                    break;
                }
            }
        } unset($player); 

        $activeDbTeamFilters = TeamFilter::where('active', '=', 1)->get();

        foreach ($players as &$player) {
            foreach ($activeDbTeamFilters as $teamFilter) {
                if ($player->team_id == $teamFilter->team_id) {
                    $player->team_ppg = $teamFilter->ppg;

                    $player->vegas_filter = ($player->vegas_score_team - $player->team_ppg) / $player->team_ppg;

                    break;
                }
            }
        } unset($player);
    }

}