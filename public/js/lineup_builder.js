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
	UPDATE PLAYER
	****************************************************************************************/

	$('a.update-player').on('click', function(e) {
		e.preventDefault();

		var status = $(this).children('div').hasClass('circle-plus-icon');
		var iconDiv = $(this).children('div');
		var iconSpan = $(this).children('div').children('span');
		var tableRow = $(this).closest('tr.available-player-row');

		toggleAvailablePlayerRow(status, iconDiv, iconSpan, tableRow);

		var playerPoolId = tableRow.data('player-pool-id');
		var playerId = tableRow.data('playerId');
		var position = tableRow.children('td.available-player-position').text();
		var name = tableRow.children('td.available-player-name').text();
		var salary = tableRow.children('td.available-player-salary').text();

		updateLineupPlayerRow(status, playerPoolId, playerId, position, name, salary);
	});

	function updateLineupPlayerRow(status, playerPoolId, playerId, position, name, salary) {
		if (status) {
			var lineupPlayerRow = $('td.lineup-player-position:contains("'+position+'")').next('td.lineup-player-name:empty').first().closest('tr.lineup-player-row');
			lineupPlayerRow.find('td.lineup-player-name').text(name);
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

});