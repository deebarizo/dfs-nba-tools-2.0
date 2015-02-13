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

use App\Classes\StatBuilder;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

date_default_timezone_set('America/Chicago');

class DailyController {

	public function daily_fd_nba($date) {
        $boxScoreLines = BoxScoreLine::skip(78000)->take(10000)->get()->toArray();

        # ddAll($boxScoreLines);

        $count = 0;

        foreach ($boxScoreLines as $boxScoreLine) {
            if ($boxScoreLine['opp_team_id'] == 0) {
                $boxScoreLineWithOppTeam = DB::table('box_score_lines')
                                                ->where('game_id', '=', $boxScoreLine['game_id'])
                                                ->where('team_id', '!=', $boxScoreLine['team_id'])
                                                ->first();

                $oppTeamId = $boxScoreLineWithOppTeam->team_id;

                # ddAll($oppTeamId);

                DB::table('box_score_lines')->where('game_id', '=', $boxScoreLine['game_id'])
                                            ->where('team_id', '!=', $oppTeamId)
                                            ->update(array('opp_team_id' => $oppTeamId));

                $count++;
            }
        }



        echo $count.' done'; exit();

        $teams = Team::all();

        $statBuilder = new StatBuilder;

        $players = $statBuilder->getPlayersInPlayerPool($date);

        $timePeriod = $statBuilder->getTimePeriodOfPlayerPool($players);

        $players = $statBuilder->matchPlayersToTeams($players, $teams);

        $teamsToday = $statBuilder->getTeamsToday($players, $teams);

        $players = $statBuilder->matchPlayersToFilters($players);

        $client = new Client;
        $vegasScores = scrapeForOdds($client, $date);

        if ($vegasScores != 'No lines yet.') {
            $players = $statBuilder->addVegasInfoToPlayers($players, $vegasScores);

            $teamFilters = $statBuilder->getTeamFilters($teamsToday, $date);

            $players = $statBuilder->addVegasFilterToPlayers($players, $teamFilters);

            $areThereVegasScores = true;       
        }

        if ($vegasScores == 'No lines yet.') {
            foreach ($players as &$player) {   
                $player->vegas_filter = 0;
            }

            $areThereVegasScores = false;
        } unset($player);

        $playerStats = $statBuilder->getBoxScoreLinesOfPlayers($players, $date);

        $players = $statBuilder->generateProjections($players, $playerStats);

        $players = $statBuilder->removeInactivePlayers($players);

        # ddAll($players);

		return view('daily_fd_nba', compact('date', 'timePeriod', 'players', 'teamsToday'));
	}

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

        $playerFd->save();
    }

}