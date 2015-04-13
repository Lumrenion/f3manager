<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'LumIT.' . $_EXTKEY,
	'Pi1',
	array(
		'FileManager' => 'openFolder, createFolder, renameFolder, downloadFolder, uploadZip, deleteFolder, renameFiles, uploadFiles, downloadMultipleFiles, deleteFiles',
		
	),
	// non-cacheable actions
	array(
		'FileManager' => 'openFolder, createFolder, renameFolder, downloadFolder, uploadZip, deleteFolder, renameFiles, uploadFiles, downloadMultipleFiles, deleteFiles',
		
	)
);

$TYPO3_CONF_VARS['FE']['eID_include']['f3manager'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('f3manager').'Classes/EidDispatcher.php';

if (TYPO3_MODE === 'BE') {
    // Page module hook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['f3manager_pi1'][$_EXTKEY] =
        'LumIT\\F3manager\\Hooks\\CmsLayout->getExtensionSummary';
}