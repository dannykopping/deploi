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
	 * The OO interface for blob object
	 *
	 * @category  VersionControl
	 * @package   Git
	 * @author    Kousuke Ebihara <ebihara@php.net>
	 * @copyright 2010 Kousuke Ebihara
	 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
	 */

	use Deploi\Util\SCM\Git\Internal\Object;

	class Blob extends Object
	{
		/**
		 * The contents of this object
		 *
		 * @var string
		 */
		protected $content;

		/**
		 * Fetch the substance of this object
		 *
		 * @return Object The "$this" object for method chain
		 */
		public function fetch()
		{
			$command = $this->git->getCommand('cat-file')
				->setOption('p')
				->addArgument($this->id);

			$this->content = trim($command->execute());

			return $this;
		}

		/**
		 * Get the contents of this object
		 *
		 * @return string
		 */
		public function getContent()
		{
			return $this->content;
		}
	}
