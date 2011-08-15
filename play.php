<?php
	require dirname(__FILE__).'/player.php';
	require dirname(__FILE__).'/fortunestower.php';

	$initial_balance = 100;
	$cost_per_game = 15;

	// Very simple logic
	function getDecision($offer, $gate, $multiplier)
	{
		if (!$gate)
		{
			if ($offer > 15)
			{
				return true;
			}
		}

		if ($offer > 25)
		{
			return true;
		}

		return false;
	}

	$player = new Player($initial_balance);

	$game = new FortunesTower();

	passthru('clear');
	$rounds = 0;

	while ($player->get() >= $cost_per_game)
	{
		$rounds++;
		echo 'Round '.$rounds."\n\n";

		$player->withdraw($cost_per_game);
		$result = $game->playGame('getDecision');
		if (is_numeric($result))
		{
			$player->deposit($result);
		}
		echo "\nBank balance = ".$player->get()."\n";
		sleep(2);
		passthru('clear');
	}

	echo 'Survived for '.$rounds.' round'.($rounds == 1 ? '' : 's')."\n\n";
