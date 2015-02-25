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
	CALCULATE AVERAGE SALARY PER PLAYER LEFT
	****************************************************************************************/

	calculateAvgSalaryPerPlayerLeft();

	function calculateAvgSalaryPerPlayerLeft() {
		var totalSalary = $('span.lineup-salary-total').text();

		var numEmptyRosterSpots = $('td.lineup-player-name:empty').length;

		if (totalSalary == 0 || totalSalary == 60000 || numEmptyRosterSpots == 0) {
			$('span.avg-salary-per-player-left').text(0);

			return;
		}

		var avgSalaryPerPlayerLeft = parseInt((60000 - totalSalary) / numEmptyRosterSpots);

		$('span.avg-salary-per-player-left').text(avgSalaryPerPlayerLeft);
	}


	/****************************************************************************************
	UPDATE AVAILABLE PLAYER LINK
	****************************************************************************************/

	$('a.update-available-player-link').on('click', function(e) {
		e.preventDefault();

		var availablePlayerRow = $(this).closest('tr.available-player-row');

		var playerPoolId = availablePlayerRow.data('player-pool-id');
		var iconDiv = getIconDiv(availablePlayerRow);
		var status = getStatus(iconDiv);
		var playerId = availablePlayerRow.data('playerId');
		var position = availablePlayerRow.children('td.available-player-position').text();
		var name = availablePlayerRow.children('td.available-player-name').text();
		var salary = availablePlayerRow.children('td.available-player-salary').text();

		if (!isPositionFull(position) && status) {
			alert('The '+position+' position is full.');
			return;
		}

		updateAvailablePlayerRow(availablePlayerRow, iconDiv, status);
		updateLineupPlayerRow(status, playerPoolId, playerId, position, name, salary);

		calculateAvgSalaryPerPlayerLeft();
	});

	function isPositionFull(position) {
		var isThereOpenSpot = $('td.lineup-player-position:contains("'+position+'")').next('td.lineup-player-name:empty').first().closest('tr.lineup-player-row').length;

		return isThereOpenSpot;
	}


	/****************************************************************************************
	REMOVE LINEUP PLAYER LINK
	****************************************************************************************/

	$('a.remove-lineup-player-link').on('click', function(e) {
		e.preventDefault();

		var playerId = $(this).closest('tr.lineup-player-row').data('player-id');

		var availablePlayerRow = getAvailablePlayerRow(playerId);
		var iconDiv = getIconDiv(availablePlayerRow);
		var status = getStatus(iconDiv);

		updateAvailablePlayerRow(availablePlayerRow, iconDiv, status);
		updateLineupPlayerRow(status, null, playerId, null, null, null);

		calculateAvgSalaryPerPlayerLeft();
	});


	/****************************************************************************************
	GETTERS FOR AVAILABLE PLAYER
	****************************************************************************************/

	function getAvailablePlayerRow(playerId) {
		return $('tr.available-player-row[data-player-id*='+playerId+']').first();
	}

	function getIconDiv(availablePlayerRow) {
		return availablePlayerRow.children('td.available-player-update').children('a.update-available-player-link').children('div');
	}

	function getStatus(iconDiv) {
		return iconDiv.hasClass('circle-plus-icon');
	}

	/****************************************************************************************
	UPDATE AVAILABLE PLAYER ROW
	****************************************************************************************/

	function updateAvailablePlayerRow(availablePlayerRow, iconDiv, status) {
		var iconSpan = iconDiv.children('span');
		
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

		availablePlayerRow.toggleClass('available-player-row-strikethrough');
	}


	/****************************************************************************************
	UPDATE LINEUP PLAYER ROW
	****************************************************************************************/

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
			var lineupPlayerRow = $('tr.lineup-player-row[data-player-id*='+playerId+']').first();

			emptyLineupPlayerRow(lineupPlayerRow);
		}

		updateTotalSalary();		
	}

	function updateTotalSalary() {
		var totalSalary = 0;

		$('td.lineup-player-salary').each(function() {
			var salaryText = $(this).text();
			var salary = checkSalaryForBlank(salaryText);

			totalSalary += salary;
		});

		$('span.lineup-salary-total').text(totalSalary);

		addColorForTotalSalary(totalSalary);
	}

	function checkSalaryForBlank(salaryText) {
		if (salaryText == '') {
			return parseInt(0);
		} 

		return parseInt(salaryText);
	}

	function emptyLineupPlayerRow(lineupPlayerRow) {
		lineupPlayerRow.removeData('player-pool-id');
		lineupPlayerRow.removeData('player-id');
		lineupPlayerRow.find('td.lineup-player-name').empty();
		lineupPlayerRow.find('td.lineup-player-salary').empty();
		lineupPlayerRow.find('a.remove-lineup-player-link').empty();	
	}


	/****************************************************************************************
	ADD COLOR FOR TOTAL SALARY
	****************************************************************************************/

	var totalSalary = $('span.lineup-salary-total').text();
	addColorForTotalSalary(totalSalary);

	function addColorForTotalSalary(totalSalary) {
		if (totalSalary >= 59400 && totalSalary <= 60000) {
			$('span.lineup-salary-total').addClass('lineup-salary-total-valid');
			$('span.lineup-salary-total').removeClass('lineup-salary-total-invalid');
		}

		if (totalSalary > 60000) {
			$('span.lineup-salary-total').addClass('lineup-salary-total-invalid');
			$('span.lineup-salary-total').removeClass('lineup-salary-total-valid');
		}

		if (totalSalary < 59400) {
			$('span.lineup-salary-total').removeClass('lineup-salary-total-valid');
			$('span.lineup-salary-total').removeClass('lineup-salary-total-invalid');
		}
	}


	/****************************************************************************************
	SUBMIT LINEUP 
	****************************************************************************************/

	$('button.submit-lineup').on('click', function() {
		if (!validateLineup()) {
			return;
		}

		$('button.submit-lineup').attr('disabled', 'disabled').text('Saving...');

		var lineupPlayerRow = $('tr.lineup-player-row');

		var playerPoolId = lineupPlayerRow.first().data('player-pool-id');
		var lineupBuyIn = $('input.lineup-buy-in-amount').val();
		var totalSalary = $('span.lineup-salary-total-valid').text();

		var playerIdsOfLineup = [];
		var hash = '';

		lineupPlayerRow.each(function() {
			var playerId = $(this).data('player-id');
			
			playerIdsOfLineup.push(playerId); 
			hash += playerId;
		});

		$.ajax({
            url: baseUrl+'/solver_top_plays/add_or_remove_lineup/',
           	type: 'POST',
           	data: { 
           		addOrRemove: 'Add',
           		playerPoolId: playerPoolId,
           		buyIn: lineupBuyIn,
           		totalSalary: totalSalary,
           		playerIdsOfLineup: playerIdsOfLineup,
           		hash: hash
           	},
            success: function() {
            	emptyLineupPlayerRow(lineupPlayerRow);

            	updateTotalSalary();

            	availablePlayerRowWithStrikethrough = $('tr.available-player-row-strikethrough');
            	
            	availablePlayerRowWithStrikethrough.removeClass('available-player-row-strikethrough');
            	availablePlayerRowWithStrikethrough.children('td.available-player-update').find('div').removeClass('circle-minus-icon').addClass('circle-plus-icon');
            	availablePlayerRowWithStrikethrough.children('td.available-player-update').find('span').removeClass('glyphicon-minus').addClass('glyphicon-plus');

            	$('button.submit-lineup').removeAttr('disabled').text('Submit Lineup');
            	$('h4.lineup').after('<div class="alert alert-info fade in" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>Success!</div>');
            }
        }); 
	});

	function validateLineup() {
		var numEmptyRosterSpots = $('td.lineup-player-name:empty').length;

		if (numEmptyRosterSpots == 0) {
			var validRoster = 1;
		}

		if (numEmptyRosterSpots != 0) {
			var validRoster = 0;
		}

		if (!validRoster) {
			alert('This lineup is missing roster spots.');
			return false;
		}

		return true;
	}

});