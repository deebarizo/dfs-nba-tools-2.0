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

use App\Models\Lineup;
use App\Models\LineupPlayer;
use App\Models\DefaultLineupBuyIn;

use App\Classes\Solver;
use App\Classes\SolverTopPlays;
use App\Classes\LineupBuilder;
use App\Classes\Formatter;

use Illuminate\Http\Request;
use App\Http\Requests\RunFDNBASalariesScraperRequest;

use Illuminate\Support\Facades\DB;

use vendor\symfony\DomCrawler\Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

use Illuminate\Support\Facades\Session;

class TeamsController {

    public function showNbaTeams() {
        $teams = Team::all();

        return view('teams/nba', compact('teams'));
    }

	public function getTeamStats($abbr_br) {
        $teamId = Team::where('abbr_br', $abbr_br)->pluck('id');

        $games = Game::where('home_team_id', $teamId)->orWhere('road_team_id', $teamId)->orderBy('date', 'desc')->take(100)->get();

        $formatter = new Formatter;

        $games = $formatter->formatNbaGames($games);

        $endYear = 2015;

        $name = Team::where('abbr_br', '=', $abbr_br)->pluck('name_br');
        $teamId = Team::where('abbr_br', '=', $abbr_br)->pluck('id');

        $allTeams = DB::table('seasons')
                ->selectRaw('SUM((fg - threep) * 2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as twopfp,
					SUM(threep * 3) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as threepfp,
					SUM(ft) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as ftpfp,
					SUM(trb * 1.2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as trbpfp,
					SUM(orb * 1.2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as orbpfp,
					SUM(drb * 1.2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as drbpfp,
					SUM(ast * 1.5) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as astpfp,
					SUM(tov) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * -1 as tovpfp,
					SUM(stl * 2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as stlpfp,
					SUM(blk * 2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as blkpfp')
                ->join('games', 'games.season_id', '=', 'seasons.id')
                ->join('box_score_lines', 'box_score_lines.game_id', '=', 'games.id')
                ->where('box_score_lines.status', '=', 'Played')
                ->where('seasons.end_year', '=', $endYear)
                ->first();

        $thisTeam = DB::table('seasons')
                ->selectRaw('SUM((fg - threep) * 2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as twopfp,
					SUM(threep * 3) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as threepfp,
					SUM(ft) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as ftpfp,
					SUM(trb * 1.2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as trbpfp,
					SUM(orb * 1.2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as orbpfp,
					SUM(drb * 1.2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as drbpfp,
					SUM(ast * 1.5) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as astpfp,
					SUM(tov) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) * -1 as tovpfp,
					SUM(stl * 2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as stlpfp,
					SUM(blk * 2) / SUM(pts+(trb*1.2)+(ast*1.5)+(stl*2)+(blk*2)-tov) as blkpfp')
                ->join('games', 'games.season_id', '=', 'seasons.id')
                ->join('box_score_lines', 'box_score_lines.game_id', '=', 'games.id')
                ->where('box_score_lines.status', '=', 'Played')
                ->where('seasons.end_year', '=', $endYear)
                ->where('box_score_lines.opp_team_id', '=', $teamId)
                ->first();

        $thisTeamPercentages = [];

        foreach ($thisTeam as $statName => $statValue) {
        	$result = ($statValue - $allTeams->$statName) / abs($allTeams->$statName) * 100; // abs is for turnovers
        	$thisTeamPercentages[] = (float)($result);
        }

        # dd($thisTeamPercentages);

        return view('teams/nba_games', compact('games', 'endYear', 'name', 'thisTeamPercentages'));
	}

}