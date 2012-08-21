<?php
	namespace Deploi\Util\SCM\Git\Util;

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
	 * The OO interface for executing Git command
	 *
	 * @category  VersionControl
	 * @package   Git
	 * @author    Kousuke Ebihara <ebihara@php.net>
	 * @copyright 2010 Kousuke Ebihara
	 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
	 */
	use Deploi\Util\SCM\Git\Internal\Component;
	use Deploi\Util\SCM\Git\Internal\Exception;

	class Command extends Component
	{
		/**
		 * The subcommand name
		 *
		 * @var string
		 */
		protected $subCommand = '';

		/**
		 * An array of arguments
		 *
		 * @var array
		 */
		protected $arguments = array();

		/**
		 * An array of options
		 *
		 * @var array
		 */
		protected $options = array();

		/**
		 * Flag to add "--" before the end of command
		 *
		 * If this is true, command is executed with "--".
		 * It is need by some Git command for understanding the specified
		 * object is not a path.
		 *
		 * @var bool
		 */
		protected $doubleDash = false;

		/**
		 * Set the subcommand name
		 *
		 * @param string $command The subcommand name
		 *
		 * @return Command The "$this" object for method chain
		 */
		public function setSubCommand($command)
		{
			$this->subCommand = $command;

			return $this;
		}

		/**
		 * Set the options
		 *
		 * @param array $options An array of new options
		 *
		 * @return Command The "$this" object for method chain
		 */
		public function setOptions($options)
		{
			$this->options = $options;

			return $this;
		}

		/**
		 * Set the arguments
		 *
		 * @param array $arguments An array of new arguments
		 *
		 * @return Command The "$this" object for method chain
		 */
		public function setArguments($arguments)
		{
			$this->arguments = array_values($arguments);

			return $this;
		}

		/**
		 * Set a option
		 *
		 * @param string      $name  A name of option
		 * @param string|bool $value A value of option. If it is "true", this option
		 *                           doesn't have a value. If it is "false", this option
		 *                           will be not used
		 *
		 * @return Command The "$this" object for method chain
		 */
		public function setOption($name, $value = true)
		{
			$this->options[$name] = $value;

			return $this;
		}

		/**
		 * Add an argument
		 *
		 * @param string $value A value of argument
		 *
		 * @return Command The "$this" object for method chain
		 */
		public function addArgument($value)
		{
			$this->arguments[] = $value;

			return $this;
		}

		/**
		 * Add / Remove a double-dash
		 *
		 * @param string $isAdding Whether to add double-dash
		 *
		 * @return Command The "$this" object for method chain
		 */
		public function addDoubleDash($isAdding)
		{
			$this->doubleDash = $isAdding;

			return $this;
		}

		/**
		 * Create a command string to execute
		 *
		 * @param array $arguments An array of arguments
		 * @param array $options   An array of options
		 *
		 * @throws Exception
		 * @return string
		 */
		public function createCommandString($arguments = array(), $options = array())
		{
			if(!$this->subCommand)
			{
				throw new Exception('You must specify "subCommand"');
			}

			$command = $this->git->getGitCommandPath() . ' ' . $this->subCommand;

			$arguments = array_merge($this->arguments, $arguments);
			$options = array_merge($this->options, $options);

			foreach($options as $k => $v)
			{
				if(false === $v)
				{
					continue;
				}

				$isShortOption = (1 === strlen($k));

				if($isShortOption)
				{
					$command .= ' -' . $k;
				}
				else
				{
					$command .= ' --' . $k;
				}

				if(true !== $v)
				{
					$command .= (($isShortOption) ? '' : '=') . escapeshellarg($v);
				}
			}

			foreach($arguments as $v)
			{
				$command .= ' ' . escapeshellarg($v);
			}

			if($this->doubleDash)
			{
				$command .= ' --';
			}

			return $command;
		}

		/**
		 * Execute a created command and get result
		 *
		 * @param array $arguments An array of arguments
		 * @param array $options   An array of options
		 *
		 * @return string
		 */
		public function execute($arguments = array(), $options = array())
		{
			$command = $this->createCommandString($arguments, $options);

			$descriptorspec = array(
				1 => array('pipe', 'w'),
				2 => array('pipe', 'w'),
			);
			$pipes = array();
			$resource = proc_open($command, $descriptorspec, $pipes, realpath($this->git->getDirectory()));

			$stdout = stream_get_contents($pipes[1]);
			$stderr = stream_get_contents($pipes[2]);
			foreach($pipes as $pipe)
			{
				fclose($pipe);
			}

			$status = trim(proc_close($resource));
			if($status)
			{
				$message = "Some errors in executing git command\n\n"
					. "Output:\n"
					. $stdout . "\n"
					. "Error:\n"
					. $stderr;
				throw new Exception($message);
			}

			return $this->stripEscapeSequence($stdout);
		}

		/**
		 * Strip terminal escape sequences from the specified string
		 *
		 * @param string $string The string that will be trimmed
		 *
		 * @return string
		 */
		public function stripEscapeSequence($string)
		{
			$string = preg_replace('/\e[^a-z]*?[a-z]/i', '', $string);

			return $string;
		}
	}
