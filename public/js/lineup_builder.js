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
	??
	****************************************************************************************/

	$('a.update-player').on('click', function(e) {
		e.preventDefault();

		var status = $(this).children('div').hasClass('circle-plus-icon');
		var iconDiv = $(this).children('div');
		var iconSpan = $(this).children('div').children('span');
		var tableRow = $(this).closest('tr.available-player-row');

		toggleAvailablePlayerRow(status, iconDiv, iconSpan, tableRow);
	});

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