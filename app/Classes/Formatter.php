<?php namespace App\Classes;

use App\Models\Team;

class Formatter {

    /****************************************************************************************
    NBA GAMES
    ****************************************************************************************/

	public function formatNbaGames($games) {
		$teams = Team::all();

		foreach ($games as $game) {
			$game = $this->addAbbrBr($teams, $game);

			$game->matchup = $game->road_team_abbr_br.'@'.$game->home_team_abbr_br;

			$game = $this->addNbaResult($game);

			$game = $this->addNbaLine($game);
		}

		ddAll($games);
	}

	private function addAbbrBr($teams, $game) {
		foreach ($teams as $team) {
			if ($game->home_team_id == $team->id) {
				$game->home_team_abbr_br = $team->abbr_br;

				break;
			}
		}

		foreach ($teams as $team) {
			if ($game->road_team_id == $team->id) {
				$game->road_team_abbr_br = $team->abbr_br;

				break;
			}
		}

		return $game;
	}

	private function addNbaResult($game) {
		if ($game->home_team_score > $game->road_team_score) {
			$game->result = $game->home_team_abbr_br.' '.$game->home_team_score.', '.$game->road_team_abbr_br.' '.$game->road_team_score;

			return $game;
		}

		if ($game->road_team_score > $game->home_team_score) {
			$game->result = $game->road_team_abbr_br.' '.$game->road_team_score.', '.$game->home_team_abbr_br.' '.$game->home_team_score;

			return $game;
		}
	}

	private function addNbaLine($game) {
		if ($game->home_team_score > $game->road_team_score) {
			$game->result = $game->home_team_abbr_br.' '.$game->home_team_score.', '.$game->road_team_abbr_br.' '.$game->road_team_score;

			return $game;
		}

		if ($game->road_team_score > $game->home_team_score) {
			$game->result = $game->road_team_abbr_br.' '.$game->road_team_score.', '.$game->home_team_abbr_br.' '.$game->home_team_score;

			return $game;
		}
	}	

}