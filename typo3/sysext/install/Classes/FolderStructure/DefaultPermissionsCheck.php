<?php
namespace TYPO3\CMS\Install\FolderStructure;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Ernesto Baschny <ernst@cron-it.de>
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

/**
 * Service class to check the default folder permissions
 *
 */
class DefaultPermissionsCheck {

	/**
	 * @var array Recommended values for a secure production site
	 *
	 * These are not the default settings (which are 0664/2775), because they might not work on every installation.
	 * For security reasons these are the recommended values nevertheless (no world-readable files).
	 * It's up to the admins to decide if these recommended secure values can be applied to their installation.
	 */
	protected $recommended = array(
		'fileCreateMask' => '0660',
		'folderCreateMask' => '2770',
	);

	/**
	 * @var array Verbose names of the settings
	 */
	protected $names = array(
		'fileCreateMask' => 'Default File permissions',
		'folderCreateMask' => 'Default Directory permissions',
	);

	/**
	 * Checks a BE/*mask setting for it's security
	 *
	 * If it permits world writing: Error
	 * If it permits world reading: Warning
	 * If it permits group writing: Notice
	 * If it permits group reading: Notice
	 * If it permits only user read/write: Ok
	 *
	 * @param string $which fileCreateMask or folderCreateMask
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	public function getMaskStatus($which) {
		$octal = '0' . $GLOBALS['TYPO3_CONF_VARS']['BE'][$which];
		$dec = octdec($octal);
		$perms = array(
			'ox' => (($dec & 001) == 001),
			'ow' => (($dec & 002) == 002),
			'or' => (($dec & 004) == 004),
			'gx' => (($dec & 010) == 010),
			'gw' => (($dec & 020) == 020),
			'gr' => (($dec & 040) == 040),
			'ux' => (($dec & 0100) == 0100),
			'uw' => (($dec & 0200) == 0200),
			'ur' => (($dec & 0400) == 0400),
			'setgid' => (($dec & 02000) == 02000),
		);
		$extraMessage = '.';
		if ($perms['ow']) {
			$permissionStatus = new \TYPO3\CMS\Install\Status\ErrorStatus();
			$extraMessage = ' (writable by anyone on the server)';
		} elseif ($perms['or']) {
			$permissionStatus = new \TYPO3\CMS\Install\Status\WarningStatus();
			$extraMessage = ' (readable by anyone on the server)';
		} elseif ($perms['gw']) {
			$permissionStatus = new \TYPO3\CMS\Install\Status\NoticeStatus();
		} elseif ($perms['gr']) {
			$permissionStatus = new \TYPO3\CMS\Install\Status\NoticeStatus();
		} else {
			$permissionStatus = new \TYPO3\CMS\Install\Status\OkStatus();
		}
		$permissionStatus->setTitle($this->names[$which] . ' (BE/' . $which . ')');
		$permissionStatus->setMessage('Currently configured as ' . $GLOBALS['TYPO3_CONF_VARS']['BE'][$which] .
			$extraMessage . '.' .
			' Recommended: ' . $this->recommended[$which] . '. Make sure that your' .
			' server environment supports this before changing it.'
		);
		return $permissionStatus;
	}

}