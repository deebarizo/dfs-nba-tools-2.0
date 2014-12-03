<?php

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

$router->post('scrapers/br_nba_box_score_lines', 'ScrapersController@br_nba_box_score_lines');
$router->post('scrapers/br_nba_games', 'ScrapersController@br_nba_games');
$router->post('scrapers/fd_nba_salaries', 'ScrapersController@fd_nba_salaries');

$router->get('studies/correlations/scores_and_vegas_scores', 'StudiesController@correlationScoresAndVegasScores');
$router->get('studies/histograms/scores', 'StudiesController@histogramScores');
$router->get('studies/correlations/scores_and_fd_scores', 'StudiesController@correlationScoresAndFDScores');

$router->get('players/{player_id}', 'PlayersController@getPlayerStats');

$router->get('daily_fd_nba', 'DailyController@daily_fd_nba');
$router->get('daily_fd_nba/{date}', 'DailyController@daily_fd_nba');
$router->post('daily_fd_nba/update_top_plays/{playerFdIndex}/{isPlayerActive}', 'DailyController@update_top_plays');

$router->get('scrapers/br_nba_box_score_lines', function() {
	return View::make('scrapers/br_nba_box_score_lines');
});
$router->get('scrapers/br_nba_games', function() {
	return View::make('scrapers/br_nba_games');
});
$router->get('scrapers/fd_nba_salaries', function() {
	return View::make('scrapers/fd_nba_salaries');
});

$router->get('scrapers', function() {
	return View::make('pages/scrapers');
});
$router->get('studies', function() {
	return View::make('pages/studies');
});
$router->get('/', function() {
	return View::make('pages/home');
});

$router->get('solver_fd_nba', 'SolverFdNbaController@solverFdNba');
$router->get('solver_fd_nba/{date}', 'SolverFdNbaController@solverFdNba');
$router->get('solver_fd_nba/{date}/{numTopLineups}', 'SolverFdNbaController@solverFdNba');
$router->get('solver_with_top_plays_fd_nba/{date}', 'SolverFdNbaController@solver_with_top_plays');

$router->resource('daily_fd_filters', 'DailyFdFiltersController', ['except' => ['create']]);
$router->get('daily_fd_filters/{player_id}/create', 'DailyFdFiltersController@create');
$router->get('daily_fd_filters/{player_id}/create/{dailyFdFilterId}', 'DailyFdFiltersController@create');

$router->get('one_time', 'ScrapersController@one_time');
