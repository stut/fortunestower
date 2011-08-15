<?php
	class FortunesTower
	{
		private $_deck = false;
		private $_deckpos = 0;
		private $_table = array();
		private $_rowstatus = array();
		private $_rowmessage = array();
		private $_tablevalue = 0;
		private $_multiplier = 1;

		public function __construct()
		{
		}

		public function newGame()
		{
			// The deck contains 10 sets of 1-7, and 4 heroes
			$this->_deck = str_repeat('1234567', 10).'HHHH';
			$this->_deckpos = 1;

			// Start the table with the first hidden card
			$this->_table = array($this->_deck[0]);

			// Reset the other status vars to their initial values
			$this->_rowstatus = array(array());
			$this->_rowmessage = array();
			$this->_tablevalue = 0;
			$this->_multiplier = 1;

			// Shuffle the deck
			$this->shuffleDeck();
		}

		public function playGame($decision_function)
		{
			// Initialise the game
			$this->newGame();

			// Game loop
			$result = false;
			$banked = false;
			while ($result === false)
			{
				// Deal the next row
				$this->dealRow();

				// Evaluate the status
				$status = $this->evaluateStatus();

				if (!$status)
				{
					$result = true;
				}
				else
				{
					// Have we reached the last row?
					if (count($this->_table) == 8)
					{
						// Yup, if we haven't banked then bank this
						if ($banked === false)
						{
							// Have we used the gate card?
							if ($this->getGateCard() != '-')
							{
								// Value is the full table
								$this->_tablevalue = 0;
								foreach ($this->_table as $row)
								{
									foreach (preg_split('//', $currentrow, -1, PREG_SPLIT_NO_EMPTY) as $digit)
									{
										if (is_numeric($digit))
										{
											$this->_tablevalue += $digit;
										}
									}
								}

								$this->_rowmessage[count($this->_table)-1] .= ' bonus '.$this->_tablevalue;
								if ($this->_multiplier > 1)
								{
									$this->_rowmessage[count($this->_table)-1] .= '*'.$this->_multiplier.'='.($this->_tablevalue*$this->_multiplier);
								}
							}

							// Bank it
							$banked = $this->_tablevalue * $this->_multiplier;
							$this->_rowmessage[count($this->_table)-1] .= ' banked';
						}
						$result = true;
					}
					elseif ($banked === false)
					{
						// No banking yet
						if (call_user_func($decision_function, $this->_tablevalue * $this->_multiplier, $this->getGateCard() != '-', $this->_multiplier))
						{
							$banked = $this->_tablevalue * $this->_multiplier;
							$this->_rowmessage[count($this->_table)-1] .= ' banked';
						}
					}
					else
					{
						// User has banked, carry on
					}
				}

				// Display the row
				$this->displayRow();
			}

			if ($banked !== false)
			{
				$result = $banked;
			}

			// Return the outcome
			return $result;
		}

		public function shuffleDeck()
		{
			$this->_deck = str_shuffle($this->_deck);
		}

		public function dealRow()
		{
			// Work out how many to deal
			$num = count($this->_table) + 1;

			// Deal the row
			$this->_table[] = substr($this->_deck, $this->_deckpos, $num);

			// Update the deck position
			$this->_deckpos += $num;

			// Check for multiplier bonuses
			if ($this->_table[$num-1] == str_repeat($this->_table[$num-1][0], $num))
			{
				$this->_multiplier = $num;
				$this->_rowmessage[$num-1] = 'x'.$num;
			}
		}

		public function evaluateStatus()
		{
			$retval = true;

			if (!isset($this->_rowmessage[$num]))
			{
				$this->_rowmessage[$num] = '';
			}
			else
			{
				$this->_rowmessage[$num] .= ' ';
			}

			// Get the rows for comparison
			$num = count($this->_table)-1;
			$currentrow = $this->_table[$num];
			$comparerow = $this->_table[$num-1];

			// Init the status item
			$this->_rowstatus[$num] = array();

			// Get the nums as an array
			$digits = preg_split('//', $currentrow, -1, PREG_SPLIT_NO_EMPTY);

			// Calculate the row value
			$this->_tablevalue = 0;
			foreach ($digits as $digit)
			{
				if (is_numeric($digit))
					$this->_tablevalue += $digit;
			}
			$this->_rowmessage[$num] .= ' '.$this->_tablevalue;
			if ($this->_multiplier > 1)
			{
				$this->_rowmessage[$num] .= '*'.$this->_multiplier.'='.($this->_tablevalue*$this->_multiplier);
			}

			// Look for misfortune
			if ($num > 1)
			{
				foreach ($digits as $id => $digit)
				{
					if (is_numeric($digit))
					{
						if ($id > 0)
						{
							// Check $id-1
							if ($comparerow[$id-1] == $digit)
							{
								// Got misfortune, check the gate card
								$gatecard = $this->getGateCard();
								if ($gatecard != '-')
								{
									// Mark the status so the user knows we've use it
									$this->_rowmessage[$num] .= ' G'.$digits[$id].'>'.$gatecard;

									// Use the gate card
									$this->useGateCard();
									$digits[$id] = $gatecard;
									$this->_table[$num] = implode('', $digits);

									// Evaluate the new row contents
									return $this->evaluateStatus();
								}

								// Nope, misfortune it is
								$this->_rowstatus[$num][] = $id;
								$retval = false;
							}
						}

						if ($id < count($digits))
						{
							// Check $id
							if ($comparerow[$id] == $digit)
							{
								// Got misfortune, check the gate card
								$gatecard = $this->getGateCard();
								if ($gatecard != '-')
								{
									// Mark the status so the user knows we've use it
									$this->_rowmessage[$num] .= ' G'.$digits[$id].'>'.$gatecard;

									// Use the gate card
									$this->useGateCard();
									$digits[$id] = $gatecard;
									$this->_table[$num] = implode('', $digits);

									// Evaluate the new row contents
									return $this->evaluateStatus();
								}

								// Nope, misfortune it is
								$this->_rowstatus[$num][] = $id;
								$retval = false;
							}
						}
					}
				}
			}

			if (!$retval)
			{
				if (in_array('H', $digits))
				{
					$this->_rowstatus[$num] = array();
					foreach ($digits as $id => $digit)
					{
						if ($digit == 'H')
						{
							$this->_rowstatus[$num][] = $id;
						}
					}
					$retval = true;
				}
				else
				{
					$this->_rowmessage[$num] .= ' X';
				}
			}

			return $retval;
		}

		public function getGateCard()
		{
			return $this->_table[0];
		}

		public function useGateCard()
		{
			$this->_table[0] = '-';
		}

		public function displayRow()
		{
			$rownum = count($this->_table)-1;
			$row = $this->_table[$rownum];
			$prefixlen = 8 - count($this->_table);
			//foreach ($this->_table as $rownum => $row)
			{
				$skipbefore = false;

				if ($rownum == 0) $row = 'G';

				$digits = preg_split('//', $row, -1, PREG_SPLIT_NO_EMPTY);

				echo str_repeat(' ', $prefixlen);

				foreach ($digits as $id => $digit)
				{
					if (!$skipbefore)
					{
						if (in_array($id, $this->_rowstatus[$rownum]))
						{
							echo '>';
						}
						else
						{
							echo ' ';
						}
					}

					echo $digit;

					if (in_array($id, $this->_rowstatus[$rownum]))
					{
						if (in_array($id+1, $this->_rowstatus[$rownum]))
						{
							echo '-';
						}
						else
						{
							echo '<';
						}
						$skipbefore = true;
					}
					else
					{
						$skipbefore = false;
					}
				}

				if (!in_array(count($digits)-1, $this->_rowstatus[$rownum]))
				{
					echo ' ';
				}

				echo str_repeat(' ', $prefixlen).'  '.trim($this->_rowmessage[$id])."\n";

				$prefixlen--;
			}
		}
	}
