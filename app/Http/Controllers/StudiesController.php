<?php namespace App\Http\Controllers;

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

use App\Models\Season;
use App\Models\Team;
use App\Models\Game;
use App\Models\BoxScoreLine;

use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class StudiesController {

	private $seasonStartYear = [
		'earliest' => 2012,
		'latest' => 2015
	];

	/****************************************************************************************
	CORRELATION: SPREADS AND PLAYER FPTS ERROR
	****************************************************************************************/	

	public function correlationSpreadsAndPlayerFptsError($mpgMax, $fppgMax, $fppgMin, $absoluteSpread) {
		if ($absoluteSpread == 'NOABS') {
			$absoluteSpread = '';
			
			$xMin = -25;
		}

		if ($absoluteSpread == 'ABS') {
			$xMin = 0;
		}

		$seasons = Season::where('start_year', '>=', $this->seasonStartYear['earliest'])
					->where('end_year', '<=', $this->seasonStartYear['latest'])
					->get()
					->toArray();

		foreach ($seasons as &$season) { 
			$season['eligible_players'] = $this->getEligiblePlayers($season['id'], $mpgMax, $fppgMax, $fppgMin);
			$season['teams'] = $this->getTeams($season['id']);

			$boxScoreLines = $this->getBoxScoreLines($season['id'], $absoluteSpread);
			$boxScoreLines = $this->removeIneligiblePlayers($boxScoreLines, $season['eligible_players']);
			$season['box_score_lines'] = $this->addPlayerFptsErrorToBoxScoreLines($boxScoreLines, $season['eligible_players'], $season['teams']);
		}

		unset($season);

		$spreads = [];
		$playerFptsError = [];

		foreach ($seasons as $season) {
			foreach ($season['box_score_lines'] as $boxScoreLine) {
				$playerFptsError[] = $boxScoreLine['player_fpts_error'];

				if ($absoluteSpread == 'ABS') {
					$spreads[] = $boxScoreLine['absolute_spread'];
				}
				
				if ($boxScoreLine['team_id'] == $boxScoreLine['home_team_id'] && $absoluteSpread == '') {
					$spreads[] = $boxScoreLine['absolute_spread'];

					continue;
				}
				
				if ($boxScoreLine['team_id'] == $boxScoreLine['road_team_id'] && $absoluteSpread == '') {
					$spreads[] = $boxScoreLine['absolute_spread'] * -1;

					continue;
				}
			}
		}

		$data = calculateCorrelation($spreads, $playerFptsError, 'Spreads', 'Player Fpts Error');

		# ddAll($data);

		for ($x = $xMin; $x <= 25 ; $x++) { 
			$y = ($data['bOne'] * $x) + $data['bNaught'];
			$lineOfBestFitJSON[] = [$x, $y];
		}

		$data['lineOfBestFitJSON'] = $lineOfBestFitJSON;

		$perfectLineJSON = [];

		for ($x = $xMin; $x <= 25 ; $x++) { 
			$y = $x * 2;
			$perfectLineJSON[] = [$x, $y];
		}

		$data['perfectLineJSON'] = $perfectLineJSON;

		$data['subhead1'] = 'Calculate Player Fpts Error:';
		$data['subhead2'] = '(Absolute Spread * '.$data['bOne'].') + '.$data['bNaught']; 

		return view('studies/correlation_spreads_and_player_fpts_error', compact('data'));		
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

	private function removeIneligiblePlayers($boxScoreLines, $eligiblePlayers) {
		foreach ($boxScoreLines as $key => $boxScoreLine) {
			$isPlayerEligible = $this->checkEligibilityOfPlayer($eligiblePlayers, $boxScoreLine);

			if (!$isPlayerEligible) { 
				unset($boxScoreLines[$key]);
			}
		}

		return $boxScoreLines;
	}

	private function checkEligibilityOfPlayer($eligiblePlayers, $boxScoreLine) {
		foreach ($eligiblePlayers as $eligiblePlayer) {
			if ($boxScoreLine['player_id'] == $eligiblePlayer->player_id) {
				return true;
			}		
		}

		return false;
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
			->having('fppg', '<', $fppgMax)
			->having('fppg', '>=', $fppgMin)
			// ->having('fppg', '<', 36)
			// ->having('fppg', '>=', 30)
			// ->having('fppg', '<', 30)
			// ->having('fppg', '>=', 25)
			// ->having('fppg', '<', 25)
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


	/****************************************************************************************
	CORRELATION: SCORES AND FD SCORES
	****************************************************************************************/

	public function correlationScoresAndFDScores() {
		$rawData = DB::table('box_score_lines')->select(DB::raw('SUM(pts) as score, SUM(pts + (trb * 1.2) + (ast * 1.5) + (blk * 2) + (stl * 2) - tov) as fd_score'))->groupBy('game_id', 'team_id')->get();

		$scores = [];

		foreach ($rawData as $key => $value) {
			$scores[$key] = $value->score;
		}

		$fdScores = [];

		foreach ($rawData as $key => $value) {
			$fdScores[$key] = $value->fd_score;
		}

		$data = calculateCorrelation($scores, $fdScores, 'Scores', 'FD Scores');

		for ($x=50; $x <= 150 ; $x++) { 
			$y = ($data['bOne'] * $x) + $data['bNaught'];
			$lineOfBestFitJSON[] = [$x, $y];
		}

		$data['lineOfBestFitJSON'] = $lineOfBestFitJSON;

		$perfectLineJSON = [];

		for ($x=50; $x <= 150 ; $x++) { 
			$y = $x * 2;
			$perfectLineJSON[] = [$x, $y];
		}

		$data['perfectLineJSON'] = $perfectLineJSON;

		$data['calculatePredictedFDScore'] = '(Score * '.$data['bOne'].') + '.$data['bNaught']; 

		return view('studies/correlation_scores_and_fd_scores', compact('data'));
	}


	/****************************************************************************************
	HISTOGRAM: SCORES
	****************************************************************************************/

	public function histogramScores() {
		$teamScores['home_team_score'] = Game::all(['home_team_score'])->toArray();
		$teamScores['road_team_score'] = Game::all(['road_team_score'])->toArray();

		$scores = [];

		foreach ($teamScores as $key => $location) {
			foreach ($location as $teamScore) {
				$scores[] = $teamScore[$key]; 
			}
		}

		sort($scores);

		$histogram = [];

		$lowestScore = $scores[0];
		$highestScore = $scores[count($scores) - 1];

		for ($i = $lowestScore; $i <= $highestScore; $i++) { 
			$histogram[] = [$i, 0];

			if (count($scores) > 0) {
				foreach ($scores as $index => $score) {
					if ($score == $i) {
						foreach ($histogram as &$array) {
							if ($score == $array[0]) {
								$array[1]++;

								break;
							}
						}

						unset($array);

						unset($scores[$index]);
					}
				}				
			}
		}

		return view('studies/histogram_scores', compact('histogram'));
	}


	/****************************************************************************************
	CORRELATION: SCORES AND VEGAS SCORES
	****************************************************************************************/

	public function correlationScoresAndVegasScores() {
		$teamScores['home_team_score'] = Game::all(['home_team_score'])->toArray();
		$teamScores['road_team_score'] = Game::all(['road_team_score'])->toArray();

		$scores = [];

		foreach ($teamScores as $key => $location) {
			foreach ($location as $teamScore) {
				$scores[] = $teamScore[$key]; 
			}
		}

		$vegasTeamScores['vegas_home_team_score'] = Game::all(['vegas_home_team_score'])->toArray();
		$vegasTeamScores['vegas_road_team_score'] = Game::all(['vegas_road_team_score'])->toArray();

		$vegasScores = [];

		foreach ($vegasTeamScores as $key => $location) {
			foreach ($location as $vegasTeamScore) {
				$vegasScores[] = $vegasTeamScore[$key]; 
			}
		}

		$data = calculateCorrelation($scores, $vegasScores, 'Scores', 'Vegas Scores');

		for ($x=40; $x <= 150 ; $x++) { 
			$y = ($data['bOne'] * $x) + $data['bNaught'];
			$lineOfBestFitJSON[] = [$x, $y];
		}

		$data['lineOfBestFitJSON'] = $lineOfBestFitJSON;

		$perfectLineJSON = [];

		for ($x=40; $x <= 150 ; $x++) { 
			$y = $x;
			$perfectLineJSON[] = [$x, $y];
		}

		$data['perfectLineJSON'] = $perfectLineJSON;

		$data['calculatePredictedScore'] = '(Vegas Score - '.$data['bNaught'].') / '.$data['bOne']; 

		return view('studies/correlation_scores_and_vegas_scores', compact('data'));
	}
	
}