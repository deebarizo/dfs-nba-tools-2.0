<?php namespace App\Http\Controllers;

use App\Season;
use App\Team;
use App\Game;
use App\Player;
use App\BoxScoreLine;
use App\PlayerPool;
use App\PlayerFd;
use App\DailyFdFilter;

use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Session;

date_default_timezone_set('America/Chicago');

class DailyFdFiltersController {

	private $dailyFdFilter;

	public function __construct(DailyFdFilter $dailyFdFilter) {
		$this->daily_fd_filter = $dailyFdFilter;
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		//
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create($player_id)
	{
		$player = DB::table('players')
            ->select('*')
            ->whereRaw('id = '.$player_id)
            ->orderBy('created_at', 'desc')
            ->get();	

        $dailyFdFilter = DB::select('SELECT t1.* FROM daily_fd_filters AS t1
                                         JOIN (
                                            SELECT player_id, MAX(created_at) AS latest FROM daily_fd_filters GROUP BY player_id
                                         ) AS t2
                                         ON t1.player_id = t2.player_id AND t1.created_at = t2.latest
                                         where t1.player_id = '.$player_id);

        if (empty($dailyFdFilter)) {
        	$playerFilter['playing'] = 1;
        	$playerFilter['fppg_source'] = null;
        	$playerFilter['fppm_source'] = null;
        	$playerFilter['cv_source'] = null;
        	$playerFilter['mp_ot_filter'] = 0;
        	$playerFilter['dnp_games'] = 0;
        	$playerFilter['notes'] = null;
        } else {
        	$playerFilter['playing'] = $dailyFdFilter[0]->playing;
        	$playerFilter['fppg_source'] = $dailyFdFilter[0]->fppg_source;
        	$playerFilter['fppm_source'] = $dailyFdFilter[0]->fppm_source;
        	$playerFilter['cv_source'] = $dailyFdFilter[0]->cv_source;;
        	$playerFilter['mp_ot_filter'] = $dailyFdFilter[0]->mp_ot_filter;
        	$playerFilter['dnp_games'] = $dailyFdFilter[0]->dnp_games;
        	$playerFilter['notes'] = $dailyFdFilter[0]->notes;
        }

		return view('daily_fd_filters/create', compact('player', 'playerFilter'));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(Request $request)
	{
		# dd($request);

		$dailyFdFilter = new DailyFdFilter;

		$dailyFdFilter->player_id = $request->get('player_id');
		$dailyFdFilter->filter = $request->get('filter');
		$dailyFdFilter->playing = $request->get('playing');
		$dailyFdFilter->fppg_source = (trim($request->get('fppg_source')) == '' ? null : trim($request->get('fppg_source')));
		$dailyFdFilter->fppm_source = (trim($request->get('fppm_source')) == '' ? null : trim($request->get('fppm_source')));
		$dailyFdFilter->cv_source = (trim($request->get('cv_source')) == '' ? null : trim($request->get('cv_source')));
		$dailyFdFilter->mp_ot_filter = $request->get('mp_ot_filter');
		$dailyFdFilter->dnp_games = $request->get('dnp_games');
		$dailyFdFilter->notes = (trim($request->get('notes')) == '' ? null : trim($request->get('notes')));

		$dailyFdFilter->save();

		$message = 'Success!';
		Session::flash('alert', 'info');

		return redirect('daily_fd_filters/'.$dailyFdFilter->player_id.'/edit')->with('message', $message);		
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		//
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($player_id)
	{
		$dailyFdFilter = 
			$this->daily_fd_filter->where('player_id', $player_id)->orderBy('created_at', 'desc')->first();
		
		$player = DB::table('players')
            ->select('*')
            ->whereRaw('id = '.$player_id)
            ->orderBy('created_at', 'desc')
            ->get();	

		return view('daily_fd_filters/edit', compact('dailyFdFilter', 'player'));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id, Request $request)
	{
		$dailyFdFilter = $this->daily_fd_filter->where('id', $id)->first();

		$dailyFdFilter->filter = $request->get('filter');
		$dailyFdFilter->playing = $request->get('playing');
		$dailyFdFilter->fppg_source = (trim($request->get('fppg_source')) == '' ? null : trim($request->get('fppg_source')));
		$dailyFdFilter->fppm_source = (trim($request->get('fppm_source')) == '' ? null : trim($request->get('fppm_source')));
		$dailyFdFilter->cv_source = (trim($request->get('cv_source')) == '' ? null : trim($request->get('cv_source')));
		$dailyFdFilter->mp_ot_filter = $request->get('mp_ot_filter');
		$dailyFdFilter->dnp_games = $request->get('dnp_games');
		$dailyFdFilter->notes = (trim($request->get('notes')) == '' ? null : trim($request->get('notes')));

		$dailyFdFilter->save();

		$message = 'Success!';
		Session::flash('alert', 'info');

		return redirect('daily_fd_filters/'.$dailyFdFilter->player_id.'/edit')->with('message', $message);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}

}
