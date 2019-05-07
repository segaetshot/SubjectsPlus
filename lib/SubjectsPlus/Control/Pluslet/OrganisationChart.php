<?php
/**
 *   @file OrganisationChart.php
 *   @brief This is to generate 
 *   @author agdarby
 *   @date Nov 2015
 */
namespace SubjectsPlus\Control;
require_once("Pluslet.php");

class Pluslet_OrganisationChart extends Pluslet {

  public function __construct($pluslet_id, $flag="", $subject_id, $isclone=0) {
    parent::__construct($pluslet_id, $flag, $subject_id, $isclone);
		$this->_title = 'Organisation Chart';
		$this->_type = 'OrganisationChart';
		$this->db = new Querier ();
  }

  protected function onEditOutput()
  {
  	$output = $this->outputExperts();
    $this->_body = $output;
  }

  protected function onViewOutput()
  {
	$output = $this->outputExperts();
    $this->_body = $output;
  }

  public function outputExperts() {
	
global $mod_rewrite;
  global $PublicPath;
  global $guide_types;

    // let's use our Pretty URLs if mod_rewrite = TRUE or 1
    if ($mod_rewrite == 1) {
       $guide_path = "";
    } else {
       $guide_path = $PublicPath . "guide.php?subject=";
    }

    // get all of our librarian experts into an array
    $q = "SELECT DISTINCT (s.staff_id), CONCAT(s.fname, ' ', s.lname) AS fullname, s.email, s.tel, s.title, sub.subject  FROM staff s, staff_subject ss, subject sub
          WHERE s.staff_id = ss.staff_id
          AND ss.subject_id = sub.subject_id
          AND s.active = 1
          AND sub.active = 1
          AND ptags LIKE '%librarian%'
    	    GROUP BY s.staff_id
    	    ORDER BY RAND()
          LIMIT 0,4";

    $expertArray = $this->db->query($q);

    // additional text - button 
    $button_text = _("Click to see Organisation Chart");  


    $list_guide_experts = "<div class=\"find-expert-area-circ\"><div class=\"expert-btn-area\"><a href=\"" . PATH_TO_SP ."subjects/organisation_chart.php?letter=Subject Librarians A-Z\" class=\"expert-button\">" . $button_text . "</a></div></div>";

  return $list_guide_experts;

  }

  static function getMenuName()
  {
		return _('OrganisationChart');
  }

  static function getMenuIcon()
  {
		$icon="<i class=\"fa fa-users\" title=\"" . _("OrganisationChart") . "\" ></i><span class=\"icon-text\">"  . _("OrganisationChart") . "</span>";
        return $icon;
  }


}