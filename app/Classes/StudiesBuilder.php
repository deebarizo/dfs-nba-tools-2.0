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
use App\Models\MlbPlayer;
use App\Models\MlbTeam;
use App\Models\MlbPlayerTeam;
use App\Models\DkMlbPlayer;
use App\Models\DkMlbContest;
use App\Models\DkMlbContestLineup;
use App\Models\DkMlbContestLineupPlayer;

use App\Classes\Scraper;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

class StudiesBuilder {

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

}