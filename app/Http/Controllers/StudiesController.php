<?php namespace App\Http\Controllers;

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

use App\Season;
use App\Team;
use App\Game;

use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class StudiesController {

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

		return view('studies/correlation_scores_and_fd_scores', compact('data'));
	}

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