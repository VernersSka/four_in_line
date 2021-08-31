<!doctype html>
<link rel="stylesheet" href="style.css">
<?php

	include "navigation.php";

	if (array_key_exists('reset', $_GET) &&  $_GET['reset'] == 'true') {
		resetGame();
		$moves = [];
	}
	else {
		$moves = get();
	}

	/** Nodefinē atļautos gājienus */
	$legal_moves = legal_moves();

	if (
		array_key_exists('id', $_GET) && 
		!array_key_exists($_GET['id'], $moves) && // uzspiežot uz aizņemta lauciņa NEIZDARA gājienu
		in_array($_GET['id'], $legal_moves) &&		// neļauj izdarīt nelegālu gājienu
		!array_key_exists('winner', $moves)				// neļauj izdarīt gājienu, ja uzvarētājs noteikts
	) {
		$symbol = count($moves) % 2 == 0 ? 'x' : 'o';
		$id = $_GET['id'];

		if (
			!array_key_exists($id, $moves) &&
			($id > 89 || array_key_exists($id + 10, $moves))
		) {
			add($id, $symbol);
			if (checkWinner($id, $moves)) {
				add('winner', $symbol);
				echo "<h2>Winner is '$symbol'!</h2>";
			}
		}

		$legal_moves = legal_moves();
		
		/** Bota gājiens */
		if (!array_key_exists('winner', $moves)) {
			$bot_move_index = array_rand($legal_moves);
			$bot_symbol = count($moves) % 2 == 0 ? 'x' : 'o';
	
			add($legal_moves[$bot_move_index], $bot_symbol);
			$moves = get();

			if (checkWinner($legal_moves[$bot_move_index], $moves)) {
				add('winner', $bot_symbol);
				echo "<h2>Winner is '$bot_symbol'!</h2>";
			}
		}
	}
?>


<div class="game_board four-in-line">
	<?php
	for($i = 0; $i <= 99; $i++) {
		echo "<a href='?id=$i'>" . @$moves[$i] . "</a> ";
		// echo "<a href='?id=$i'>" . $i . "</a> ";
	}
	?>
</div>

<div class="reset-btn">
	<a href="?reset=true">RESET BOARD</a>
</div>

<?php
function add($id, $symbol) {
	global $moves;
		/* Pievieno simbolu masīvā $moves un failā four_data.json */
		$moves[$id] = $symbol;
		$json = json_encode($moves, JSON_PRETTY_PRINT);
		file_put_contents('four_data.json', $json);
}

function get() {
	if (!file_exists('four_data.json')) {
		return [];
	}

	$content = file_get_contents('four_data.json');
	$data = json_decode($content, true);
	if (!is_array($data)) {
		$data = [];
	}

	return $data;
}

function legal_moves() {
	$legal_moves = [90, 91, 92, 93, 94, 95, 96, 97, 98, 99];
	global $moves;
		if (count($moves) > 0) {
		foreach ($moves as $move_index => $move) {
			if (in_array($move_index, $legal_moves)) {
				$key_to_change = array_search($move_index, $legal_moves);
				$legal_moves[$key_to_change] -= 10; 
			}
		}
	}
	return $legal_moves;
}

function resetGame() {
	file_put_contents('four_data.json', '{}');

	header('Location: ?');
}

function countMatches($id, $moves, $step, $v_direction = 0) {
	$symbol = $moves[$id];
	$col = getCol($id);
	$row = getRow($id);

	$item_id = $id;
	$count = 0;

	for ($i = 0; $i <= 2; $i++) {
		$item_id = $item_id + $step;
		if (
			$symbol == @$moves[$item_id] &&
			($v_direction === false ||
			getRow($item_id - $step) + $v_direction == getRow($item_id))
		) {
			$count++;
		}
		else {
			break;
		}
	}

	return $count;
}

function checkWinner($id, $moves) {
	/**START Horizontal */
	$count = countMatches($id, $moves, -1);
	if ($count == 3) {
		return true;
	}
	$count += countMatches($id, $moves, 1);
	if ($count >= 3) {
		return true;
	}
	/**END Horizontal */

	/**START Diagonal1 */
	$count = countMatches($id, $moves, -9, -1);
	if ($count == 3) {
		return true;
	}

	$count += countMatches($id, $moves, 9, 1);
	if ($count >= 3) {
		return true;
	}
	/**END Diagonal1 */

	/**START Diagonal2 */
	$count = countMatches($id, $moves, -11, -1);
	if ($count == 3) {
		return true;
	}

	$count += countMatches($id, $moves, 11, 1);
	if ($count >= 3) {
		return true;
	}
	/**END Diagonal2 */

	$count = countMatches($id, $moves, 10, false);
	if ($count == 3) {
		return true;
	}

	return false;
}

function getCol($id) {
	return $id % 10;
}
function getRow($id) {
	$col = $id % 10;
	return ($id - $col) / 10;
}
?>