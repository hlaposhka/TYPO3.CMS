<?php
namespace TYPO3\CMS\Install\FolderStructure;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Install\Status;

/**
 * A directory
 */
class DirectoryNode extends AbstractNode implements NodeInterface {

	/**
	 * @var NULL|integer Default for directories is octal 02775 == decimal 1533
	 */
	protected $targetPermission = '2775';

	/**
	 * Implement constructor
	 *
	 * @param array $structure Structure array
	 * @param NodeInterface $parent Parent object
	 * @throws Exception\InvalidArgumentException
	 */
	public function __construct(array $structure, NodeInterface $parent = NULL) {
		if (is_null($parent)) {
			throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
				'Node must have parent',
				1366222203
			);
		}
		$this->parent = $parent;

		// Ensure name is a single segment, but not a path like foo/bar or an absolute path /foo
		if (strstr($structure['name'], '/') !== FALSE) {
			throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
				'Directory name must not contain forward slash',
				1366226639
			);
		}
		$this->name = $structure['name'];

		if (isset($structure['targetPermission'])) {
			$this->setTargetPermission($structure['targetPermission']);
		}

		if (array_key_exists('children', $structure)) {
			$this->createChildren($structure['children']);
		}
	}

	/**
	 * Get own status and status of child objects
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	public function getStatus() {
		$result = array();
		if (!$this->exists()) {
			$status = new Status\ErrorStatus();
			$status->setTitle('Directory ' . $this->getRelativePathBelowSiteRoot() . ' does not exist');
			$status->setMessage('The Install Tool can try to create it');
			$result[] = $status;
		} else {
			$result = $this->getSelfStatus();
		}
		$result = array_merge($result, $this->getChildrenStatus());
		return $result;
	}

	/**
	 * Create a test file and delete again if directory exists
	 *
	 * @return boolean TRUE if test file creation was successful
	 */
	public function isWritable() {
		$result = TRUE;
		if (!$this->exists()) {
			$result = FALSE;
		} elseif (!$this->canFileBeCreated()) {
			$result = FALSE;
		}
		return $result;
	}

	/**
	 * Fix structure
	 *
	 * If there is nothing to fix, returns an empty array
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	public function fix() {
		$result = $this->fixSelf();
		foreach ($this->children as $child) {
			/** @var $child NodeInterface */
			$result = array_merge($result, $child->fix());
		}
		return $result;
	}

	/**
	 * Fix this directory:
	 *
	 * - create with correct permissions if it was not existing
	 * - if there is no "write" permissions, try to fix it
	 * - leave it alone otherwise
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	protected function fixSelf() {
		$result = array();
		if (!$this->exists()) {
			$resultCreateDirectory = $this->createDirectory();
			$result[] = $resultCreateDirectory;
			if ($resultCreateDirectory instanceof \TYPO3\CMS\Install\Status\OkStatus &&
				!$this->isPermissionCorrect()
			) {
				$result[] = $this->fixPermission();
			}
		} elseif (!$this->isWritable()) {
			// If directory is not writeable, we might have permissions to fix that
			// Try it:
			$result[] = $this->fixPermission();
		} elseif (!$this->isDirectory()) {
			$status = new Status\ErrorStatus();
			$status->setTitle('Path ' . $this->getRelativePathBelowSiteRoot() . ' is not a directory');
			$fileType = @filetype($this->getAbsolutePath());
			if ($fileType) {
				$status->setMessage(
					'The target ' . $this->getRelativePathBelowSiteRoot() . ' should be a directory,' .
					' but is of type ' . $fileType . '. This cannot be fixed automatically. Please investigate.'
				);
			} else {
				$status->setMessage(
					'The target ' . $this->getRelativePathBelowSiteRoot() . ' should be a directory,' .
					' but is of unknown type, probably because an upper level directory does not exist. Please investigate.'
				);
			}
			$result[] = $status;
		}
		return $result;
	}

	/**
	 * Create directory if not exists
	 *
	 * @throws Exception
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function createDirectory() {
		if ($this->exists()) {
			throw new Exception(
				'Directory ' . $this->getAbsolutePath() . ' already exists',
				1366740091
			);
		}
		$result = @mkdir($this->getAbsolutePath());
		if ($result === TRUE) {
			$status = new Status\OkStatus();
			$status->setTitle('Directory ' . $this->getRelativePathBelowSiteRoot() . ' successfully created.');
		} else {
			$status = new Status\ErrorStatus();
			$status->setTitle('Directory ' . $this->getRelativePathBelowSiteRoot() . ' not created!');
			$status->setMessage(
				'The target directory could not be created. There is probably a' .
				' group or owner permission problem on the parent directory.'
			);
		}
		return $status;
	}

	/**
	 * Get status of directory - used in root and directory node
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	protected function getSelfStatus() {
		$result = array();
		if (!$this->isDirectory()) {
			$status = new Status\ErrorStatus();
			$status->setTitle($this->getRelativePathBelowSiteRoot() . ' is not a directory');
			$status->setMessage(
				'Directory ' . $this->getRelativePathBelowSiteRoot() . ' should be a directory,' .
				' but is of type ' . filetype($this->getAbsolutePath())
			);
			$result[] = $status;
		} elseif (!$this->isWritable()) {
			$status = new Status\ErrorStatus();
			$status->setTitle('Directory ' . $this->getRelativePathBelowSiteRoot() . ' is not writable');
			$status->setMessage(
				'Path ' . $this->getAbsolutePath() . ' exists, but no file underneath it' .
				' can be created.'
			);
			$result[] = $status;
		} elseif (!$this->isPermissionCorrect()) {
			$status = new Status\NoticeStatus();
			$status->setTitle('Directory ' . $this->getRelativePathBelowSiteRoot() . ' permissions mismatch');
			$status->setMessage(
				'Default configured permissions are ' . $this->getTargetPermission() .
				' but current permissions are ' . $this->getCurrentPermission()
			);
			$result[] = $status;
		} else {
			$status = new Status\OkStatus();
			$status->setTitle('Directory ' . $this->getRelativePathBelowSiteRoot());
			$status->setMessage(
				'Is a directory with the configured permissions of ' . $this->getTargetPermission()
			);
			$result[] = $status;
		}
		return $result;
	}

	/**
	 * Get status of children
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	protected function getChildrenStatus() {
		$result = array();
		foreach ($this->children as $child) {
			/** @var $child NodeInterface */
			$result = array_merge($result, $child->getStatus());
		}
		return $result;
	}

	/**
	 * Create a test file and delete again - helper for isWritable
	 *
	 * @return boolean TRUE if test file creation was successful
	 */
	protected function canFileBeCreated() {
		$testFileName = uniqid('installToolTest_');
		$result = @touch($this->getAbsolutePath() . '/' . $testFileName);
		if ($result === TRUE) {
			unlink($this->getAbsolutePath() . '/' . $testFileName);
		}
		return $result;
	}

	/**
	 * Checks if not is a directory
	 *
	 * @return boolean True if node is a directory
	 */
	protected function isDirectory() {
		$path = $this->getAbsolutePath();
		return (!@is_link($path) && @is_dir($path));
	}

	/**
	 * Create children nodes - done in directory and root node
	 *
	 * @param array $structure Array of childs
	 * @throws Exception\InvalidArgumentException
	 */
	protected function createChildren(array $structure) {
		foreach ($structure as $child) {
			if (!array_key_exists('type', $child)) {
				throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
					'Child must have type',
					1366222204
				);
			}
			if (!array_key_exists('name', $child)) {
				throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
					'Child must have name',
					1366222205
				);
			}
			$name = $child['name'];
			foreach ($this->children as $existingChild) {
				/** @var $existingChild NodeInterface */
				if ($existingChild->getName() === $name) {
					throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
						'Child name must be unique',
						1366222206
					);
				}
			}
			$this->children[] = new $child['type']($child, $this);
		}
	}
}
