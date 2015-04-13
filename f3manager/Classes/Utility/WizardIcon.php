<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Philipp Seßner <philipp.sessner@gmail.com>
 *  All rights reserved
 *
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
 * Utility to add the YAG Icon to Element Wizzard
 *
 * @package Utility
 * @author Philipp Seßner <philipp.sessner@gmail.com>
 */
class Tx_f3manager_Utility_WizardIcon {

    /**
     * Adds the formhandler wizard icon
     *
     * @param array Input array with wizard items for plugins
     * @return array Modified input array, having the item for formhandler
     * pi1 added.
     */
    function proc($wizardItems)	{
        $wizardItems['plugins_tx_f3manager_pi1'] = array(
            'icon' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('f3manager') . 'ext_icon-32.png',
            'title' => $GLOBALS['LANG']->sL('LLL:EXT:f3manager/Resources/Private/Language/locallang_be.xlf:plugin.title'),
            'description' => $GLOBALS['LANG']->sL('LLL:EXT:f3manager/Resources/Private/Language/locallang_be.xlf:.description'),
            'params' => '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=f3manager_pi1'
        );

        return $wizardItems;
    }
}
?>