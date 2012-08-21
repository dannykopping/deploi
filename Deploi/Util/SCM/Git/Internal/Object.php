<?php
	namespace Deploi\Util\SCM\Git\Internal;

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
	 * The base class for the all Git objects (commit, tree, blob and tag(unsupported))
	 *
	 * @category  VersionControl
	 * @package   Git
	 * @author    Kousuke Ebihara <ebihara@php.net>
	 * @copyright 2010 Kousuke Ebihara
	 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
	 */
	use Deploi\Util\SCM\Git\Internal\Component;

	abstract class Object extends Component
	{
		/**
		 * The identifier of this object
		 *
		 * @var string
		 */
		public $id;

		/**
		 * Constructor
		 *
		 * @param Git $git An instance of the Git
		 * @param string             $id  An identifier of this object
		 */
		public function __construct(Git $git, $id = null)
		{
			parent::__construct($git);

			$this->id = $id;
		}

		/**
		 * Fetch the substance of this object
		 *
		 * Object has contents in the Git repository. But it might be large, so
		 * script should fetch contents by calling this method only when necessary.
		 *
		 * @return Object The "$this" object for method chain
		 */
		abstract public function fetch();

		/**
		 * Get a value of this instance as string
		 *
		 * @return string The identifier of this object
		 */
		public function __toString()
		{
			return $this->id;
		}
	}
