<?php

/**
 *   @file services/staff.php
 *   @brief staff listings
 *
 *   @author adarby
 *   @date august, 2010
 *   @todo
 */

//use SubjectsPlus\Control\Staff;
use SubjectsPlus\Control\OrganisationChartDisplay;
//use SubjectsPlus\Control\CompleteMe;
use SubjectsPlus\Control\Querier;

include("../control/includes/config.php");
include("../control/includes/functions.php");
include("../control/includes/autoloader.php");

// If you have a theme set, but DON'T want to use it for this page, comment out the next line
if (isset($subjects_theme)  && $subjects_theme != "") { include("themes/$subjects_theme/staff.php"); exit;}

$page_title = "Organisation Chart";
$description = "Library contact list.";
$keywords = "staff list, librarians, contacts";

//$use_jquery = array("ui", "ui_styles");

//////////
// Generate List
//////////

//$intro = "<br />";

//$our_cats = array("A-Z", "By Department","Subject Librarians A-Z", "Librarians by Subject Specialty");

//if (!isset($_GET["letter"]) || $_GET["letter"] == "") {$_GET["letter"] = "A-Z";}

//$selected_letter = scrubData($_GET["letter"]);

//$alphabet = getLetters($our_cats, $selected_letter);

//if ($selected_letter == "A-Z") {

$intro = "<p><img src=\"$IconPath/information.png\" alt=\"icon\" /> Click on a name for more information.</p>
<br />";

//}

$objOrgChart = new OrganisationChartDisplay;
$arrStaffDetails = $objOrgChart->getStaffDetails();
include("includes/header.php");

?>
<script type='text/javascript' src='../assets/js/jquery.js'></script>
<link href="../assets/css/shared/jquery.orgchart.css" media="all" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="../assets/js/jquery.orgchart.js"></script>
<script type="text/javascript">
    var testData = [
        {memberId: 1, otherInfo: 'My Organization', parentId: 0},
        {memberId: 2, otherInfo: 'CEO Office', parentId: 1},
        {memberId: 3, otherInfo: 'Division 1', parentId: 1},
        {memberId: 4, otherInfo: 'Division 2', parentId: 3},
        {memberId: 6, otherInfo: 'Division 3', parentId: 4},
        {memberId: 7, otherInfo: 'Division 4', parentId: 4}
        
    ];
	var testData = <?php echo $arrStaffDetails; ?>;
	//var testData = <?php echo $arrStaffDetails; ?>;
	//console.log(testData);
	//testData = $.parseJSON(testData);
	//console.log(testData);
	/* $(function(){
        org_chart = $('#orgChart').orgChart({
            data: testData,
            showControls: true,
            allowEdit: false,
            onAddNode: function(node){ 
                log('Created new node on node '+node.data.id);
                org_chart.newNode(node.data.id); 
            },
            onDeleteNode: function(node){
                log('Deleted node '+node.data.id);
                org_chart.deleteNode(node.data.id); 
            },
            onClickNode: function(node){
                log('Clicked node '+node.data.id);
            }

        });
    });*/

	$(function(){
		var members;
		members = testData;
		for(var i = 0; i < members.length; i++){
		    var member = members[i];
			var imageName = member.otherInfo;
			var src = "<?php echo $UserPath.'/_';?>"+imageName.replace(" ",".")+"/headshot_large.jpg";
			
			var imgSource = '<img src="'+src+'" width=50px height=50px style="padding-bottom:8px;padding-top:8px;">';
			//console.log(src);
			if(i==0){
				$("#mainContainer123").append("<li id="+member.memberId+" class='tooltip'>"+imgSource+member.otherInfo+"<span class='tooltiptext'><em>FROM</em> "+member.department+"</span></li>");
				
			} else {
				if($('#pr_'+member.parentId).length<=0){
				  $('#'+member.parentId).append("<ul id='pr_"+member.parentId+"'><li id="+member.memberId+" class='tooltip'>"+imgSource+member.otherInfo+"<span class='tooltiptext'><em>FROM</em> "+member.department+"</span></li></ul>");
				} else {
				  $('#pr_'+member.parentId).append("<li id="+member.memberId+" class='tooltip'>"+imgSource+member.otherInfo+"<span class='tooltiptext'><em>FROM</em> "+member.department+"</span></li>");
			    }				
			}
		}
		$("#mainContainer123").orgChart({container: $("#main123"),interactive: true, fade: true, speed: 'slow'});	
	});
    // just for example purpose
    function log(text){
        $('#consoleOutput').append('<p>'+text+'</p>')
    }
    </script>
	<style>
	.node{
		width:60px!important;
	}
	table{
		font-size:10px!important;
	}
	.tooltip {
  position: relative;
  display: inline-block;
  border-bottom: 1px dotted black;
}

.tooltip .tooltiptext {
  visibility: hidden;
  width: 120px;
  background-color: black;
  color: #fff;
  text-align: center;
  border-radius: 6px;
  padding: 5px 0;

  /* Position the tooltip */
  position: absolute;
  z-index: 1;
}

.tooltip:hover .tooltiptext {
  visibility: visible;
}
	</style>
<div class="pure-g">
	<div class="pure-u-1 pure-u-lg-2-3 pure-u-xl-4-5">
		<div class="pluslet">
			<div class="titlebar">
				<div class="titlebar_text"><?php print _("Organisation Chart"); ?></div>
			</div>
			<div class="pluslet_body">
				<div id="orgChartContainer">
					<div id="orgChart"></div>
				</div>
			</div>
		</div>
	</div>
</div>
<div  style="display: none">
	<ul id="mainContainer123" class="clearfix"></ul>	
</div>
<div id="main123" style="font-size:7px;line-height:normal;width:fit-content;"></div>
<?php

////////////
// Footer
///////////

include("includes/footer.php");

?>