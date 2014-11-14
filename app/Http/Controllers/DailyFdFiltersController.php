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
	public function create()
	{
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		//
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

        # dd($dailyFdFilter);

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
