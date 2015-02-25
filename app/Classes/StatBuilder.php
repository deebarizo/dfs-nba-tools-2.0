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
    STUDIES
    ****************************************************************************************/

    public function getXMinBasedOnAbsoluteSpread($absoluteSpread) {
        if ($absoluteSpread == 'NOABS') {
            return -25;
        }

        if ($absoluteSpread == 'ABS') {
            return 0;
        }
    }

    public function getSpreadsAndPlayerFptsErrorBySeason($earliestSeasonStartYear, $latestSeasonStartYear, $mpgMax, $fppgMax, $fppgMin, $absoluteSpread) {
        $seasons = Season::where('start_year', '>=', $earliestSeasonStartYear)
                    ->where('end_year', '<=', $latestSeasonStartYear)
                    ->get()
                    ->toArray();

        foreach ($seasons as &$season) { 
            $season['eligible_players'] = $this->getEligiblePlayers($season['id'], $mpgMax, $fppgMax, $fppgMin);
            $season['teams'] = $this->getTeams($season['id']);

            $boxScoreLines = $this->getBoxScoreLines($season['id'], $absoluteSpread);
            $boxScoreLines = $this->removeIneligiblePlayers($boxScoreLines, $season['eligible_players']);
            $season['box_score_lines'] = $this->addPlayerFptsErrorToBoxScoreLines($boxScoreLines, $season['eligible_players'], $season['teams']);

        } unset($season);

        return $seasons;
    }

    private function getEligiblePlayers($seasonId, $mpgMax, $fppgMax, $fppgMin) {
        return DB::table('box_score_lines')
            ->select(DB::raw('player_id, 
                              players.name, 
                              box_score_lines.team_id, 
                              teams.abbr_br, 
                              AVG(mp) as mpg, 
                              SUM(pts + (trb * 1.2) + (ast * 1.5) + (blk * 2) + (stl * 2) - tov) / count(*) as fppg,
                              count(*) as num_games, 
                              count(DISTINCT box_score_lines.team_id) as num_teams'))
            ->join('games', 'games.id', '=', 'box_score_lines.game_id')
            ->join('seasons', 'seasons.id', '=', 'games.season_id')
            ->join('players', 'players.id', '=', 'box_score_lines.player_id')
            ->join('teams', 'teams.id', '=', 'box_score_lines.team_id')
            ->where('seasons.id', '=', $seasonId)
            ->where('status', '=', 'Played')
            ->groupBy('player_id')
            ->having('mpg', '>=', $mpgMax)
            ->having('fppg', '<=', $fppgMax)
            ->having('fppg', '>=', $fppgMin)
            ->having('num_games', '>=', 41)
            ->having('num_teams', '=', 1)
            ->get();
    }

    private function getTeams($seasonId) {
        $teams = Team::all();

        foreach ($teams as $team) {
            $team->num_games = DB::table('games')
                                    ->select(DB::raw('COUNT(*) as num_games'))
                                    ->join('seasons', 'seasons.id', '=', 'games.season_id')
                                    ->whereRaw('seasons.id = '.$seasonId.' 
                                                and (home_team_id = '.$team->id.' or road_team_id = '.$team->id.')')
                                    ->pluck('num_games');

            $games = DB::table('games')
                                    ->select('*')
                                    ->join('seasons', 'seasons.id', '=', 'games.season_id')
                                    ->whereRaw('seasons.id = '.$seasonId.' 
                                                and (home_team_id = '.$team->id.' or road_team_id = '.$team->id.')')
                                    ->get();

            $team->fppg = $this->getTeamFppg($team->num_games, $team->id, $seasonId);
            $team->ppg = $this->getTeamPpg($team->num_games, $games, $team->id);
            
            $team->multiplier = $team->fppg / $team->ppg;
        }

        return $teams;
    }

    private function getBoxScoreLines($seasonId, $absoluteSpread) {
        return BoxScoreLine::select(DB::raw('box_score_lines.id as box_score_line_id, 
                                              game_id, 
                                              season_id, 
                                              box_score_lines.player_id,
                                              players.name,
                                              box_score_lines.team_id,
                                              teams.abbr_br, 
                                              home_team_id,
                                              vegas_home_team_score,
                                              road_team_id, 
                                              vegas_road_team_score,
                                              '.$absoluteSpread.'(vegas_road_team_score - vegas_home_team_score) as absolute_spread,
                                              mp, 
                                              pts + (trb * 1.2) + (ast * 1.5) + (blk * 2) + (stl * 2) - tov as fpts'))
                    ->join('games', 'games.id', '=', 'box_score_lines.game_id')
                    ->join('seasons', 'seasons.id', '=', 'games.season_id')
                    ->join('players', 'players.id', '=', 'box_score_lines.player_id')
                    ->join('teams', 'teams.id', '=', 'box_score_lines.team_id')
                    ->where('seasons.id', '=', $seasonId)
                    ->where('status', '=', 'Played')
                    ->get()
                    ->toArray();
    }

    private function removeIneligiblePlayers($boxScoreLines, $eligiblePlayers) {
        foreach ($boxScoreLines as $key => $boxScoreLine) {
            $isPlayerEligible = $this->checkEligibilityOfPlayer($eligiblePlayers, $boxScoreLine);

            if (!$isPlayerEligible) { 
                unset($boxScoreLines[$key]);
            }
        }

        return $boxScoreLines;
    }

    private function addPlayerFptsErrorToBoxScoreLines($boxScoreLines, $eligiblePlayers, $teams) {
        foreach ($boxScoreLines as &$boxScoreLine) {
            $boxScoreLine = $this->addPlayerFptsErrorToBoxScoreLine($boxScoreLine, $eligiblePlayers, $teams);
        }

        unset($boxScoreLine);

        return $boxScoreLines;
    }

    private function addPlayerFptsErrorToBoxScoreLine($boxScoreLine, $eligiblePlayers, $teams) {
        $teamStats = $this->getTeamStats($teams, $boxScoreLine['team_id']);

        foreach ($eligiblePlayers as $eligiblePlayer) {
            if ($eligiblePlayer->player_id == $boxScoreLine['player_id']) {
                if ($boxScoreLine['team_id'] == $boxScoreLine['home_team_id']) {
                    $boxScoreLine['vegas_score_diff'] = ($boxScoreLine['vegas_home_team_score'] * $teamStats->multiplier) - $teamStats->fppg;
                }

                if ($boxScoreLine['team_id'] == $boxScoreLine['road_team_id']) {
                    $boxScoreLine['vegas_score_diff'] = ($boxScoreLine['vegas_road_team_score'] * $teamStats->multiplier) - $teamStats->fppg;
                }

                $boxScoreLine['vegas_modifier'] = ($boxScoreLine['vegas_score_diff'] / $teamStats->fppg);
                $boxScoreLine['projected_fpts'] = $eligiblePlayer->fppg * (1 + $boxScoreLine['vegas_modifier']);
                $boxScoreLine['player_fpts_diff'] = $boxScoreLine['fpts'] - $boxScoreLine['projected_fpts'];
                $boxScoreLine['player_fpts_error'] = $boxScoreLine['player_fpts_diff'] / $boxScoreLine['projected_fpts'];
            }
        }

        return $boxScoreLine;
    }

    private function getTeamStats($teams, $teamId) {
        foreach ($teams as $team) {
            if ($team->id == $teamId) {
                return $team;
            }
        }
    }

    private function getTeamFppg($numGames, $teamId, $seasonId) {
        $totalFpts = DB::table('games')
                        ->select(DB::raw('SUM(pts + (trb * 1.2) + (ast * 1.5) + (blk * 2) + (stl * 2) - tov) as total_fpts'))
                        ->join('seasons', 'seasons.id', '=', 'games.season_id')
                        ->join('box_score_lines', 'box_score_lines.game_id', '=', 'games.id')
                        ->whereRaw('seasons.id = '.$seasonId.' 
                                    and box_score_lines.team_id = '.$teamId)
                        ->pluck('total_fpts');

        return $totalFpts / $numGames;
    }

    private function getTeamPpg($numGames, $games, $teamId) {
        $totalPoints = 0;

        foreach ($games as $game) {
            $totalPoints += $this->getTeamScore($game, $teamId);
        }

        return $totalPoints / $numGames;
    }

    private function getTeamScore($game, $teamId) {
        if ($game->home_team_id == $teamId) {
            return $game->home_team_score;
        }

        return $game->road_team_score;
    }

    private function checkEligibilityOfPlayer($eligiblePlayers, $boxScoreLine) {
        foreach ($eligiblePlayers as $eligiblePlayer) {
            if ($boxScoreLine['player_id'] == $eligiblePlayer->player_id) {
                return true;
            }       
        }

        return false;
    }


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
            foreach ($vegasScores as $key => $vegasScore) {
                if ($player->team_name == $vegasScore['team']) {
                    $player->vegas_score_team = number_format(round($vegasScore['score'], 2), 2);

                    if ($key % 2 == 0) { // even or odd check         
                        $player->is_player_on_home_team = '';
                    } else {
                        $player->is_player_on_home_team = '<span style="color: #bbb" class="glyphicon glyphicon-home" aria-hidden="true"></span>';
                    }

                    $player->game_time = $vegasScore['time'];
                }

                if ($player->opp_team_name == $vegasScore['team']) {
                    $player->vegas_score_opp_team = number_format(round($vegasScore['score'], 2), 2);

                    if ($key % 2 == 0) { // even or odd check         
                        $player->is_player_on_road_team = '';
                    } else {
                        $player->is_player_on_road_team = '<span style="color: #bbb" class="glyphicon glyphicon-home" aria-hidden="true"></span>';
                    }
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

            $teamStats['team_offense'] = DB::table('seasons')
                                ->selectRaw('SUM(pts) as team_pts, 
                                    SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as team_fdpts, 
                                    SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) / SUM(pts) as team_multiplier')
                                ->join('games', 'games.season_id', '=', 'seasons.id')
                                ->join('box_score_lines', 'box_score_lines.game_id', '=', 'games.id')
                                ->where('seasons.id', '=', $seasonId)
                                ->where('box_score_lines.team_id', '=', $teamId)
                                ->where('games.date', '<', $date)
                                ->first();

            $teamStats['opp_team_defense'] = DB::table('seasons')
                                ->selectRaw('SUM(pts) as team_pts, 
                                    SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as team_fdpts, 
                                    SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) / SUM(pts) as team_multiplier')
                                ->join('games', 'games.season_id', '=', 'seasons.id')
                                ->join('box_score_lines', 'box_score_lines.game_id', '=', 'games.id')
                                ->where('seasons.id', '=', $seasonId)
                                ->where('box_score_lines.opp_team_id', '=', $teamsToday['opp_id'][$key])
                                ->where('games.date', '<', $date)
                                ->first();

            # ddAll($teamStats);

            $teamFilters[$key] = new \stdClass();
            $teamFilters[$key]->team_id = $teamId;
            $teamFilters[$key]->ppg = $teamStats['team_offense']->team_pts / $numGamesInCurrentSeason;
            $teamFilters[$key]->multiplier = 
                ($teamStats['team_offense']->team_multiplier + $teamStats['opp_team_defense']->team_multiplier) / 2;

            # ddAll($teamFilters);
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

        foreach ($teamFilters as &$teamFilter) {
            $teamFilter->fppg = $teamFilter->ppg * $teamFilter->multiplier;
        } unset($teamFilter);

        # ddAll($teamFilters);

        return $teamFilters;
    }

    public function addVegasFilterToPlayers($players, $teamFilters) {
        foreach ($players as &$player) {
            foreach ($teamFilters as $teamFilter) {
                if ($player->team_id == $teamFilter->team_id) {
                    $player->team_fppg = $teamFilter->fppg;

                    $player->vegas_filter = 
                        (($player->vegas_score_team * $teamFilter->multiplier) - $player->team_fppg) / $player->team_fppg;

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

                # ddAll(count($playerStats[$player->player_id]['cs']));

                // FPPG Source

                if (is_numeric($player->filter->fppg_source) ) {
                    $player->mp_mod = (($player->filter->fppg_source * count($playerStats[$player->player_id]['cs'])) - $player->filter->mp_ot_filter) / count($playerStats[$player->player_id]['cs']);
                } 

                if ($player->filter->fppg_source == 'mp cs') {
                    $player->mp_mod = calculateMpMod($playerStats[$player->player_id]['cs'], $player->filter->mp_ot_filter);
                }

                if (is_null($player->filter->fppg_source) ) {
                    $player->mp_mod = calculateMpMod($playerStats[$player->player_id]['all'], $player->filter->mp_ot_filter);
                }

                // No FPPM Source

                if (!isset($player->fppm) ) {
                    $player = calculateFppm($player, $playerStats[$player->player_id]['all']);
                }

                // No Mp Mod

                if (!isset($player->mp_mod)) {
                    ddAll($player);
                }
            } else {
                $player = calculateFppm($player, $playerStats[$player->player_id]['all']);
                $player->mp_mod = calculateMpMod($playerStats[$player->player_id]['all'], 0);
            }

            // FILTERS

            if (!isset($player->vegas_filter)) {
                $player->vegas_filter = 0;
            }

            $player->fppmWithVegasFilter = ($player->fppm * $player->vegas_filter) + $player->fppm;
            $player->fppgWithVegasFilter = numFormat($player->mp_mod * $player->fppmWithVegasFilter); // I need this for Line Filter and it must be numFormat

            if (!isset($player->line)) {
                $player->line = 0;
            }

            $player->fppmWithLineFilter = $this->getFppmBasedOnLineFilter($player, null);
            $player->fppgWithLineFilter = $player->mp_mod * $player->fppmWithLineFilter;

            // STATS IN VIEW

            $player->fppmTotalFilter = numFormat(($player->vegas_filter + $this->getFppmBasedOnLineFilter($player, 'line filter')) * 100, 2);
            
            $player->fppmWithAllFilters = numFormat($player->fppmWithLineFilter);
            $player->fppgWithAllFilters = numFormat($player->fppgWithLineFilter);

            $player->vr = numFormat($player->fppgWithAllFilters / ($player->salary / 1000));

            $player->svr = numFormat($this->calculateSvr($player->vr, $player->salary));

        } unset($player);

        # ddAll($players);

        return $players;
    }

    private function calculateSvr($vr, $salary) {
        $salaryDifferential = $salary - 6500;

        $salaryFilter = $salaryDifferential / 1000 / 100; // 1% for every 1000 salary

        return ($vr * $salaryFilter) + $vr;
    }

    private function getFppmBasedOnLineFilter($player, $doYouWantLineFilter) {
        // 1

        if ($player->fppgWithVegasFilter >= -100 && $player->fppgWithVegasFilter <= 15.74) {
            $absFilter = (abs($player->line) * 0.0099590105826463) + -0.062472771788925;
            $noAbsFilter = ($player->line * 0.0013742750195296) + -0.0013315510683428;

            $lineFilter = $absFilter + $noAbsFilter;
            if ($doYouWantLineFilter == 'line filter') { return $lineFilter; }

            return ($player->fppmWithVegasFilter * $lineFilter) + $player->fppmWithVegasFilter; 
        }

        // 2

        if ($player->fppgWithVegasFilter >= 15.75 && $player->fppgWithVegasFilter <= 17.73) {
            $absFilter = (abs($player->line) * 0.0022343456534535) + -0.011324016918622;
            $noAbsFilter = ($player->line * 0.0032427752122044) + 0.00055266055181689;

            $lineFilter = $absFilter + $noAbsFilter;
            if ($doYouWantLineFilter == 'line filter') { return $lineFilter; }

            return ($player->fppmWithVegasFilter * $lineFilter) + $player->fppmWithVegasFilter; 
        }

        // 3

        if ($player->fppgWithVegasFilter >= 17.74 && $player->fppgWithVegasFilter <= 19.41) {
            $absFilter = (abs($player->line) * 0.00099913884196103) + -0.003994736167979;
            $noAbsFilter = ($player->line * 0.0018291068082837) + 0.0018485630352972;

            $lineFilter = $absFilter + $noAbsFilter;
            if ($doYouWantLineFilter == 'line filter') { return $lineFilter; }

            return ($player->fppmWithVegasFilter * $lineFilter) + $player->fppmWithVegasFilter; 
        }

        // 4

        if ($player->fppgWithVegasFilter >= 19.42 && $player->fppgWithVegasFilter <= 21.26) {
            $absFilter = (abs($player->line) * -0.00021908540075569) + 0.0063423056940844;
            $noAbsFilter = ($player->line * 0.00023017987434186) + 0.0050057191903182;

            $lineFilter = $absFilter + $noAbsFilter;
            if ($doYouWantLineFilter == 'line filter') { return $lineFilter; }

            return ($player->fppmWithVegasFilter * $lineFilter) + $player->fppmWithVegasFilter; 
        }

        // 5

        if ($player->fppgWithVegasFilter >= 21.27 && $player->fppgWithVegasFilter <= 23.37) {
            $absFilter = (abs($player->line) * -0.00026201861927537) + 0.0043580611754587;
            $noAbsFilter = ($player->line * 0.001459366497387) + 0.0020222836072165;

            $lineFilter = $absFilter + $noAbsFilter;
            if ($doYouWantLineFilter == 'line filter') { return $lineFilter; }

            return ($player->fppmWithVegasFilter * $lineFilter) + $player->fppmWithVegasFilter; 
        }

        // 6

        if ($player->fppgWithVegasFilter >= 23.38 && $player->fppgWithVegasFilter <= 25.52) {
            $absFilter = (abs($player->line) * 0.00044376730269296) + -0.0014406736369231;
            $noAbsFilter = ($player->line * 0.00026090347075302) + 0.0011094746798654;

            $lineFilter = $absFilter + $noAbsFilter;
            if ($doYouWantLineFilter == 'line filter') { return $lineFilter; }

            return ($player->fppmWithVegasFilter * $lineFilter) + $player->fppmWithVegasFilter; 
        }

        // 7

        if ($player->fppgWithVegasFilter >= 25.53 && $player->fppgWithVegasFilter <= 28.23) {
            $absFilter = (abs($player->line) * -0.002142278124001) + 0.015956396110717;
            $noAbsFilter = ($player->line * -0.0000495151661039) + 0.0031850492840315;

            $lineFilter = $absFilter + $noAbsFilter;
            if ($doYouWantLineFilter == 'line filter') { return $lineFilter; }

            return ($player->fppmWithVegasFilter * $lineFilter) + $player->fppmWithVegasFilter; 
        }

        // 8

        if ($player->fppgWithVegasFilter >= 28.24 && $player->fppgWithVegasFilter <= 31.59) {
            $absFilter = (abs($player->line) * -0.0057115419949928) + 0.0357902689116409;
            $noAbsFilter = ($player->line * -0.0003854563889886) + 0.0012655362967571;

            $lineFilter = $absFilter + $noAbsFilter;
            if ($doYouWantLineFilter == 'line filter') { return $lineFilter; }

            return ($player->fppmWithVegasFilter * $lineFilter) + $player->fppmWithVegasFilter; 
        }

        // 9

        if ($player->fppgWithVegasFilter >= 31.60 && $player->fppgWithVegasFilter <= 35.46) {
            $absFilter = (abs($player->line) * -0.0044383003324907) + 0.0296787454732803;
            $noAbsFilter = ($player->line * -0.0000064391984030) + 0.0030693417404139;

            $lineFilter = $absFilter + $noAbsFilter;
            if ($doYouWantLineFilter == 'line filter') { return $lineFilter; }

            return ($player->fppmWithVegasFilter * $lineFilter) + $player->fppmWithVegasFilter; 
        }

        // 10

        if ($player->fppgWithVegasFilter >= 35.47 && $player->fppgWithVegasFilter <= 200) {
            $absFilter = (abs($player->line) * -0.0059866500620494) + 0.0406688399777451;
            $noAbsFilter = ($player->line * 0.0018768345361964) + 0.0086390450606912;

            $lineFilter = $absFilter + $noAbsFilter;
            if ($doYouWantLineFilter == 'line filter') { return $lineFilter; }

            return ($player->fppmWithVegasFilter * $lineFilter) + $player->fppmWithVegasFilter; 
        }
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