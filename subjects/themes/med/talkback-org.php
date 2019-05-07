<?php
/**
 * @file talback.php
 * @brief Suggestion box public interface.  based on file in subjectsplus, talkback.php
 * @description When someone submits a comment, it will be scrubbed and added to
 *   the talkback table in the db.  If you want to receive an email when something
 *   comes in, leave $send_email_notification set to "1".  By default, this email
 *   goes to the person set as the admin in config.php; if you want to change this
 *   to someone else, do so below
 *   This email function uses the PHP mail() function; if this doesn't work in
 *   your environment, turn $send_email_notification off
 *   In version 2.x of SP, there are now filters to add different talkback instances
 *
 * @author adarby
 * @date update aug 2014
 * @todo
 */

use SubjectsPlus\Control\Querier;


$db = new Querier;

$use_jquery = array();

/* Set local variables */

$page_title       = _( "Comments" );
$page_description = _( "Share your comments and suggestions about the library" );
$page_keywords    = _( "library, comments, suggestions, complaints" );

// Skill testing question + answer
$stk        = _( "5 times 4 = " );
$stk_answer = "20";

// Show headshots
$show_talkback_face = 1;

$form_action  = "talkback.php"; // this can be overriden below
$bonus_sql    = ""; // ditto
$set_filter   = ""; // tritto
$tb_bonus_css = ""; //fritto
$cat_filters  = ""; // hawaii five-o

/////////////////////////
// Deal with multiple talkback instances
// Usually if you have branch libraries who want separate
// pages/results
////////////////////////

if ( isset( $all_tbtags ) ) {
// Let's get the first item off the tb array to use as our default
	reset( $all_tbtags ); // make sure array pointer is at first element
	$set_filter = key( $all_tbtags );

// And set our default bonus sql
	$bonus_sql = "AND tbtags LIKE '%" . $set_filter . "%'";

// determine branch/filter
	if ( isset( $_REQUEST["v"] ) ) {
		$set_filter = scrubData( lcfirst( $_REQUEST["v"] ) );

		// Quick'n'dirty setup email recipients
		switch ( $set_filter ) {
			case "music":
				$page_title   = "Comments for the Music Library";
				$form_action  = "talkback.php?v=$set_filter";
				$tb_bonus_css = "talkback_form_music";
				break;
			case "rsmas":
				$page_title  = "Comments for the Marine Library";
				$form_action = "talkback.php?v=$set_filter";
				break;
			default:
				$set_filter = "calder";
				// nothing, we just use the $administrator email on file (config.php)
				$form_action = "talkback.php";
		}
		// override our admin email
		if ( isset( $all_tbtags[ $set_filter ] ) && $all_tbtags[ $set_filter ] != "" ) {
			$administrator_email = $all_tbtags[ $set_filter ];
		}

	} else {
		$set_filter = "calder";
	}
}

///////////////////////
// Our Topic Filters
///////////////////////

if ( isset( $all_cattags ) ) {

	foreach ( $all_cattags as $value ) {
		if ( isset( $_GET["c"] ) && $value == $_GET["c"] ) {
			$tag_class  = "ctag-on";
			$cat_filter = $value;
		} else {
			$tag_class = "";
		}
		$cat_filters .= " <a href=\"talkback.php?v=$set_filter&c=$value\" class=\"$tag_class\">$value</a>";
	}
}

if ( isset( $_GET['c'] ) ) {
	if ( in_array( $_GET['c'], $all_cattags ) ) {
		$bonus_sql .= " AND cattags LIKE '%" . $_GET['c'] . "%'";
	}
}

///////////////////////
// Feedback
///////////////////////

$feedback = "";

$submission_feedback = "
<div class=\"talkback-message talkback-success\">\n
<h2>" . _( "Thanks" ) . "</h2>\n
<div class=\"talkback-message-body\">\n
<p>" . _( "Thank you for your feedback.  We will try to post a response within the next three business days." ) . "</p>\n
</div>\n
</div>\n
";

$submission_failure_feedback = "
<div class=\"talkback-message talkback-error\">\n
<h2>" . _( "Oh dear." ) . "</h2>\n
<div class=\"talkback-message-body\">\n
<p>" . _( "There was a problem with your submission.  Please try again." ) . "</p>
<p>" . _( "If you continue to get an error, please contact the <a href=\"mailto:$administrator_email\">administrator</a>" ) . "
</div>\n
</div>\n";

//////////////////////
// Some email stuff
//////////////////////

$send_email_notification = 1;
$send_to                 = $administrator_email;
/* Use any ol' email address as from, to make sure the mail works */
$sent_from = $administrator_email;

// clean up post variables
if ( isset( $_POST["name"] ) ) {
	$this_name = scrubData( $_POST["name"] );
} else {
	$this_name = "";
}

if ( isset( $_POST["the_suggestion"] ) ) {
	$this_comment = scrubData( $_POST["the_suggestion"] );
} else {
	$this_comment = "";
}

//////////////////////
// date and time stuff
//////////////////////

$today     = getdate();
$month     = $today['month'];
$mday      = $today['mday'];
$year      = $today['year'];
$this_year = date( "Y" );

$todaycomputer = date( 'Y-m-d H:i:s' );

// let's do the blacklister first

if ( BlackLister( $this_comment ) == true ) {
	// we'll pretend it was an okay submission
	$feedback     = $submission_feedback;
	$this_name    = "";
	$this_comment = "";
	$stage_two    = "ok";

} elseif ( isset( $_POST['the_suggestion'] ) ) {

// clean submission and enter into db!  Don't show page again.

	// Call the function post_captcha
	$res = post_captcha( $_POST['g-recaptcha-response'] );

	if ( ! $res['success'] ) {
		// What happens when the reCAPTCHA is not properly set up
		$feedback = $submission_failure_feedback;

	} else {
		// If CAPTCHA is successful...

		if ( $this_name == "" ) {
			$this_name = "Anonymous";
		}

		// Make a safe query
		$connection = $db->getConnection();
		$statement  = $connection->prepare( "INSERT INTO talkback (question, q_from, date_submitted, display, tbtags, answer)
			VALUES (:question, :q_from, :date_submitted, 'No', :tbtags, '')" );

		$statement->bindParam( ":question", $this_comment );
		$statement->bindParam( ":q_from", $this_name );
		$statement->bindParam( ":date_submitted", $todaycomputer );
		$statement->bindParam( ":tbtags", $set_filter );
		$statement->execute();

		$stage_one = "ok";


		if ( isset( $debugger ) && $debugger == "yes" ) {
			//	print "<p class=\"debugger\">$query<br /><strong>from</strong> this file</p>";
		}

		// Send an email if this is turned on
		if ( $send_email_notification == 1 ) {
			ini_set( "SMTP", $email_server );
			ini_set( "sendmail_from", $sent_from );

			/* here the subject and header are assembled */

			$subject = _( "New Comment via SubjectsPlus" );
			$header  = "Return-Path: $sent_from\n";
			$header  .= "From:  $sent_from\n";
			$header  .= "Content-Type: text/html; charset=iso-8859-1;\n\n";

			$message = "<html><body style=\"margin:0;\">
					<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" bgcolor=\"#d4d4d4\" style=\"height: 100%;\">
						<tr>
						<td valign=\"top\" align=\"center\">
						<table cellpadding=\"0\" cellspacing=\"0\" bgcolor=\"#FFFFFF\" style=\"width:600px; height:auto;\" border=\"0\">
						  <tr>
						     <td width=\"600\" height=\"40\" valign=\"top\" bgcolor=\"#d4d4d4\">&nbsp;</td>
						  </tr>
						  <tr>
						     <td width=\"600\" height=\"120\" valign=\"middle\" align=\"center\" bgcolor=\"#FFFFFF\">                
						          <p style=\"font-size:28px; color:#444; font-family:Helvetica, sans-serif;\">" . _( "New Comment Awaits Response" ) . "</p>
						      </td>
						  </tr>     
						  <tr>
							   <td width=\"600\" height=\"60\" valign=\"top\" align=\"center\" bgcolor=\"#FFFFFF\">                
						        <table width=\"600\" height=\"40\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" bgcolor=\"#FFFFFF\">
						            <tr>
						              <td width=\"10\" valign=\"top\" height=\"40\" bgcolor=\"#FFFFFF\">&nbsp;</td>
						              <td width=\"50\" valign=\"top\" height=\"40\" bgcolor=\"#FFFFFF\">
						                  <img src=\"http://sp.library.miami.edu/assets/images/email/calendar.jpg\" width=\"40\" height=\"40\" border=\"0\">
						              </td>
						              <td width=\"150\" valign=\"bottom\" height=\"40\" bgcolor=\"#FFFFFF\">
						                  <p style=\"font-size:22px; color:#444; font-family:Helvetica, sans-serif;\">" . _( "Received:" ) . "</p>
						              </td>
						               <td width=\"380\" valign=\"bottom\" height=\"40\" bgcolor=\"#FFFFFF\">
						                  <p style=\"font-size:22px; color:#858585; font-family:Helvetica, sans-serif;\">$month $mday, $year</p>
						              </td>
						              <td width=\"10\" valign=\"top\" height=\"40\" bgcolor=\"#FFFFFF\">&nbsp;</td>
						            </tr>
						          </table>
						      </td>
						  </tr>   
						  <tr>
						     <td width=\"600\" height=\"60\" valign=\"top\" align=\"center\" bgcolor=\"#FFFFFF\">                
						        <table width=\"600\" height=\"40\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" bgcolor=\"#FFFFFF\">
						            <tr>
						              <td width=\"10\" valign=\"top\" height=\"40\" bgcolor=\"#FFFFFF\">&nbsp;</td>
						              <td width=\"50\" valign=\"top\" height=\"40\" bgcolor=\"#FFFFFF\">
						                  <img src=\"http://sp.library.miami.edu/assets/images/email/contact.jpg\" width=\"40\" height=\"40\" border=\"0\">
						              </td>
						              <td width=\"150\" valign=\"bottom\" height=\"40\" bgcolor=\"#FFFFFF\">
						                  <p style=\"font-size:22px; color:#444; font-family:Helvetica, sans-serif;\">" . _( "Contact:" ) . "</p>
						              </td>
						               <td width=\"380\" valign=\"bottom\" height=\"40\" bgcolor=\"#FFFFFF\">
						                  <p style=\"font-size:22px; color:#858585; font-family:Helvetica, sans-serif;\">";
			$message .= $db->quote( $this_name );

			$message .= "</p></td>
						              <td width=\"10\" valign=\"top\" height=\"40\" bgcolor=\"#FFFFFF\">&nbsp;</td>
						            </tr>
						          </table>
						      </td>
						  </tr>  
						  <tr>
						     <td width=\"600\" height=\"65\" valign=\"top\" align=\"center\" bgcolor=\"#FFFFFF\">                
						        <table width=\"600\" height=\"40\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" bgcolor=\"#FFFFFF\">
						            <tr>
						              <td width=\"10\" valign=\"top\" height=\"40\" bgcolor=\"#FFFFFF\">&nbsp;</td>
						              <td width=\"50\" valign=\"top\" height=\"40\" bgcolor=\"#FFFFFF\">
						                  <img src=\"http://sp.library.miami.edu/assets/images/email/comment.jpg\" width=\"40\" height=\"40\" border=\"0\">
						              </td>
						              <td width=\"530\" valign=\"middle\" height=\"40\" bgcolor=\"#FFFFFF\">
						                  <p style=\"font-size:22px; color:#444; font-family:Helvetica, sans-serif;\">" . _( "Comment:" ) . "</p>
						              </td>              
						              <td width=\"10\" valign=\"top\" height=\"40\" bgcolor=\"#FFFFFF\">&nbsp;</td>
						            </tr>
						          </table>
						      </td>
						  </tr> 						  
						  <tr>
						     <td width=\"600\" valign=\"top\" align=\"center\" bgcolor=\"#FFFFFF\">                
						        <table width=\"600\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" bgcolor=\"#FFFFFF\">
						            <tr>
						              <td width=\"60\" valign=\"top\" bgcolor=\"#FFFFFF\">&nbsp;</td>              
						              <td width=\"530\" valign=\"top\" bgcolor=\"#FFFFFF\">
						                  <p style=\"font-size:20px; color:#858585; font-family:Helvetica, sans-serif;\">";

			$message .= $db->quote( $this_comment );
			$message .= "</p>
						              </td>              
						              <td width=\"10\" valign=\"top\" bgcolor=\"#FFFFFF\">&nbsp;</td>
						            </tr>
						          </table>
						      </td>
						  </tr> 
						  <tr>
						     <td width=\"600\" height=\"60\" valign=\"top\" bgcolor=\"#FFFFFF\">&nbsp;</td>
						  </tr>      
						  <tr>
						     <td width=\"600\" height=\"50\" valign=\"top\" align=\"center\" bgcolor=\"#FFFFFF\">                
						        <table width=\"600\" height=\"50\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" bgcolor=\"#FFFFFF\">
						            <tr>
						              <td width=\"175\" height=\"50\" valign=\"middle\" bgcolor=\"#FFFFFF\">&nbsp;</td>              
						              <td width=\"250\" height=\"50\" valign=\"middle\" align=\"center\" bgcolor=\"#858585\">
						                  <p style=\"font-size:28px; color:#FFF; font-family:Helvetica, sans-serif;\"><a href=\"http://sp.library.miami.edu/control/talkback\" target=\"_blank\" style=\"color: #FFF; text-decoration:none;\"><span style=\"color: #FFF; text-decoration:none;\">" . _( "Reply Now" ) . "</span></a></p>
						              </td>              
						              <td width=\"175\" height=\"50\" valign=\"middle\" bgcolor=\"#FFFFFF\">&nbsp;</td>
						            </tr>
						          </table>
						      </td>
						  </tr>
						  <tr>
						     <td width=\"600\"  height=\"30\" valign=\"bottom\" align=\"center\" bgcolor=\"#FFFFFF\">
						     	<p style=\"font-size:14px; color:#858585; font-family:Helvetica, sans-serif;\">" . _( "You will be required to log in" ) . "</p>
						     </td>
						  </tr>      
						  <tr>
						     <td width=\"600\"  height=\"100\" valign=\"top\" bgcolor=\"#FFFFFF\">&nbsp;</td>
						  </tr>       
						  <tr>
						     <td width=\"600\" height=\"70\" valign=\"middle\" align=\"center\" bgcolor=\"#FFFFFF\">
						        <img src=\"http://sp.library.miami.edu/assets/images/email/subjectsplus-footer.jpg\" width=\"276\" height=\"40\" border=\"0\">
						      </td>
						  </tr>
						</table>            
						</td>
						</tr>
						</table>
						</body>
						</html>";

			// begin assembling actual message

			$success = mail( $send_to, "$subject", $message, $header );
			// The below is just for testing purposes
			if ( $success ) {
				$stage_two = "ok";
				//print "mail sent to $send_to";
			} else {
				$stage_two = "fail";
				//print "mail didn't go to $send_to";
			}
		}

		if ( $stage_one == "ok" ) {
			$feedback     = $submission_feedback;
			$this_name    = "";
			$this_comment = "";
		} else {
			$feedback = $submission_failure_feedback;
		}
	}


}

////////////////////
// Display the page
////////////////////

if ( isset( $_GET["t"] ) && $_GET["t"] == "prev" ) {
	$db         = new Querier;
	$connection = $db->getConnection();
	$statement  = $connection->prepare( "SELECT talkback_id, question, q_from, date_submitted, DATE_FORMAT(date_submitted, '%b %d %Y') as thedate, 
	answer, a_from, fname, lname, email, staff.title, YEAR(date_submitted) as theyear
	FROM talkback LEFT JOIN staff 
	ON talkback.a_from = staff.staff_id 
	WHERE (display ='1' OR display ='Yes') 
	AND tbtags LIKE :tbtags
    AND cattags LIKE :ctags
	AND YEAR(date_submitted) < :year 
	GROUP BY theyear, date_submitted ORDER BY date_submitted DESC" );

	$filter = '%' . $set_filter . '%';

	if ( isset( $_GET['c'] ) ) {
		$cat_tags = '%' . scrubData( $_GET['c'] ) . '%';

	} else {
		$cat_tags = "%%";

	}

	$statement->bindParam( ":year", $this_year );
	$statement->bindParam( ":tbtags", $filter );
	$statement->bindParam( ":ctags", $cat_tags );

	$statement->execute();


	$our_result = $statement->fetchAll();

	$comment_header = "<h2>" . _( "Comments from Previous Years" ) . " <span style=\"font-size: 12px;\"><a href=\"talkback.php?v=$set_filter\" class=\"talkback-link\">" . _( "See this year" ) . "</a></span></h2>";

} elseif ( isset( $_GET["c"] ) ) {

	$db         = new Querier;
	$connection = $db->getConnection();
	$statement  = $connection->prepare( "SELECT talkback_id, question, q_from, date_submitted, DATE_FORMAT(date_submitted, '%b %d %Y') as thedate, answer, a_from, fname, lname, email, staff.title, YEAR(date_submitted) as theyear
	FROM talkback LEFT JOIN staff 
	ON talkback.a_from = staff.staff_id 
	WHERE (display ='1' OR display ='Yes') 
	AND tbtags LIKE :tbtags
  AND cattags LIKE :ctags
	GROUP BY theyear, date_submitted ORDER BY date_submitted DESC" );


	$filter = '%' . $set_filter . '%';

	if ( isset( $_GET['c'] ) ) {
		$cat_tags = '%' . scrubData( $_GET['c'] ) . '%';

	} else {
		$cat_tags = "%%";
	}

	$statement->bindParam( ":tbtags", $filter );
	$statement->bindParam( ":ctags", $cat_tags );

	$statement->execute();


	$our_result = $statement->fetchAll();

	$comment_header = "<h2>" . _( "Comments about " ) . scrubData( $_GET['c'] ) . " <span style=\"font-size: 12px;\"><a href=\"talkback.php?v=$set_filter\" class=\"talkback-link\">" . _( "See all for this year" ) . "</a></span></h2>";

} else {
	// New ones //

	$db         = new Querier;
	$connection = $db->getConnection();
	$statement  = $connection->prepare( "SELECT talkback_id, question, q_from, date_submitted, DATE_FORMAT(date_submitted, '%b %d %Y') as thedate,
	answer, a_from, fname, lname, email, staff.title, YEAR(date_submitted) as theyear
	FROM talkback LEFT JOIN staff
	ON talkback.a_from = staff.staff_id
	WHERE (display ='1' OR display ='Yes')
    AND tbtags LIKE :tbtags
	AND cattags LIKE :ctags
	AND YEAR(date_submitted) >= :year
	ORDER BY date_submitted DESC" );

	$statement->bindParam( ":year", $this_year );
	$filter = '%' . $set_filter . '%';
	if ( isset( $_GET['c'] ) ) {
		$cat_tags = '%' . scrubData( $_GET['c'] ) . '%';

	} else {
		$cat_tags = "%%";

	}
	//AND tbtags LIKE :tbtags
	$statement->bindParam( ":tbtags", $filter );
	$statement->bindParam( ":ctags", $cat_tags );
	$statement->execute();


	$our_result = $statement->fetchAll();


	$comment_header = "<h2>" . _( "Comments from " ) . "$this_year <span style=\"font-size: 11px; font-weight: normal;\"><a href=\"talkback.php?t=prev&v=$set_filter\" class=\"talkback-link\">" . _( "See previous years" ) . "</a></span></h2>";

}

/* Select all Records, either current or previous year*/

$result_count = count( $our_result );

if ( $result_count != 0 ) {

	$row_count = 1;
	$results   = "";

	foreach ( $our_result as $myrow ) {

		$talkback_id = $myrow["0"];
		$question    = $myrow["1"];
		$answer      = $myrow["5"];
		$answer      = preg_replace( '/<\/?div.*?>/ ', '', $answer );
		$answer      = tokenizeText( $answer );
		// $answer = stripslashes(htmlspecialchars_decode($myrow["5"])); Louisa's proposed fix for messy answer @todo
		$keywords        = $myrow["3"];
		$responder_email = $myrow["9"];

		// Let's link back to the staff page
		$name_id  = explode( "@", $responder_email );
		$lib_page = "staff_details.php?name=" . $name_id[0];

		$results .= "
		<div class=\"tellus_item oddrow\">\n
		<a name=\"$talkback_id\"></a>\n
		<p class=\"tellus_comment\"><span class=\"comment_num\">$row_count</span> <strong>$question</strong><br />
		   <span style=\"clear: both;font-size: 11px;\">Comment on $myrow[4] </span>
		</p>";
		if ( $show_talkback_face == 1 ) {
			$results .= getHeadshot( $myrow[9] );
		}
		$results .= $answer;
		$results .= "<p style=\"clear: both;font-size: 11px;\">Answered by <a href=\"$lib_page\">$myrow[7] $myrow[8]</a>, $myrow[10]</p></div>\n";

		// Add 1 to the row count, for the "even/odd" row striping

		$row_count ++;
	}
} else {
	$results    = "<p>" . _( "There are no comments just yet.  Be the first!" ) . "</p>";
	$no_results = true;
}


///////////////////
// Incomplete Comment submission
///////////////////

//if (isset($_POST['skill']) and $_POST['skill'] != $stk_answer) {
//
//	$stk_message = "
//	<div class=\"talkback-message talkback-error\">\n
//	<h2>" ._("Hmm, That Was a Tricky Bit of Math") . "</h2>\n
//	<div class=\"talkback-message-body\">\n
//	<p>" . _("Sorry, you must answer the Skill Testing Question correctly.  It's an anti-spam measure . . . .") . "</p>
//	</div>\n
//	</div>\n
//	";
//
//} else {
//	$stk_message = "";
//}


include( "includes/header_med.php" );

?>

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        function onSubmit(token) {
            document.getElementById("tellus").submit();
        }
    </script>

    <div class="panel-container">
        <div class="pure-g">

            <div class="pure-u-1 pure-u-lg-3-4 panel-adj">
                <div class="breather">
					<?php print $feedback; ?>

					<?php print _( "<p>Please use this page to write comments or make suggestions about Library services, resources, and facilities.</p>
 
        <p>Suitable comments will be posted along with Library responses.</p>

        <p class=\"response-link\"><a href=\"#tellus\">Submit your response</a></p>" ); ?>

                    <div id="letterhead_small" align="center"><?php print $cat_filters; ?></div>
					<?php print $comment_header . $results; ?>

                </div>
            </div> <!--end 3/4 main area column-->

            <div class="pure-u-1 pure-u-lg-1-4 database-page sidebar-bkg">
                <div class="tip">
                    <h2>Need help <strong>now</strong>? <br/><a href="http://calder.med.miami.edu/librarianask.html">Ask
                            a Librarian</a>.</h2>
					<?php if ( isset( $stage_two ) ) {
						print "<p>" . _( "Thank you for your submission." ) . "<a href=\"talkback.php\">" . _( "Did you want to say something else?" ) . "</a>";
					} else { ?>

                        <form id="tellus" action="<?php print $form_action; ?>" method="post" class="pure-form">
                            <div class="talkback_form <?php print $tb_bonus_css; ?>">
                                <p><strong><?php print _( "Your comment:" ); ?></strong><br/>
                                    <textarea name="the_suggestion" cols="26" rows="6" class="form-item"
                                              value="<?php print $this_comment; ?>"></textarea><br/><br/>
                                    <strong><?php print _( "Your email (optional):" ); ?></strong><br/>
                                    <input type="text" name="name" size="20" value="<?php print $this_name; ?>"
                                           class="form-item"/>
                                    <br/>
									<?php print _( "(In case we need to contact you)" ); ?>

                                    <br/><br/>
									<?php global $talkback_recaptcha_site_key; ?>
                                    <button type="submit" name="submit_comment"
                                            class="pure-button pure-button-topsearch g-recaptcha"
                                            data-sitekey="<?php echo $talkback_recaptcha_site_key; ?>"
                                            data-callback="onSubmit"
                                            data-size="invisible"><?php print _( "Submit" ); ?></button>
                                </p>
                            </div>
                        </form>
					<?php } ?>
                    <p>
                </div>
                <div class="tipend"></div>

            </div><!--end 1/4 sidebar column-->

        </div> <!--end pure-g-->
    </div> <!--end panel-container-->


<?php

///////////////////////////
// Load footer file
///////////////////////////

include( "includes/footer_um.php" );

///////////////////
// Blacklister Function
/////////////////////

function BlackLister( $checkstring ) {
	$blacklist_terms = "viagra|cialis";

	if ( preg_match( "/$blacklist_terms/i", $checkstring ) ) {
		// found naughtiness
		return true;
	} else {
		return false;
	}

}

?>