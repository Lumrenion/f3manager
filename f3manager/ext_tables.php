<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	$_EXTKEY,
	'Pi1',
	'LLL:EXT:f3manager/Resources/Private/Language/locallang_be.xlf:plugin.title'
);


/**
 * Register Plugin as Page Content
 */
$extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($_EXTKEY);
$pluginSignature = strtolower($extensionName) . '_pi1';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,recursive,select_key,pages';


/**
 * Register static Typoscript Template
 */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'FAL Frontend File Manager');


/**
 * Register flexform
 */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:'.$_EXTKEY.'/Configuration/FlexForms/FileTree.xml');
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_pi1'] = 'pi_flexform';


// Register file manager for 'contains plugin' in sysfolders
$TCA['pages']['columns']['module']['config']['items'][] = array(
    'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_be.xml:plugin.title',
    'f3fileman',
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'ext_icon.png');
\TYPO3\CMS\Backend\Sprite\SpriteManager::addTcaTypeIcon('pages', 'contains-f3fileman', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'ext_icon.png');

if(TYPO3_MODE == 'BE') {
    $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['Tx_f3manager_Utility_WizardIcon'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Utility/WizardIcon.php';
}


/**
 * Extend Permission-Options for Folders
 */
$tempColumns = array(
    'fe_groups_write' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:f3manager/Resources/Private/Language/locallang_db.xlf:write_permission',
        'config' => array(
            'type' => 'select',
            'size' => 20,
            'maxitems' => 40,
            'items' => array(
                array(
                    'LLL:EXT:f3manager/Resources/Private/Language/locallang_db.xlf:write_any_login',
                    -2
                ),
                array(
                    'LLL:EXT:lang/locallang_general.xlf:LGL.usergroups',
                    '--div--'
                )
            ),
            'exclusiveKeys' => '-1,-2',
            'foreign_table' => 'fe_groups',
            'foreign_table_where' => 'ORDER BY fe_groups.title'
        )
    )
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns("tx_falsecuredownload_folder", $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes("tx_falsecuredownload_folder", "fe_groups_write", "0", "after:fe_groups");