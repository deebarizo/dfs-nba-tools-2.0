<?php namespace App\Http\Controllers;

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
use App\Models\DKMlbContest;

use App\Classes\StatBuilder;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

date_default_timezone_set('America/Chicago');

class DailyController {

    /****************************************************************************************
    DAILY
    ****************************************************************************************/

	public function showDaily($site, $sport, $timePeriod, $date, $contestId) {

        /****************************************************************************************
        DAILY FD NBA
        ****************************************************************************************/

        if ($site == 'fd' && $sport == 'nba') {
            $teams = Team::all();

            $statBuilder = new StatBuilder;

            $players = $statBuilder->getPlayersInPlayerPool($site, $sport, $timePeriod, $date);

            $timePeriod = $players[0]->time_period;

            $players = $statBuilder->matchPlayersToTeams($players, $teams);

            $teamsToday = $statBuilder->getTeamsToday($players, $teams);

            $players = $statBuilder->matchPlayersToFilters($players);

            $client = new Client;
            $vegasScores = scrapeForOdds($client, $date);

            $seasonId = 12;

            if ($vegasScores != 'No lines yet.') {
                $players = $statBuilder->addVegasInfoToPlayers($players, $vegasScores);

                $teamFilters = $statBuilder->getTeamFilters($teamsToday, $date, $seasonId);

                $players = $statBuilder->addVegasFilterToPlayers($players, $teamFilters);

                $areThereVegasScores = true;       
            }

            if ($vegasScores == 'No lines yet.') {
                foreach ($players as &$player) {   
                    $player->vegas_filter = 0;
                }

                $areThereVegasScores = false;
            } unset($player);

            $playerStats = $statBuilder->getBoxScoreLinesOfPlayers($players, $date, $seasonId);

            # ddAll($players);

            $players = $statBuilder->generateProjections($players, $playerStats);

            $gameTimes = [];

            if ($vegasScores == 'No lines yet.') {
                $gameTimes[] = 'No lines yet.';
            } else {
                foreach ($vegasScores as $vegasScore) {
                    $gameTimes[] = $vegasScore['time'];
                }
                $gameTimes = array_unique($gameTimes);
            }
        
            # ddAll($players);

    		return view('daily/fd/nba', compact('date', 'timePeriod', 'players', 'teamsToday', 'gameTimes'));
        }

        /****************************************************************************************
        DAILY DK MLB
        ****************************************************************************************/

        if ($site == 'dk' && $sport == 'mlb') {
            $statBuilder = new StatBuilder;

            $timePeriodInUrl = $timePeriod;
            $timePeriod = urlToUcWords($timePeriod);

            $players = $statBuilder->getPlayersForDkMlbDaily($timePeriod, $date, $contestId);
            $teams = $statBuilder->getTeamsForDkMlbDaily($timePeriod, $date);

            if (isset($players[0]->are_there_box_score_lines)) {
                $areThereBoxScoreLines = 1;

                $tableSize = '75%';
            } else {
                $areThereBoxScoreLines = 0;

                $tableSize = '85%';
            }

            if ($contestId != 'nc') {
                $contestName = DkMlbContest::where('id', $contestId)->pluck('name');
            } else {
                $contestName = 'None';
            }

            return view('daily/dk/mlb', compact('date', 
                                                'timePeriodInUrl', 
                                                'timePeriod', 
                                                'players', 
                                                'teams', 
                                                'areThereBoxScoreLines', 
                                                'tableSize',
                                                'contestName'));
        }
	}


    /****************************************************************************************
    DAILY FD NBA AJAX
    ****************************************************************************************/

    public function update_top_plays($playerFdIndex, $isPlayerActive) {
        $playerFd = PlayerFd::find($playerFdIndex);

        if ($isPlayerActive === 'true') {
            $playerFd->top_play_index = 0;
        } else {
            $playerFd->top_play_index = 1;
        }

        $playerFd->save();
    }

    public function updateTargetPercentage($playerFdIndex, $newTargetPercentage) {
        $playerFd = PlayerFd::find($playerFdIndex);

        $playerFd->target_percentage = $newTargetPercentage;

        if ($newTargetPercentage > 0) {
            $playerFd->top_play_index = 1;
        } else {
            $playerFd->top_play_index = 0;
        }

        $playerFd->save();
    }


    /****************************************************************************************
    DAILY DK MLB AJAX
    ****************************************************************************************/

    public function updateTargetPercentageForDkMlb(Request $request) {
        $dkMlbPlayersId = $request->input('dkMlbPlayersId');
        $targetPercentage = $request->input('targetPercentage');

        $dkMlbPlayer = DkMlbPlayer::find($dkMlbPlayersId);

        $dkMlbPlayer->target_percentage = $targetPercentage;

        $dkMlbPlayer->save();
    }

}