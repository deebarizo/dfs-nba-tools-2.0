<?php namespace App\Http\Controllers;

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

use App\Season;
use App\Team;
use App\Game;

use Illuminate\Http\Request;

class StudiesController {

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

		return view('studies/correlation_scores_and_vegas_scores', compact('data'));
	}
	
}