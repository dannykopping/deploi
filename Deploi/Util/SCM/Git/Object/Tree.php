<?php
	namespace Deploi\Util\SCM\Git\Object;

	/**
	 * Copyright 2010 Kousuke Ebihara
	 *
	 * Licensed under the Apache License, Version 2.0 (the "License");
	 * you may not use this file except in compliance with the License.
	 * You may obtain a copy of the License at
	 *
	 * http://www.apache.org/licenses/LICENSE-2.0
	 *
	 * Unless required by applicable law or agreed to in writing, software
	 * distributed under the License is distributed on an "AS IS" BASIS,
	 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	 * See the License for the specific language governing permissions and
	 * limitations under the License.
	 *
	 * PHP Version 5
	 *
	 * @category  VersionControl
	 * @package   Git
	 * @author    Kousuke Ebihara <ebihara@php.net>
	 * @copyright 2010 Kousuke Ebihara
	 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
	 */

	/**
	 * The OO interface for tree object
	 *
	 * @category  VersionControl
	 * @package   Git
	 * @author    Kousuke Ebihara <ebihara@php.net>
	 * @copyright 2010 Kousuke Ebihara
	 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
	 */
	use SeekableIterator;
	use Deploi\Util\SCM\Git\Internal\Exception;
	use Deploi\Util\SCM\Git\Internal\Object;
	use Deploi\Util\SCM\Git\Git;

	class Tree extends Object implements SeekableIterator
	{
		/**
		 * The current position
		 *
		 * @var int
		 */
		protected $position = 0;

		/**
		 * An array of instances of object
		 *
		 * @var array
		 */
		protected $objects = array();

		/**
		 * Constructor
		 *
		 * @param Git                $git An instance of the Git
		 * @param string             $id  An identifier of this object
		 */
		public function __construct(Git $git, $id)
		{
			$this->position = 0;

			parent::__construct($git, $id);
		}

		/**
		 * Fetch the substance of this object
		 *
		 * @return Object The "$this" object for method chain
		 */
		public function fetch()
		{
			$command = $this->git->getCommand('ls-tree')
				->addArgument($this->id);

			$lines = explode(PHP_EOL, trim($command->execute()));
			foreach($lines as $line)
			{
				$itemString = str_replace("\t", ' ', $line);

				list ($mode, $type, $id, $name) = explode(' ', $itemString, 4);

				$class = '' . ucfirst($type);

				$this->objects[] = new $class($this->git, $id);
			}

			return $this;
		}

		/**
		 * Seeks to the specified position
		 *
		 * @param int $position The position to seek to
		 *
		 * @return null
		 */
		public function seek($position)
		{
			$this->position = $position;

			if(!$this->valid())
			{
				throw new Exception('Invalid offset is specified');
			}
		}

		/**
		 * Rewind this iterator to the first position
		 *
		 * @return null
		 */
		public function rewind()
		{
			$this->position = 0;
		}

		/**
		 * Get the current value
		 *
		 * @return Object
		 */
		public function current()
		{
			return $this->objects[$this->position];
		}

		/**
		 * Get the current key
		 *
		 * @return int
		 */
		public function key()
		{
			return $this->position;
		}

		/**
		 * Move forward to next positon
		 *
		 * @return null
		 */
		public function next()
		{
			++$this->position;
		}

		/**
		 * Checks if current position is valid
		 *
		 * @return bool
		 */
		public function valid()
		{
			return isset($this->objects[$this->position]);
		}
	}
