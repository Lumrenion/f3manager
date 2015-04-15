.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt

.. _misc:

RealURL-Configuration
=====================

Pretty URLs can partly be done with RealURL. At least the controller and action can be displayed nicely. Just the folder has to be displayed as is because the folder structure and therefore the identifier of the folder uses slashes which would be interpreted by RealUrl in the wrong way.
If that's not the matter you can set

.. code-block:: php

    $TYPO3_CONF_VARS['EXTCONF']['realurl']['_DEFAULT']['postVarSets']['_DEFAULT']['f3manager'] = array(
        array(
            'GETvar' => 'tx_f3manager_pi1[action]',
        ),
        array(
            'GETvar' => 'tx_f3manager_pi1[controller]',
        ),
        array(
            //This does not work because the folder-path uses slashes
            //'GETvar' => 'tx_f3manager_pi1[openFolder]',
        ),
    );

This will create an URL like
http://www.example.com/path/to/your/site/f3manager/openFolder/FileManager/index.html?tx_f3manager_pi1[openFolder]=%2Ffolder1%2F

Todo
====

Complete this document
Check for Ke-Search and Solr FAL Support
