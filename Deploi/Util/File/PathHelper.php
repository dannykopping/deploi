<?php
	namespace Deploi\Util\File;

	/**
	 *
	 */
	use Net_SSH2;

	/**
	 *
	 */
	class PathHelper
	{
		/**
		 *    true if file exists and is a block special file
		 */
		const IS_BLOCK = "-b";

		/**
		 *    true if file exists and is a character special file.
		 */
		const IS_CHARACTER_SPECIAL = "-c";

		/**
		 *    true if file exists and is a directory.
		 */
		const IS_DIRECTORY = "-d";

		/**
		 *    true if file exists.
		 */
		const EXISTS = "-e";

		/**
		 *    true if file exists and is a regular file.
		 */
		const IS_FILE = "-f";

		/**
		 *    true if file exists and has its setgid bit set.
		 */
		const HAS_SET_GID_BIT = "-g";

		/**
		 *    true if file exists and is a symbolic link.
		 */
		const IS_SYMBOLIC = "-h";

		/**
		 *    true if file exists and has its sticky bit set.
		 */
		const HAS_STICKY_BIT = "-k";

		/**
		 *    true if length of string is non-zero.
		 */
		const IS_NON_ZERO_LENGTH = "-n";

		/**
		 *    true if option named option is on. option may be a single character, in which
		 *     case it is a single letter option name.
		 */
		const IS_NAMED_OPTION = "-o";

		/**
		 *    true if file exists and is a FIFO special file (named pipe).
		 */
		const IS_NAMED_PIPE = "-p";

		/**
		 *    true if file exists and is readable by current process.
		 */
		const IS_READABLE = "-r";

		/**
		 *    true if file exists and has size greater than zero.
		 */
		const IS_SIZE_NON_ZERO = "-s";

		/**
		 *    true if file descriptor number fd is open and
		 *     associated with a terminal device
		 */
		const IS_TERMINAL_DEVICE = "-t";

		/**
		 *    true if file exists and has its setuid bit set.
		 */
		const HAS_SET_UID_BIT = "-u";

		/**
		 *    true if file exists and is writable by current process.
		 */
		const IS_WRITABLE = "-w";

		/**
		 *    true if file exists and is executable by current process. If file exists
		 *     and is a directory, then the current process has permission to
		 *     search in the directory.
		 */
		const IS_EXECUTABLE_OR_SEARCHABLE = "-x";

		/**
		 *    true if length of string is zero.
		 */
		const IS_SIZE_ZERO = "-z";

		/**
		 *    true if file exists and is owned by the effective user ID of this process.
		 */
		const IS_OWNED_BY_USERID = "-O";

		/**
		 *    true if file exists and its group matches the effective group ID of this process.
		 */
		const IS_OWNED_BY_GROUP = "-G";

		/**
		 *    true if file exists and its access time is not newer than its modification time.
		 */
		const IS_ACCESS_TIME_LOWER_THAN_MODIFICATION_TIME = "-N";

		/**
		 *    true if file exists and is a socket.
		 */
		const IS_SOCKET = "-S";

		/**
		 * @static
		 *
		 * @param $property
		 *
		 * @return string
		 */
		public static function getText($property)
		{
			switch($property)
			{
				case self::IS_BLOCK:
					return "block";
				case self::IS_CHARACTER_SPECIAL:
					return "character special";
				case self::IS_DIRECTORY:
					return "directory";
				case self::EXISTS:
					return "exists";
				case self::IS_FILE:
					return "file";
				case self::HAS_SET_GID_BIT:
					return "has setgid bit";
				case self::IS_SYMBOLIC:
					return "symbolic link";
				case self::HAS_STICKY_BIT:
					return "has sticky bit";
				case self::IS_NON_ZERO_LENGTH:
					return "non-zero length";
				case self::IS_NAMED_OPTION:
					return "named option";
				case self::IS_NAMED_PIPE:
					return "named pipe";
				case self::IS_READABLE:
					return "readable";
				case self::IS_SIZE_NON_ZERO:
					return "zero size";
				case self::IS_TERMINAL_DEVICE:
					return "terminal device";
				case self::HAS_SET_UID_BIT:
					return "has setuid bit";
				case self::IS_WRITABLE:
					return "writable";
				case self::IS_EXECUTABLE_OR_SEARCHABLE:
					return "executable";
				case self::IS_SIZE_ZERO:
					return "zero size";
				case self::IS_OWNED_BY_USERID:
					return "owned by userid";
				case self::IS_OWNED_BY_GROUP:
					return "owned by group";
				case self::IS_ACCESS_TIME_LOWER_THAN_MODIFICATION_TIME:
					return "access time is lower than modification time";
				case self::IS_SOCKET:
					return "socket";
				default:
					return null;
					break;
			}
		}
	}
