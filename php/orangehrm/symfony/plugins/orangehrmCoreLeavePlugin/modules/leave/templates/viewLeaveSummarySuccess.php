<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */
?>
<script type="text/javascript" src="<?php echo public_path('../../scripts/jquery/jquery.js')?>"></script>

<link href="<?php echo public_path('../../themes/orange/css/jquery/jquery.autocomplete.css')?>" rel="stylesheet" type="text/css"/>

<?php echo stylesheet_tag('../orangehrmCoreLeavePlugin/css/viewLeaveSummarySuccess');?>

<?php if (isset($form->recordsCount) && $form->recordsCount == 0 && isset($searchFlag) && $searchFlag == 1) { ?>
<div class="messageBalloon_notice" style="width:730px;"><?php echo __('No Results Found for This Criteria'); ?></div>
<?php } ?>

<!--[if IE]>
<style type="text/css">
    #leaveSummary_txtEmpName {
        width: 195px;
    }
</style>
<![endif]-->

<form id="frmLeaveSummarySearch" name="frmLeaveSummarySearch" method="post" action="<?php echo url_for('leave/viewLeaveSummary'); ?>">
<div class="outerbox" style="width: 850px;">
<div class="mainHeading"><h2><?php echo __('Leave Summary') ?></h2></div>
    <div class="searchbar">
        <?php echo $form->render(); ?>
        <br class="clear" />

        <div class="formbuttons">
            <input type="hidden" name="pageNo" id="pageNo" value="<?php echo $form->pageNo; ?>" />
            <input type="hidden" name="hdnAction" id="hdnAction" value="search" />
            <input type="button" name="btnSearch" id="btnSearch" value="<?php echo __('Search') ?>" class="savebutton" />
            <?php if ($form->userType == 'Admin' || $form->userType == 'Supervisor') { ?>
            <input type="reset" id="btnReset" value="<?php echo __('Reset') ?>" class="savebutton" />
            <?php } ?>
        </div>
    </div>
</div>

<?php if ($form->saveSuccess) { ?>
<div class="messageBalloon_success"><?php echo __('Leave Entitlements Successfully Saved'); ?></div>
<?php } ?>
<div id="validationMsg"></div>
<?php //if ($form->recordsCount > 0) { ?>

<?php include_component('core', 'ohrmList'); ?>

</form> <!-- End of frmLeaveSummarySearch -->

<?php //} // End of if ($recordsCount > 0) ?>

<script type="text/javascript">
    
    var lang_typeHint = "<?php echo __("Type for hints");?>" + "...";
    
    $(document).ready(function() {
        
        if ($("#leaveSummary_txtEmpName").val() == "" || $("#leaveSummary_txtEmpName").val() == lang_typeHint) {
            $("#leaveSummary_txtEmpName").addClass("inputFormatHint").val(lang_typeHint);
        }
        
        $("#leaveSummary_txtEmpName").one('focus', function() {
            if ($(this).hasClass("inputFormatHint")) {
                $(this).val("");
                $(this).removeClass("inputFormatHint");
            }
        });
        
    });

/* Define language strings here */
var lang_not_numeric = '<?php echo __('Enter a Positive Number Less Than 365 with Two Decimal Places'); ?>';

/* Employee list */

var empdata = <?php echo str_replace('&#039;',"'",$form->getEmployeeListAsJson());?>;


function submitPage(pageNo) {

    document.frmLeaveSummarySearch.pageNo.value = pageNo;
    document.frmLeaveSummarySearch.hdnAction.value = 'paging';
    document.getElementById('frmLeaveSummarySearch').submit();

}

var editButtonCaption = "<?php echo __('Edit');?>";
var saveButtonCaption = "<?php echo __('Save');?>";
</script>

<script type="text/javascript" src="<?php echo public_path('../../scripts/jquery/jquery.autocomplete.js')?>"></script>