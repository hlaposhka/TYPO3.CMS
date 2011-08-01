<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Rens Admiraal <r.admiraal@drecomm.nl>
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
 * View object for the upload element
 *
 * @author Rens Admiraal <r.admiraal@drecomm.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_view_confirmation_element_fileupload extends tx_form_view_confirmation_element_abstract {

	/**
	 * Default layout of this object
	 *
	 * @var string
	 */
	protected $layout = '
		<label />
		<inputvalue />
	';

	/**
	 * Constructor
	 *
	 * @param tx_form_domain_model_element_fileupload $model Model for this element
	 * @return void
	 */
	public function __construct(tx_form_domain_model_element_fileupload $model) {
		parent::__construct($model);
	}
}
?>