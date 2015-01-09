$(document).ready(function() {

	/****************************************************************************************
	CREATE TABLE
	****************************************************************************************/
	
	$('#available-players').dataTable({
		"scrollY": "600px",
		"paging": false,
		"order": [[2, "desc"]]
	});

	$('#available-players_filter').hide();


	/****************************************************************************************
	UPDATE AVAILABLE PLAYER LINK
	****************************************************************************************/

	$('a.update-available-player-link').on('click', function(e) {
		e.preventDefault();

		var tableRow = $(this).closest('tr.available-player-row');

		var playerPoolId = tableRow.data('player-pool-id');
		var playerId = tableRow.data('playerId');
		var position = tableRow.children('td.available-player-position').text();
		var name = tableRow.children('td.available-player-name').text();
		var salary = tableRow.children('td.available-player-salary').text();

		toggleAvailablePlayerRow();
		updateLineupPlayerRow(status, playerPoolId, playerId, position, name, salary);

		var status = $(this).children('div').hasClass('circle-plus-icon');
		var iconDiv = $(this).children('div');
		var iconSpan = $(this).children('div').children('span');
	});

	function updateLineupPlayerRow(status, playerPoolId, playerId, position, name, salary) {
		if (status) {
			var lineupPlayerRow = $('td.lineup-player-position:contains("'+position+'")').next('td.lineup-player-name:empty').first().closest('tr.lineup-player-row');
			lineupPlayerRow.attr('data-player-pool-id', playerPoolId);
			lineupPlayerRow.attr('data-player-id', playerId);
			lineupPlayerRow.find('td.lineup-player-name').text(name);
			lineupPlayerRow.find('td.lineup-player-salary').text(salary);
			lineupPlayerRow.find('a.remove-lineup-player-link').append('<div class="circle-minus-icon"><span class="glyphicon glyphicon-minus"></span></div>');
		}

		if (!status) {
			removeLineupPlayerRow(playerId);
		}			
	}

	function toggleAvailablePlayerRow(status, iconDiv, iconSpan, tableRow) {
		if (status) {
			iconDiv.removeClass('circle-plus-icon');
			iconDiv.addClass('circle-minus-icon');

			iconSpan.removeClass('glyphicon-plus');
			iconSpan.addClass('glyphicon-minus');
		}

		if (!status) {
			iconDiv.addClass('circle-plus-icon');
			iconDiv.removeClass('circle-minus-icon');

			iconSpan.addClass('glyphicon-plus');
			iconSpan.removeClass('glyphicon-minus');
		}

		tableRow.toggleClass('available-player-row-strikethrough');
	}


	/****************************************************************************************
	REMOVE LINEUP PLAYER LINK
	****************************************************************************************/

	$('a.remove-lineup-player-link').on('click', function(e) {
		e.preventDefault();

		var playerId = $(this).closest('tr.lineup-player-row').data('player-id');

		removeLineupPlayerRow(playerId);
	});


	/****************************************************************************************
	REMOVE LINEUP PLAYER ROW
	****************************************************************************************/

	function removeLineupPlayerRow(playerId) {
		var lineupPlayerRow = $('tr.lineup-player-row[data-player-id*='+playerId+']').first();
		lineupPlayerRow.removeData('player-pool-id');
		lineupPlayerRow.removeData('player-id');
		lineupPlayerRow.find('td.lineup-player-name').empty();
		lineupPlayerRow.find('td.lineup-player-salary').empty();
		lineupPlayerRow.find('a.remove-lineup-player-link').empty();		
	}

});