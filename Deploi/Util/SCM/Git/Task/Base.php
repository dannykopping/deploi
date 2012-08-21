<?php
	namespace Deploi\Util\SCM\Git\Task;

	/**
	*  $Id: 5ffc8c9c51dfa9bd0d691a88db670cdeb5f985c1 $
	*
	* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
	* "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
	* LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
	* A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
	* OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
	* SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
	* LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
	* DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
	* THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
	* OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	*
	* This software consists of voluntary contributions made by many individuals
	* and is licensed under the LGPL. For more information please see
	* <http://phing.info>.
	*/

	/**
	 * Base class for Git tasks
	 *
	 * @author  Victor Farazdagi <simple.square@gmail.com>
	 * @version $Id: 5ffc8c9c51dfa9bd0d691a88db670cdeb5f985c1 $
	 * @package phing.tasks.ext.git
	 * @see     VersionControl_Git
	 * @since   2.4.3
	 */
	use Deploi\Util\SCM\Git\Git;
	use Deploi\Util\SCM\Git\Internal\Exception;

	abstract class Base
	{
		const MSG_DEBUG = 4;
		const MSG_VERBOSE = 3;
		const MSG_INFO = 2;
		const MSG_WARN = 1;
		const MSG_ERR = 0;

		/**
		 * Bath to git binary
		 *
		 * @var string
		 */
		private $gitPath = '/usr/bin/git';

		/**
		 * @var Git
		 */
		private $gitClient = null;

		/**
		 * Current repository directory
		 *
		 * @var string
		 */
		private $repository;

		/**
		 * Initialize Task.
		 * Check and include necessary libraries.
		 */
		public function init()
		{
		}

		public function log($message, $type)
		{
			$typeText = "";
			switch($type)
			{
				case self::MSG_DEBUG:
					$typeText = "Debug";
					break;
				case self::MSG_VERBOSE:
					$typeText = "Verbose";
					break;
				case self::MSG_INFO:
					$typeText = "Info";
					break;
				case self::MSG_WARN:
					$typeText = "Warning";
					break;
				case self::MSG_ERR:
					$typeText = "Error";
					break;
			}

			echo "[$typeText]\t\t$message\n";
		}

		/**
		 * Set repository directory
		 *
		 * @param string $repository Repo directory
		 *
		 * @return Base
		 */
		public function setRepository($repository)
		{
			$this->repository = $repository;
			return $this;
		}

		/**
		 * Get repository directory
		 *
		 * @return string
		 */
		public function getRepository()
		{
			return $this->repository;
		}

		/**
		 * Set path to git executable
		 *
		 * @param string $gitPath New path to git repository
		 *
		 * @return Base
		 */
		public function setGitPath($gitPath)
		{
			$this->gitPath = $gitPath;
			return $this;
		}

		/**
		 * Get path to git executable
		 *
		 * @return string
		 */
		public function getGitPath()
		{
			return $this->gitPath;
		}

		protected function getGitClient($reset = false, $repository = null)
		{
			$this->gitClient = ($reset === true) ? null : $this->gitClient;
			$repository = (null === $repository)
				? $this->getRepository()
				: $repository;

			if(null === $this->gitClient)
			{
				try
				{
					$this->gitClient = new Git($repository);
				}
				catch(Exception $e)
				{
					// re-package
					throw new Exception(
						'You must specify readable directory as repository.');

				}
			}
			$this->gitClient->setGitCommandPath($this->getGitPath());

			return $this->gitClient;
		}
	}



