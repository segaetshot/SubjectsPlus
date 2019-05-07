<?php
   namespace SubjectsPlus\Control;
/**
 *   @file
 *   @brief creates the staff listing
 *
 *   @author adarby, d lowder
 *   @date
 *   @todo
 */
    
use PDO;
    
    
class OrganisationChartDisplay {
	
	protected $objDatabase;
	
	function __construct() {
		$this->objDatabase = new Querier;
	}
	
	function getStaffDetails() {
		$strSql = "select staff_id as id, concat(fname,' ',lname) as name,supervisor_id as parent from staff where staff_id!=1 order by supervisor_id asc";
		$arrResult = $this->objDatabase->query($strSql);
		foreach($arrResult as $key=>$value){
			$strSql = "select d.name from department d left join staff_department sd on d.department_id=sd.department_id where sd.staff_id=".$value['id'];
			$arrDepartment = $this->objDatabase->query($strSql);
			if(!isset($value['parent'])) $value['parent'] = 0;
			$arrNew[] = ['memberId'=>(int)$value['id'], 'otherInfo'=>$value['name'], 'parentId'=>(int)$value['parent'], 'department'=>$arrDepartment[0][0]];
		}
		//exit();
		return json_encode($arrNew);
	}
	
  function writeTable($qualifier, $get_assoc_subs = 1, $print_display = 0) {

    global $tel_prefix;
    global $mod_rewrite;

    // sanitize submission
    $selected = scrubData($qualifier);

    
    return $items;
  }

  function searchFor($qualifier) {
    
  }

  function getAssocSubjects($staff_id, $ptags) {
    global $mod_rewrite;
    $assoc_subjects = "";

    // See if they're a librarian, and then check for subjects

    $islib = preg_match('/librarian/', $ptags);

    if ($islib == 1) {
      // UM hack in query
      $q2 = "SELECT subject, shortform 
              FROM subject, staff_subject 
              WHERE subject.subject_id = staff_subject.subject_id
              AND staff_subject.staff_id = $staff_id
              AND subject.active = 1
              AND type = 'Subject'
              ORDER BY subject";
      //print $q2;
        $db = new Querier;
      $r2 = $db->query($q2);

      foreach ($r2 as $myrow2) {

        if ($mod_rewrite == 1) {
          $link_to_guide = $myrow2[1];
        } else {
          $link_to_guide = "guide.php?subject=" . $myrow2[1];
        }

        $assoc_subjects .= "<a href=\"$link_to_guide\">$myrow2[0]</a>, ";
      }
    }

    if ($assoc_subjects != "") {
      $assoc_subjects = rtrim($assoc_subjects, ", ");
      $assoc_subjects = "<br /><span class=\"smaller\">$assoc_subjects</span>";
    } else {
      $assoc_subjects = "";
    }
    return $assoc_subjects;
  }

}

?>