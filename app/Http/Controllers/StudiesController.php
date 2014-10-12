<?php namespace App\Http\Controllers;

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

use App\Season;
use App\Team;
use App\Game;

use Illuminate\Http\Request;

class StudiesController {

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

		$correlation = calculateCorrelation($scores, $vegasScores, 'Scores', 'Vegas Scores');

		return view('studies/correlation_scores_and_vegas_scores', compact('correlation'));
	}
	
}