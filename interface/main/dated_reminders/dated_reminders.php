<?php
/**
 * Used for displaying dated reminders.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Craig Bezuidenhout <http://www.tajemo.co.za/>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2012 tajemo.co.za <http://www.tajemo.co.za/>
 * @copyright Copyright (c) 2018 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */


// removed as jquery is already called in messages page (if you need to use jQuery, uncomment it futher down)
require_once('../../globals.php');
require_once("$srcdir/dated_reminder_functions.php");

        $days_to_show = 30;
        $alerts_to_show = $GLOBALS['dated_reminders_max_alerts_to_show'];
        $updateDelay = 60; // time is seconds


// ----- get time stamp for start of today, this is used to check for due and overdue reminders
        $today = strtotime(date('Y/m/d'));

 // ----- set $hasAlerts to false, this is used for auto-hiding reminders if there are no due or overdue reminders
        $hasAlerts = false;

// mulitply $updateDelay by 1000 to get miliseconds
        $updateDelay = $updateDelay * 1000;

//-----------------------------------------------------------------------------
// HANDEL AJAX TO MARK REMINDERS AS READ
// Javascript will send a post
// ----------------------------------------------------------------------------
if (isset($_POST['drR'])) {
    if (!verifyCsrfToken($_POST["csrf_token_form"])) {
        csrfNotVerified();
    }

    // set as processed
    setReminderAsProcessed($_POST['drR']);
    // ----- get updated data
    $reminders = RemindersArray($days_to_show, $today, $alerts_to_show);
    // ----- echo for ajax to use
    echo getRemindersHTML($today, $reminders);
    // stop any other output
    exit;
}

//-----------------------------------------------------------------------------
// END HANDEL AJAX TO MARK REMINDERS AS READ
// ----------------------------------------------------------------------------

      $reminders = RemindersArray($days_to_show, $today, $alerts_to_show);

        ?>

      <style type="text/css">
         div.dr{
           margin:0;
           font-size:0.6em;
         }
         .dr_container a{
           font-size:0.8em;
         }
         .dr_container{
           padding:5px 5px 8px 5px;
         }
         .dr_container p{
           margin:6px 0 0 0;
         }
         .patLink{
           font-weight: bolder;
           cursor:pointer;
           text-decoration: none;
         }
         .patLink:hover{
           font-weight: bolder;
           cursor:pointer;
           text-decoration: underline;
         }
      </style>
      <script type="text/javascript">
         $(document).ready(function (){
            $(".hideDR").click(function(){
              if($(this).html() == "<span><?php echo xla('Hide Reminders') ?></span>"){
                $(this).html("<span><?php echo xla('Show Reminders') ?></span>");
                $(".drHide").slideUp("slow");
              }
              else{
                $(this).html("<span><?php echo xla('Hide Reminders') ?></span>");
                $(".drHide").slideDown("slow");
              }
            })
           // run updater after 30 seconds
           var updater = setTimeout("updateme(0)", 1);
         })

           function openAddScreen(id){
             if(id == 0){
               top.restoreSession();
               dlgopen('<?php echo $GLOBALS['webroot']; ?>/interface/main/dated_reminders/dated_reminders_add.php', '_drAdd', 700, 500);
             }else{
               top.restoreSession();
               dlgopen('<?php echo $GLOBALS['webroot']; ?>/interface/main/dated_reminders/dated_reminders_add.php?mID='+encodeURIComponent(id)+'&csrf_token_form=<?php echo attr_url(collectCsrfToken()); ?>', '_drAdd', 700, 500);
             }
           }

           function updateme(id){
             refreshInterval = <?php echo attr($updateDelay); ?>;
             if(id > 0){
              $(".drTD").html('<p style="text-size:3em; margin-left:200px; color:black; font-weight:bold;"><?php echo xla("Processing") ?>...</p>');
             }
             if(id == 'new'){
              $(".drTD").html('<p style="text-size:3em; margin-left:200px; color:black; font-weight:bold;"><?php echo xla("Processing") ?>...</p>');
             }
             top.restoreSession();
             // Send the skip_timeout_reset parameter to not count this as a manual entry in the
             //  timing out mechanism in OpenEMR.
             $.post("<?php echo $GLOBALS['webroot']; ?>/interface/main/dated_reminders/dated_reminders.php",
               {
                drR: id,
                skip_timeout_reset: "1",
                csrf_token_form: "<?php echo attr(collectCsrfToken()); ?>"
               },
               function(data) {
                if(data == 'error'){
                  alert("<?php echo xls('Error Removing Message') ?>");
                }else{
                  if(id > 0){
                    $(".drTD").html('<p style="text-size:3em; margin-left:200px; color:black; font-weight:bold;"><?php echo xla("Refreshing Reminders") ?> ...</p>');
                  }
                  $(".drTD").html(data);
                }
              // run updater every refreshInterval seconds
              var repeater = setTimeout("updateme(0)", refreshInterval);
             });
           }

            function openLogScreen(){
               top.restoreSession();
               dlgopen('<?php echo $GLOBALS['webroot']; ?>/interface/main/dated_reminders/dated_reminders_log.php', '_drLog', 700, 500);
            }


            function goPid(pid) {
              top.restoreSession();
                <?php
                  echo "  top.RTop.location = '../../patient_file/summary/demographics.php' " .
                  "+ '?set_pid=' + pid;\n";
                ?>
}
      </script>

        <?php
          // initialize html string
          $pdHTML = '<div class="container">
                            <div class="drHide col-xs-12">'.
                                '<a title="'.xla('View Past and Future Reminders').'" onclick="openLogScreen()" class="btn btn-default btn-show" href="#"><span>'.xlt('View Log').'</span></a>&nbsp;'
                                .'<a onclick="openAddScreen(0)" class="btn btn-default btn-add" href="#"><span>'.xlt('Create A Dated Reminder').'</span></a>
                            </div>
                            <div class="col-xs-12 pre-scrollable oe-margin-t-10">
                            <fieldset>
                            <legend>'.xla('Dated Reminders').'</legend>
                           <table class="table-condensed">
                            </tr>
                                <td class="drHide drTD">';

          $pdHTML .= getRemindersHTML($today, $reminders);
          $pdHTML .= '</td></tr></table></fieldset></div></div>';
          // print output
          echo $pdHTML;
        ?>
