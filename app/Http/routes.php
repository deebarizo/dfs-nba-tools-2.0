<?php

ini_set('max_execution_time', 10800); // 10800 seconds = 3 hours

$router->get('/', 'PlayerPoolsController@home');

/****************************************************************************************
SCRAPERS
****************************************************************************************/

$router->post('scrapers/br_nba_box_score_lines', 'ScrapersController@br_nba_box_score_lines');
$router->post('scrapers/br_nba_games', 'ScrapersController@br_nba_games');
$router->post('scrapers/fd_nba_salaries', 'ScrapersController@fd_nba_salaries');
$router->post('scrapers/dk_mlb_salaries', 'ScrapersController@dk_mlb_salaries');


/****************************************************************************************
STUDIES
****************************************************************************************/

$router->get('studies/correlations/scores_and_vegas_scores', 'StudiesController@correlationScoresAndVegasScores');
$router->get('studies/histograms/scores', 'StudiesController@histogramScores');
$router->get('studies/correlations/scores_and_fd_scores', 'StudiesController@correlationScoresAndFDScores');
$router->get('studies/correlations/spreads_and_player_fpts_error/{mpgMax}/{fppgMax}/{fppgMin}/{absoluteSpread}', 'StudiesController@correlationSpreadsAndPlayerFptsError');
$router->get('studies/general/classifying_projected_fpts/', 'StudiesController@classifyingProjectedFpts');


/****************************************************************************************
PLAYERS
****************************************************************************************/

$router->get('players/{player_id}', 'PlayersController@getPlayerStats');


/****************************************************************************************
DAILY
****************************************************************************************/

$router->get('daily/{site}/{sport}/{timePeriod}/{date}', 'DailyController@daily');

$router->post('daily_fd_nba/update_top_plays/{playerFdIndex}/{isPlayerActive}', 'DailyController@update_top_plays');
$router->post('daily_fd_nba/update_target_percentage/{playerFdIndex}/{newTargetPercentage}', 'DailyController@updateTargetPercentage');

$router->post('daily/dk/mlb/update_target_percentage_for_dk_mlb', 'DailyController@updateTargetPercentageForDkMlb');


/****************************************************************************************
STATIC PAGES
****************************************************************************************/

$router->get('scrapers/br_nba_box_score_lines', function() {
	return View::make('scrapers/br_nba_box_score_lines');
});
$router->get('scrapers/br_nba_games', function() {
	return View::make('scrapers/br_nba_games');
});
$router->get('scrapers/fd_nba_salaries', function() {
	return View::make('scrapers/fd_nba_salaries');
});
$router->get('scrapers/dk_mlb_salaries', function() {
	return View::make('scrapers/dk_mlb_salaries');
});

$router->get('scrapers', function() {
	return View::make('pages/scrapers');
});
$router->get('studies', function() {
	return View::make('pages/studies');
});
$router->get('player_search', function() {
	return View::make('player_search');
});


/****************************************************************************************
SOLVER NBA
****************************************************************************************/

$router->get('solver_fd_nba', 'SolverFdNbaController@solverFdNba');
$router->get('solver_fd_nba/{date}', 'SolverFdNbaController@solverFdNba');
$router->get('solver_fd_nba/{date}/{numTopLineups}', 'SolverFdNbaController@solverFdNba');


/****************************************************************************************
SOLVER TOP PLAYS (NBA)
****************************************************************************************/

$router->get('solver_with_top_plays_fd_nba/', 'SolverFdNbaController@solver_with_top_plays');
$router->get('solver_with_top_plays_fd_nba/{date}', 'SolverFdNbaController@solver_with_top_plays');
$router->post('solver_top_plays/update_buy_in/{playerPoolId}/{buyIn}', 'SolverFdNbaController@updateBuyIn');
$router->post('solver_top_plays/add_default_lineup_buy_in/{addDefaultLineupBuyIn}', 'SolverFdNbaController@addDefaultLineupBuyIn');
$router->post('solver_top_plays/add_or_remove_lineup/', 'SolverFdNbaController@addOrRemoveLineup'); 
$router->post('solver_top_plays/update_lineup_buy_in/{playerPoolId}/{hash}/{lineupBuyIn}', 'SolverFdNbaController@updateLineupBuyIn');
$router->post('solver_top_plays/play_or_unplay_lineup/', 'SolverFdNbaController@playOrUnplayLineup'); 


/****************************************************************************************
SOLVER TOP PLAYS (MLB)
****************************************************************************************/

$router->get('solver_top_plays/{siteInUrl}/mlb/{timePeriodInUrl}/{date}', 'SolverTopPlaysMlbController@solverTopPlaysMlb');


/****************************************************************************************
FILTERS
****************************************************************************************/

$router->resource('daily_fd_filters', 'DailyFdFiltersController', ['except' => ['create']]);
$router->get('daily_fd_filters/{player_id}/create', 'DailyFdFiltersController@create');
$router->get('daily_fd_filters/{player_id}/create/{dailyFdFilterId}', 'DailyFdFiltersController@create');


/****************************************************************************************
LINEUP BUILDER
****************************************************************************************/

$router->get('lineup_builder/', 'LineupBuilderController@showActiveLineups');
$router->get('lineup_builder/{date}', 'LineupBuilderController@showActiveLineups');
$router->get('lineup_builder/{date}/create', 'LineupBuilderController@createLineup');
$router->get('lineup_builder/{date}/create/{hash}', 'LineupBuilderController@createLineup');


/****************************************************************************************
MISC
****************************************************************************************/

$router->get('get_player_name_autocomplete', 'PlayersController@getPlayerNameAutocomplete');

$router->get('teams/{abbr_br}', 'TeamsController@getTeamStats');

$router->get('nbawowy/', 'NbawowyController@nbawowy_form');
$router->get('nbawowy/{name}/{startDate}/{endDate}/on/{playerOn}/off/{playerOff}/{team}', 'NbawowyController@nbawowy');

$router->get('one-of', 'OneOfController@run');