<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class DailyFdFilter extends Eloquent {

	protected $guarded = array('id');

	public function dailyFdFilter() {
		return $this->belongsTo('Player');
	}
	
}