<?php
namespace TYPO3\CMS\Install\Controller\Action\Common;

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

use TYPO3\CMS\Install\Controller\Action;

/**
 * Welcome page
 */
class InstallToolDisabledAction extends Action\AbstractAction {

	/**
	 * Executes the action
	 *
	 * @return string Rendered content
	 */
	protected function executeAction() {
		/** @var \TYPO3\CMS\Install\SystemEnvironment\Check $statusCheck */
		$statusCheck = $this->objectManager->get('TYPO3\\CMS\\Install\\SystemEnvironment\\Check');
		$statusObjects = $statusCheck->getStatus();
		/** @var \TYPO3\CMS\Install\Status\StatusUtility $statusUtility */
		$statusUtility = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\StatusUtility');
		$alerts = $statusUtility->filterBySeverity($statusObjects, 'alert');
		$this->view->assign('alerts', $alerts);
		return $this->view->render(!empty($alerts));
	}
}
