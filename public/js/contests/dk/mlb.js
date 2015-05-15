$(document).ready(function() {

	/****************************************************************************************
	CREATE TABLE
	****************************************************************************************/

	for (var i = 0; i < numOfContests; i++) {
		$('#contest'+i).dataTable({
			"scrollY": "600px",
			"paging": false,
			"order": [[3, "desc"]]
		});	

		// console.log(i);

		$('#contest'+i+'_filter').hide();
	};


	/****************************************************************************************
	RUN FILTERS
	****************************************************************************************/

	var position;
	var filter = {};

	function runFilter() {
		filter = getFilter();

		$('tr.player-row').removeClass('hide-player-row');

		runPositionFilter(filter);
	}

	function getFilter() {
		position = $('select.position-filter').val();

		filter = {
			position: position
		};

		return filter;
	}


	/********************************************
	RUN POSITION FILTER
	********************************************/

	$('select.position-filter').on('change', function() {
		runFilter();
	});

	function runPositionFilter(filter) {
		if (filter.position == 'All') {
			return;
		}

		$('tr.player-row').each(function() {
			var playerRow = $(this);

			hidePositionsNotSelected(playerRow, filter.position);
		});				
	}

	function hidePositionsNotSelected(playerRow, position) {
		var playerRowPosition = $(playerRow).data('position');

		if (playerRowPosition == position) {
			return;
		}

		$(playerRow).addClass('hide-player-row');
	}
	

});