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
    PLAYERS
    ****************************************************************************************/

    public function getTeamAbbrBr($teamId, $teams) {
        foreach ($teams as $team) {
            if ($teamId == $team->id) {
                return $team->abbr_br;
            }
        }
    }

    public function getTeamAbbrPm($teamId, $teams) {
        foreach ($teams as $team) {
            if ($teamId == $team->id) {
                return $team->abbr_pm;
            }
        }
    }

    public function createGameScore($teamScore, $oppTeamScore) {
        if ($teamScore > $oppTeamScore) {
            return '<span style="color: green">W</span> '.$teamScore.'-'.$oppTeamScore;
        }

        if ($teamScore < $oppTeamScore) {
            return '<span style="color: red">L</span> '.$teamScore.'-'.$oppTeamScore;
        }
    }

    public function createLine($vegasTeamScore, $oppVegasTeamScore) {
        $diff = abs($vegasTeamScore - $oppVegasTeamScore);

        if ($vegasTeamScore > $oppVegasTeamScore) {
            return '-'.$diff;
        }    

        if ($vegasTeamScore < $oppVegasTeamScore) {
            return '+'.$diff;
        }       

        return 'PK';
    }


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

        foreach ($teamsToday['id'] as $key => $teamId) {
            $teamsToday['opp_id'][$key] = $this->getOppTeamId($players, $teamId);
        }

        # ddAll($teamsToday);

        return $teamsToday;
	}

    private function getOppTeamId($players, $teamId) {
        foreach ($players as $player) {
            if ($teamId == $player->team_id) {
                return $player->opp_team_id;
            }
        }
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

    public function getTeamFilters($teamsToday, $date, $seasonId) {
        # ddAll($teamsToday);

        foreach ($teamsToday['id'] as $key => $teamId) {
            $numGamesInCurrentSeason = Game::where('season_id', '=', $seasonId)
                                        ->where('date', '<', $date)
                                        ->where(function($query) use($teamId) {
                                            return $query->where('home_team_id', '=', $teamId) 
                                                         ->orWhere('road_team_id', '=', $teamId);
                                        })                                        
                                        ->count();

            ddAll($numGamesInCurrentSeason);

            $teamPPG = $totalPoints / $gamesCount;

            $teamFilters[$key] = new \stdClass();
            $teamFilters[$key]->team_id = $teamId;
            $teamFilters[$key]->ppg = $teamPPG;
        }

        $activeDbTeamFilters = TeamFilter::where('active', '=', 1)->get()->toArray();

        foreach ($activeDbTeamFilters as $activeDbTeamFilter) {
            foreach ($teamFilters as $teamFilter) {
                if ($teamFilter->team_id == $activeDbTeamFilter['team_id']) {
                    $teamFilter->ppg = $activeDbTeamFilter['ppg'];

                    break;
                }
            }
        }

        return $teamFilters;
    }

    public function addVegasFilterToPlayers($players, $teamFilters) {
        foreach ($players as &$player) {
            foreach ($teamFilters as $teamFilter) {
                if ($player->team_id == $teamFilter->team_id) {
                    $player->team_ppg = $teamFilter->ppg;

                    $player->vegas_filter = ($player->vegas_score_team - $player->team_ppg) / $player->team_ppg;

                    break;
                }
            }
        } unset($player);         

        return $players;
    }

    public function getBoxScoreLinesOfPlayers($players, $date) {
        foreach ($players as $player) {
            if (isset($player->filter)) {
                if ($player->filter->filter == 1) {
                    $playerStats[$player->player_id]['cs'] = getBoxScoreLinesForPlayer(11, $player->player_id, $date);
                }
            }

            $playerStats[$player->player_id]['all'] = getBoxScoreLinesForPlayer(10, $player->player_id, $date);
        }

        return $playerStats;
    }

    public function generateProjections($players, $playerStats) {
        foreach ($players as &$player) {
            if (isset($player->filter) && $player->filter->filter == 1) {
                if ($player->filter->fppg_source == 'fp cs') {
                    echo $player->name."'s filter should be changed from 'fp cs' to 'mp cs' and 'cs'.<br>";
                }
                
                // FPPM Source

                if (is_numeric($player->filter->fppm_source) ) {
                    $player->fppm = $player->filter->fppm_source;
                }        

                if ($player->filter->fppm_source == 'cs') {
                    $player = calculateFppm($player, $playerStats[$player->player_id]['cs']);
                } 

                // FPPG Source

                if (is_numeric($player->filter->fppg_source) ) {
                    $player->mp_mod = $player->filter->fppg_source;
                } 

                if ($player->filter->fppg_source == 'mp cs') {
                    $player->mp_mod = calculateMpMod($playerStats[$player->player_id]['cs'], $player->filter->mp_ot_filter);
                }

                if (is_null($player->filter->fppg_source) ) {
                    $player->mp_mod = calculateMpMod($playerStats[$player->player_id]['all'], $player->filter->mp_ot_filter);
                }


                if (!isset($player->fppm) ) {
                    $player = calculateFppm($player, $playerStats[$player->player_id]['all']);
                }

                if (!isset($player->mp_mod)) {
                    ddAll($player);
                }
            } else {
                $player = calculateFppm($player, $playerStats[$player->player_id]['all']);
                $player->mp_mod = calculateMpMod($playerStats[$player->player_id]['all'], 0);
            }

            // STATS IN VIEW

            $player->fppmWithVegasFilter = numFormat(($player->fppm * $player->vegas_filter) + $player->fppm);

            $player->fppg = $player->mp_mod * $player->fppm;
            $player->fppgWithVegasFilter = numFormat(($player->fppg * $player->vegas_filter) + $player->fppg);

            $player->vr = numFormat($player->fppgWithVegasFilter / ($player->salary / 1000));

        } unset($player);

        return $players;
    }

    public function removeInactivePlayers($players) {
        foreach ($players as $key => $player) {
            if (isset($player->filter)) {
                if (isset($player->filter->playing) && $player->filter->playing == 0) {
                    unset($players[$key]);
                    continue;
                }

                if (isset($player->filter->notes) && $player->filter->notes == 'DTD') {
                    $dtdPlayers[] = $player;

                    unset($players[$key]);
                    continue;                    
                }           
            }
        }

        return $players;        
    }

}