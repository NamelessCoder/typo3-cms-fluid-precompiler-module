/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: TYPO3/CMS/CmsFluidPreCompilerModule/NodeVisualiser
 */
define(['jquery'], function($) {

    /**
     *
     * @type {{}}
     * @exports TYPO3/CMS/CmsFluidPreCompilerModule/NodeVisualiser
     */
    var NodeVisualiser = {};

    $('.expand-collapse').click(function clickToggle() {
        $('tr.childof-' + $(this).attr('id').substring(7)).toggleClass('hidden');
        $(this).find('span').toggleClass('fa-plus-square').toggleClass('fa-minus-square');
    });

    $('#expandall').click(function() {
        $('a.expand-collapse').each(function() {
            if ($(this).find('.fa-plus-square').length) {
                $(this).click();
            }
        });
        $(this).addClass('hidden');
        $('#collapseall').removeClass('hidden');
    });

    $('#collapseall').click(function() {
        $('a.expand-collapse').each(function() {
            if ($(this).find('.fa-minus-square').length) {
                $(this).click();
            }
        });
        $(this).addClass('hidden');
        $('#expandall').removeClass('hidden');
    });

    return NodeVisualiser;
});