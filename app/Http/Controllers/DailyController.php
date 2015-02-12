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

	public function daily_fd_nba($date = 'default') {
		if ($date == 'default') {
            $date = getDefaultDate();

            return redirect('daily_fd_nba/'.$date);
		}

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

        // calculate stats

        foreach ($players as &$player) {
            if (isset($player->filter)) {
                if ($player->filter->filter == 1) {
                    if ($player->filter->fppg_source == 'fp cs') {
                        echo $player->name."'s filter should be changed from 'fp cs' to 'mp cs' and 'cs'.<br>";
                    }                    

                    if ($player->filter->fppg_source == 'mp cs') {
                        $player->mp_mod = calculateMpMod($playerStats[$player->player_id]['cs'], $player->filter->mp_ot_filter);

                        if ($player->filter->fppm_source == 'cs') {
                            $player = calculateFppm($player, $playerStats[$player->player_id]['cs']);
                        }
                    }

                    if (is_numeric($player->filter->fppg_source))  {
                        $player->mp_mod = $player->filter->fppg_source;
                    }

                    if (is_numeric($player->filter->cv_source)) {
                        $player->cv = $player->filter->cv_source;
                    }
                } 
            }

            // FPPG
            if ( !isset($player->fppgWithVegasFilter) ) {
                $player = calculateFppg($player, $playerStats[$player->player_id]['all']);
            }

            // FPPM

            if ( !isset($player->fppmWithVegasFilter) ) {
                $player = calculateFppm($player, $playerStats[$player->player_id]['all']);
            }

            if ( isset($player->filter->fppm_source) ) {
                if ($player->filter->fppm_source == 'cs') {
                    $player = calculateFppm($player, $playerStats[$player->player_id]['cs']);
                } 

                if ( is_numeric($player->filter->fppm_source) ) {
                    $player->fppm = $player->filter->fppm_source;
                    $player->fppmWithVegasFilter = numFormat(($player->fppm * $player->vegas_filter) + $player->fppm);               

                    if ( is_null($player->filter->fppg_source) ) {
                        $player->mp_mod = calculateMpMod($playerStats[$player->player_id]['all'], $date, $player->filter->mp_ot_filter);
                    }
                }
            }

            // MP MOD

            if (isset($player->mp_mod)) {
                $player->fppg = $player->mp_mod * $player->fppm;
                $player->fppgWithVegasFilter = numFormat(($player->fppg * $player->vegas_filter) + $player->fppg);                
            }
        } unset($player);

        foreach ($players as &$player) {
            $player->vr = numFormat( $player->fppgWithVegasFilter / ($player->salary / 1000) );
        } unset($player);

        // remove players that are not playing or DTD

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

        // order DTD players array by team

        if (isset($dtdPlayers)) {
            foreach ($dtdPlayers as $key => $row) {
	            $teamId[$key]  = $row->team_id;
	        }   

	        array_multisort($teamId, SORT_ASC, $dtdPlayers); 	
        } else {
        	$dtdPlayers = [];
        }

        # ddAll($players);

		return view('daily_fd_nba', compact('date', 'timePeriod', 'players', 'dtdPlayers', 'teamsToday'));
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