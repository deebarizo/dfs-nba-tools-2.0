<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Game extends Eloquent {
	protected $guarded = array('id');

	public function season() {
		return $this->belongsTo('Season');
	}

}