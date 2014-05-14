<?php
/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */

/**
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http://yourdomain/min/builder/
 *
 * See http://code.google.com/p/minify/wiki/CustomSource for other ideas
 **/

$groupBasePath      = "/".substr($_SERVER["SCRIPT_NAME"],0,-14);

return array(
    'headerCss' => array(
        $groupBasePath."/public/bootstrap/css/bootstrap.css",
        $groupBasePath."/public/bootstrap/css/bootstrap-theme.css",
       	$groupBasePath."/basic/css/default.css",
    ),
    'headerJs' => array(
        $groupBasePath."/public/Jquery/jquery-1.8.2.js",
        $groupBasePath."/public/date.js",
        $groupBasePath."/public/bootstrap/js/bootstrap.js",
        $groupBasePath."/basic/js/selectselectselect.js"
    ),
    'footerCss'	=> array(
        $groupBasePath."/public/explain_prompt/example.css",
        $groupBasePath."/basic/js/explain_prompt/explain.imprompt.css",
        $groupBasePath."/public/artDialog5/skins/default.css",
    ),
    'footerJs'	=> array(
        $groupBasePath."/public/artDialog5/source/jquery.artDialog.js",
        $groupBasePath."/public/artDialog5/source/artDialog.plugins.js",
        $groupBasePath."/public/explain_prompt/jquery-impromptu.4.0.js",
        $groupBasePath."/basic/js/explain_prompt/explain.impromptu.js",
        $groupBasePath."/public/jquery-imgpreview.js",
        $groupBasePath."/basic/js/DxShowMessage.js",
        $groupBasePath."/basic/js/dxFunction.js",
        $groupBasePath."/public/angular.js",
        $groupBasePath."/basic/js/angular_ext.js",
        $groupBasePath."/basic/js/pop.js",
    ),
    'dataListCss'    => array(
        $groupBasePath."/public/sigma_grid/gt_grid.css",
        $groupBasePath."/public/sigma_grid/skin/default/skinstyle.css",
        $groupBasePath."/basic/css/sigma_grid.css",
        $groupBasePath."/public/validate/css/validationEngine.jquery.css"
        ),
    'dataListJs'    => array(
        $groupBasePath."/public/sigma_grid/gt_msg_cn.js",
        $groupBasePath."/basic/js/sigma_custom/fix.toolbar.js",
        $groupBasePath."/basic/js/DataOpe.js",
        $groupBasePath."/basic/js/dataope_ext.js",
        $groupBasePath."/public/validate/js/jquery.validationEngine.js",
        $groupBasePath."/public/validate/js/languages/jquery.validationEngine-zh_CN.js",
        $groupBasePath."/basic/js/validate.js",
        ),
    'dataEditJs'    => array(
        $groupBasePath."/public/validate/js/jquery.validationEngine.js",
        $groupBasePath."/public/validate/js/languages/jquery.validationEngine-zh_CN.js",
        $groupBasePath."/basic/js/validate.js",
        $groupBasePath."/public/jquery-upload-file/js/vendor/jquery.ui.widget.js",
        $groupBasePath."/public/jquery-upload-file/js/jquery.iframe-transport.js",
        $groupBasePath."/public/jquery-upload-file/js/jquery.fileupload.js",
        $groupBasePath."/public/jquery-upload-file/js/jquery.fileupload-fp.js",
        $groupBasePath."/public/jquery-upload-file/js/jquery.fileupload-ui.js",
        $groupBasePath."/public/json2.js",
        )
);
