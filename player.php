<?php
	class Player
	{
		private $_bank = 100;

		public function __construct($initial_balance = 100)
		{
			$this->_bank = $initial_balance;
		}

		public function get()
		{
			return $this->_bank;
		}

		public function withdraw($amount)
		{
			$this->_bank -= $amount;
		}

		public function deposit($amount)
		{
			$this->_bank += $amount;
		}
	}
