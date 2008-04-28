<?php
/**
* Forums model
* 
* @package forums
* @author The myTravelbook Team <http://www.sourceforge.net/projects/mytravelbook>
* @copyright Copyright (c) 2005-2006, myTravelbook Team
* @license http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL)
* @version $Id: forums.model.php 32 2007-04-03 10:22:22Z marco_p $
*/

class Forums extends PAppModel {
    const THREADS_PER_PAGE = 15;
    const POSTS_PER_PAGE = 15;
    const NUMBER_LAST_POSTS_PREVIEW = 5; // Number of Posts shown as a help on the "reply" page
    


/** ------------------------------------------------------------------------------
* function : MakeRevision
* this is a copy of a function allready running in Function tools
* this is not the best place for it, please contact jeanyves if you feel like to change this
* MakeRevision this function save a copy of current value of record Id in table
* TableName for member IdMember with Done By reason
* @$Id : id of the record
* @$TableName : table where the revision is to be done 
* @$IdMemberParam : the member who cause the revision, the current memebr will be use if this is not set
* @$DoneBy : a text to say why the update was done (this must be one of the value of the enum 'DoneByMember','DoneByOtherMember","DoneByVolunteer','DoneByAdmin','DoneByModerator')
*/
function MakeRevision($Id, $TableName, $IdMemberParam = 0, $DoneBy = "DoneByMember") {
	global $_SYSHCVOL; // this is needed to retrieve the optional mem
	$IdMember = $IdMemberParam;
	if ($IdMember == 0) {
		$IdMember = $_SESSION["IdMember"];
	}
	$qry = mysql_query("select * from " . $TableName . " where id=" . $Id);
	if (!$qry) {
	  throw new PException("forum::MakeRevision fail to select id=#".$Id." from ".$TableName);
	}

	$count = mysql_num_fields($qry);
	$rr = mysql_fetch_object($qry);

	$XMLstr = "";
	for ($ii = 0; $ii < $count; $ii++) {
		$field = mysql_field_name($qry, $ii);
		$XMLstr .= "<field>" . $field . "</field>\n";
		$XMLstr .= "<value>" . $rr-> $field . "</value>\n";
	}
	$str = "INSERT INTO " . $_SYSHCVOL['ARCH_DB'] . ".previousversion(IdMember,TableName,IdInTable,XmlOldVersion,Type) VALUES(" . $IdMember . ",'" . $TableName . "'," . $Id . ",'" . mysql_real_escape_string($XMLstr) . "','" . $DoneBy . "')";
	if (!$qry) {
	  throw new PException("forum::MakeRevision fail to insert id=#".$Id." for ".$TableName." into ".$_SYSHCVOL['ARCH_DB'] . ".previousversion");
	}
	mysql_query($str);
} // end of MakeRevision


/**
* InsertInfTrad function
*
* This InsertInFTrad create a new translatable text in forum_trads
* @$ss is for the content of the text
* @$TableColumn refers to the table and coilumn the trad is associated to
* @$IdRecord is the num of the record in this table
* @$_IdMember ; is the id of the member who own the record
* @$_IdLanguage
* @$IdTrad  is probably useless (I don't remmber why I defined it)
* 
* 
* Warning : as default language this function will use by priority :
* 1) the content of $_IdLanguage if it is set to something else than -1
* 2) the content of an optional $_POST[IdLanguage] if it is set
* 3) the content of the current $_SESSION['IdLanguage'] of the current membr if it set
* 4) The default language (0)
* 
*/ 
function InsertInFTrad($ss,$TableColumn,$IdRecord, $_IdMember = 0, $_IdLanguage = -1, $IdTrad = -1) {
	$DefLanguage=0 ;
   if (isset($_SESSION['IdLanguage'])) {
	   $DefLanguage=$_SESSION['IdLanguage'] ;
	}
	if (isset($_POST['IdLanguage'])) { // This will allow to consider a Language specified in the form
	   $DefLanguage=$_POST['IdLanguage'] ;
	}
	if ($_IdMember == 0) { // by default it is current member
		$IdMember = $_SESSION['IdMember'];
	} else {
		$IdMember = $_IdMember;
	}

	if ($_IdLanguage == -1) {
		$IdLanguage = $DefLanguage;
	}
	else {
		$IdLanguage = $_IdLanguage;
	}

	if ($IdTrad <=0) { // if a new IdTrad is needed
		// Compute a new IdTrad
   	$s = $this->dao->query("select max(IdTrad) as maxi from forum_trads");
   	if (!$s) {
      	   throw new PException('Failed in InsertInFTrad searchin max(IdTrad)');
   	}
		$rr=$s->fetch(PDB::FETCH_OBJ) ;
		if (isset ($rr->maxi)) {
			$IdTrad = $rr->maxi + 1;
		} else {
			$IdTrad = 1;
		}
	}

	$IdOwner = $IdMember;
	$IdTranslator = $_SESSION['IdMember']; // the recorded translator will always be the current logged member
	$Sentence = $ss;
	$str = "insert into forum_trads(TableColumn,IdRecord,IdLanguage,IdOwner,IdTrad,IdTranslator,Sentence,created) ";
	$str .= "Values('".$TableColumn."',".$IdRecord.",". $IdLanguage . "," . $IdOwner . "," . $IdTrad . "," . $IdTranslator . ",\"" . $Sentence . "\",now())";
   $s = $this->dao->query($str);
   if (!$s) {
      throw new PException('Failed in InsertInFTrad for inserting in forum_trads!');
   }
	// Now save the redudant reference
	if (($IdRecord>0) and (!empty($TableColumn))) {
	   $table=explode(".",$TableColumn) ;
	   $str="update ".$table[0]." set ".$TableColumn."=".$IdTrad." where id=".$IdRecord ;
      $s = $this->dao->query($str);
      if (!$s) {
      	  throw new PException("InsertInFTrad Failed in updating ".$TableColumn." for IdRecord=#".$IdRecord." with value=[".$IdTrad."]");
      }
	   
	}
	return ($IdTrad);
} // end of InsertInFTrad

/**
* ReplaceInFTrad function
*
* This ReplaceInFTrad replace or create translatable text in forum_trads
* @$ss is for the content of the text
* @$TableColumn refers to the table and column the trad is associated to
* @$IdRecord is the num of the record in this table
* $IdTrad is the record in forum_trads to replace (unique for each IdLanguage)
* @$Owner ; is the id of the member who own the record
* 
* Warning : as default language this function will use by priority :
* 1) the content of $_IdLanguage if it is set to something else than -1
* 2) the content of an optional $_POST[IdLanguage] if it is set
* 3) the content of the current $_SESSION['IdLanguage'] of the current membr if it set
* 4) The default language (0)
* 
*/ 
function ReplaceInFTrad($ss,$TableColumn,$IdRecord, $IdTrad = 0, $IdOwner = 0) {
	$DefLanguage=0 ;
   if (isset($_SESSION['IdLanguage'])) {
	   $DefLanguage=$_SESSION['IdLanguage'] ;
	}
	if (isset($_POST['IdLanguage'])) { // This will allow to consider a Language specified in the form
	   $DefLanguage=$_POST['IdLanguage'] ;
	}
	if ($IdOwner == 0) {
		$IdMember = $_SESSION['IdMember'];
	} else {
		$IdMember = $IdOwner;
	}
	if ($IdTrad == 0) {
		return ($this->InsertInFTrad($ss,$TableColumn,$IdRecord, $IdMember,$DefLanguage)); // Create a full new translation
	}
	$IdTranslator = $_SESSION['IdMember']; // the recorded translator will always be the current logged member
  	$s = $this->dao->query("select * from forum_trads where IdTrad=" . $IdTrad . " and IdLanguage=" . $DefLanguage." /* in forum->ReplaceInFTrad */");
  	if (!$s) {
  	   throw new PException('Failed in ReplaceInFTrad searching prefious IdTrad=#'.$IdTrad.' for IdLanguage='.$DefLanguage);
  	}
	$rr=$s->fetch(PDB::FETCH_OBJ) ;
	if (!isset ($rr->id)) {
		//	  echo "[$str] not found so inserted <br />";
		return ($this->InsertInFTrad($ss,$TableColumn,$IdRecord, $IdMember, $DefLanguage, $IdTrad)); // just insert a new record in memberstrads in this new language
	} else {
		if ($ss != addslashes($rr->Sentence)) { // Update only if sentence has changed
			$this->MakeRevision($rr->id, "forum_trads"); // create revision
			$str = "UPDATE forum_trads SET TableColumn='".$TableColumn."',IdRecord=".$IdRecord.",IdTranslator=" . $IdTranslator . ",Sentence='" . $ss . "' WHERE id=" . $rr->id;
   		$s = $this->dao->query($str);
   		if (!$s) {
      		   throw new PException('Failed in ReplaceInFTrad for updating in forum_trads!');
   		}
		}
	}
	return ($IdTrad);
} // end of ReplaceInFTrad


/**
* FindAppropriatedLanguage function will retrieve the appropriated default language 
* for a member who want to reply to a thread (started with the#@IdPost post)
*/
function FindAppropriatedLanguage($IdPost=0) {
   $ss="select `IdContent` FROM `forums_posts` WHERE `id`=".$IdPost ;
	$q=mysql_query($ss) ;
	$row=mysql_fetch_object($q) ;
	
//	$q = $this->_dao->query($ss);
//	$row = $q->fetch(PDB::FETCH_OBJ);
	if (!isset($row->IdContent)) {
	   return (0) ;
	}
	else {
	   $IdTrad=$row->IdContent ;
	}

	// Try IdTrad with current language of the member
  	$query ="SELECT IdLanguage FROM `forum_trads` WHERE `IdTrad`=".$IdTrad." and `IdLanguage`=".$_SESSION["IdLanguage"] ;
	$q = mysql_query($query);
	$row = mysql_fetch_object($q) ;
	if (isset ($row->IdLanguage)) {
	   return($row->IdLanguage) ;
	}

	// Try with the original language used for this post	
	$query ="SELECT `IdLanguage` FROM `forum_trads` WHERE `IdTrad`=".$IdTrad."  order by id asc limit 1" ;
	$q = mysql_query($query);
	$row = mysql_fetch_object($q) ;
	if (isset ($row->IdLanguage)) {
	   return($row->IdLanguage) ;
	}
	
	return(0) ; // By default we will return english

} // end of FindAppropriatedLanguage

    public function __construct() {
        parent::__construct();
    }
    
    public static $continents = array(
        'AF' => 'Africa',
        'AN' => 'Antarctica',
        'AS' => 'Asia',
        'EU' => 'Europe',
        'NA' => 'North America',
        'SA' => 'South Amercia',
        'OC' => 'Oceania'
    );
    
    private function boardTopLevel() {
        if ($this->tags) {
            $subboards = array();
            $taginfo = $this->getTagsNamed();
            
            $url = 'forums';
            
            $subboards[$url] = 'Forums';
            
            for ($i = 0; $i < count($this->tags) - 1; $i++) {
                if (isset($taginfo[$this->tags[$i]])) {
                    $url = $url.'/t'.$this->tags[$i].'-'.$taginfo[$this->tags[$i]];
                    $subboards[$url] = $taginfo[$this->tags[$i]];
                }
            }
            
            if (count($this->tags)>0) {
               $title = $taginfo[$this->tags[count($this->tags) -1]];
               $href = $url.'/t'.$this->tags[count($this->tags) -1].'-'.$title;
            }
            else {
               $title = "no tags";
               $href = $url.'/t'.'-'.$title;
            }
            
			 
            $this->board = new Board($this->dao, $title, $href, $subboards, $this->tags, $this->continent);
            $this->board->initThreads($this->getPage());
        } else {
            $this->board = new Board($this->dao, 'Forums', '.');
            foreach (Forums::$continents as $code => $name) {
                $this->board->add(new Board($this->dao, $name, 'k'.$code.'-'.$name));
            }
            $this->board->initThreads($this->getPage());
        }
    } // end of boardTopLevel
    
/**
* Language managment
* this function same the current version the current user is using
* It is used to change in an artificial way, the
*/

    private function boardContinent()
    {
        if (!isset(Forums::$continents[$this->continent]) || !Forums::$continents[$this->continent]) {
            throw new PException('Invalid Continent');
        }
        
        $subboards = array('forums/' => 'Forums');
        
        $url = 'forums/k'.$this->continent.'-'.Forums::$continents[$this->continent];
        $href = $url;
        if ($this->tags) {
            $taginfo = $this->getTagsNamed();
            
            $subboards[$url] = Forums::$continents[$this->continent];
            
            for ($i = 0; $i < count($this->tags) - 1; $i++) {
                if (isset($taginfo[$this->tags[$i]])) {
                    $url = $url.'/t'.$this->tags[$i].'-'.$taginfo[$this->tags[$i]];
                    $subboards[$url] = $taginfo[$this->tags[$i]];
                }
            }
            
            $title = $taginfo[$this->tags[count($this->tags) -1]];
            
        } else {
            $title = Forums::$continents[$this->continent];
        }
        
        $this->board = new Board($this->dao, $title, $href, $subboards, $this->tags, $this->continent);
        
        $countries = $this->getAllCountries($this->continent);
        foreach ($countries as $code => $country) {
            $this->board->add(new Board($this->dao, $country, 'c'.$code.'-'.$country));
        }
        $this->board->initThreads($this->getPage());
    } // end of boardContinent
    
    public function getAllCountries($continent) {
        $query = sprintf(
            "
SELECT `iso_alpha2`, `name` 
FROM `geonames_countries` 
WHERE `continent` = '%s'
ORDER BY `name` ASC
            ",
            $continent
        );
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve countries!');
        }
        $countries = array();
        while ($row = $s->fetch(PDB::FETCH_OBJ)) {
            $countries[$row->iso_alpha2] = $row->name;
        }
        return $countries;    
    }
    
    private function boardAdminCode() {
        $query = sprintf(
            "
SELECT `name`, `continent` 
FROM `geonames_countries` 
WHERE `iso_alpha2` = '%s'
            ",
            $this->countrycode
        );
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('No such Country');
        }
        $countrycode = $s->fetch(PDB::FETCH_OBJ);
        
        $navichain = array('forums/' => 'Forums', 
            'forums/k'.$this->continent.'-'.Forums::$continents[$this->continent].'/' => Forums::$continents[$this->continent],
            'forums/k'.$this->continent.'-'.Forums::$continents[$this->continent].'/c'.$this->countrycode.'-'.$countrycode->name.'/' => $countrycode->name);
    
        $query = sprintf(
            "
SELECT `name`
FROM `geonames_admincodes` 
WHERE `country_code` = '%s' AND `admin_code` = '%s'
            ",
            $this->countrycode,
            $this->admincode
        );
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('No such Admincode');
        }
        $admincode = $s->fetch(PDB::FETCH_OBJ);

        $url = 'forums/k'.$this->continent.'-'.Forums::$continents[$this->continent].'/c'.$this->countrycode.'-'.$countrycode->name.'/a'.$this->admincode.'-'.$admincode->name;
        $href = $url;
        if ($this->tags) {
            $taginfo = $this->getTagsNamed();
            
            
            $navichain[$url] = $admincode->name;
            
            for ($i = 0; $i < count($this->tags) - 1; $i++) {
                if (isset($taginfo[$this->tags[$i]])) {
                    $url = $url.'/t'.$this->tags[$i].'-'.$taginfo[$this->tags[$i]];
                    $navichain[$url] = $taginfo[$this->tags[$i]];
                }
            }
            
            $title = $taginfo[$this->tags[count($this->tags) -1]];
        } else {
            $title = $admincode->name;
        }

        $this->board = new Board($this->dao, $title, $href, $navichain, $this->tags, $this->continent, $this->countrycode, $this->admincode);
        
        $locations = $this->getAllLocations($this->countrycode, $this->admincode);
        foreach ($locations as $geonameid => $name) {
            $this->board->add(new Board($this->dao, $name, 'g'.$geonameid.'-'.$name));
        }
        $this->board->initThreads($this->getPage());
    }
    
    public function getAllLocations($countrycode, $admincode)
    {
        $query = sprintf(
            "
SELECT `geonameid`, `name` 
FROM `geonames_cache` 
WHERE `fk_countrycode` = '%s' AND `fk_admincode` = '%s'
ORDER BY `population` DESC
LIMIT 100
            ",
            $countrycode,
            $admincode
        );
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve Districts!');
        }
        $locations = array();
        while ($row = $s->fetch(PDB::FETCH_OBJ)) {
            $locations[$row->geonameid] = $row->name;
        }
        natcasesort($locations);
        return $locations;        
    }
    
    private function boardCountry()
    {
        $query = sprintf(
            "
SELECT `name`, `continent` 
FROM `geonames_countries` 
WHERE `iso_alpha2` = '%s'
            ",
            $this->countrycode
        );
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('No such Country');
        }
        $countrycode = $s->fetch(PDB::FETCH_OBJ);
        
        $navichain = array('forums/' => 'Forums', 
            'forums/k'.$this->continent.'-'.Forums::$continents[$this->continent].'/' => Forums::$continents[$this->continent]);
        
        $url = 'forums/k'.$this->continent.'-'.Forums::$continents[$this->continent].'/c'.$this->countrycode.'-'.$countrycode->name;
        $href = $url;
        if ($this->tags) {
            $taginfo = $this->getTagsNamed();
            
            
            $navichain[$url] = $countrycode->name;
            
            for ($i = 0; $i < count($this->tags) - 1; $i++) {
                if (isset($taginfo[$this->tags[$i]])) {
                    $url = $url.'/t'.$this->tags[$i].'-'.$taginfo[$this->tags[$i]];
                    $navichain[$url] = $taginfo[$this->tags[$i]];
                }
            }
            
            $title = $taginfo[$this->tags[count($this->tags) -1]];
        } else {
            $title = $countrycode->name;
        }
        
        
        $this->board = new Board($this->dao, $title, $href, $navichain, $this->tags, $this->continent, $this->countrycode);
        
        $admincodes = $this->getAllAdmincodes($this->countrycode);
        foreach ($admincodes as $code => $name) {
            $this->board->add(new Board($this->dao, $name, 'a'.$code.'-'.$name));
        }
        
        $this->board->initThreads($this->getPage());
    }
    
    public function getAllAdmincodes($country_code)
    {
        $query = sprintf(
            "
SELECT `admin_code`, `name` 
FROM `geonames_admincodes` 
WHERE `country_code` = '%s'
ORDER BY `name` ASC
            ",
            $country_code
        );
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve Districts!');
        }
        $admincodes = array();
        while ($row = $s->fetch(PDB::FETCH_OBJ)) {
            $admincodes[$row->admin_code] = $row->name;
        }
        return $admincodes;
    }
    
    private function boardLocation()
    {
        $query = sprintf(
            "
SELECT `name`, `continent` 
FROM `geonames_countries` 
WHERE `iso_alpha2` = '%s'
            ",
            $this->countrycode
        );
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('No such Country');
        }
        $countrycode = $s->fetch(PDB::FETCH_OBJ);

    
        $query = sprintf(
            "
SELECT `name` 
FROM `geonames_admincodes` 
WHERE `country_code` = '%s' AND `admin_code` = '%s'
            ",
            $this->countrycode, $this->admincode
        );
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('No such Admincode');
        }
        $admincode = $s->fetch(PDB::FETCH_OBJ);
        
        $navichain = array(
            'forums/' => 'Forums', 
            'forums/k'.$this->continent.'-'.Forums::$continents[$this->continent].'/' => Forums::$continents[$this->continent],
            'forums/k'.$this->continent.'-'.Forums::$continents[$this->continent].'/c'.$this->countrycode.'-'.$countrycode->name.'/' => $countrycode->name,
            'forums/k'.$this->continent.'-'.Forums::$continents[$this->continent].'/c'.$this->countrycode.'-'.$countrycode->name.'/a'.$this->admincode.'-'.$admincode->name.'/' => $admincode->name
        );
                
        $query = sprintf(
            "
SELECT `name` 
FROM `geonames_cache` 
WHERE `geonameid` = '%d'
            ",
            $this->geonameid
        );
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('No such Country');
        }
        $geonameid = $s->fetch(PDB::FETCH_OBJ);
        
        $url = 'forums/k'.$this->continent.'-'.Forums::$continents[$this->continent].'/c'.$this->countrycode.'-'.$countrycode->name.'/a'.$this->admincode.'-'.$admincode->name.'/g'.$this->geonameid.'-'.$geonameid->name;
        $href = $url;
        if ($this->tags) {
            $taginfo = $this->getTagsNamed();
            
            $navichain[$url] = $geonameid->name;
            for ($i = 0; $i < count($this->tags) - 1; $i++) {
                if (isset($taginfo[$this->tags[$i]])) {
                    $url = $url.'/t'.$this->tags[$i].'-'.$taginfo[$this->tags[$i]];
                    $navichain[$url] = $taginfo[$this->tags[$i]];
                }
            }
            
            $title = $taginfo[$this->tags[count($this->tags) -1]];
        } else {
            $title = $geonameid->name;
        }
        
        $this->board = new Board($this->dao, $title, $href, $navichain, $this->tags, $this->continent, $this->countrycode, $this->admincode, $this->geonameid);
        $this->board->initThreads($this->getPage());
    }
    
    /**
    * Fetch all required data for the view to display a forum
    */
    public function prepareForum() {
        if (!$this->geonameid && !$this->countrycode && !$this->continent) { 
            $this->boardTopLevel();
        } else if ($this->continent && !$this->geonameid && !$this->countrycode) { 
            $this->boardContinent();
        } else if (isset($this->admincode) && $this->admincode && $this->continent && $this->countrycode && !$this->geonameid) { 
            $this->boardadminCode();
        } else if ($this->continent && $this->countrycode && !$this->geonameid) {
            $this->boardCountry();
        } else if ($this->continent && $this->countrycode && $this->geonameid && isset($this->admincode) && $this->admincode) { 
            $this->boardLocation();
        } else {
            if (PVars::get()->debug) {
                throw new PException('Invalid Request');
            } else {
                PRequest::home();
            }
        }
    } // end of prepareForum
    
    private $board;
    private $topboard;
    public function getBoard() {
        return $this->board;
    }
    
    public function createProcess() {
        if (!($User = APP_User::login())) {
            return false;
        }
        
        $vars =& PPostHandler::getVars();

        $vars_ok = $this->checkVarsTopic($vars);
        if ($vars_ok) {
            $topicid = $this->newTopic($vars);
            PPostHandler::clearVars();
            return PVars::getObj('env')->baseuri.'forums/s'.$topicid;
        } else {
            return false;
        }
    
    }
    
    /*
     * Fill the Vars in order to edit a post
     */
    public function getEditData($callbackId) {
        $words = new MOD_words();

        $query =
            "
SELECT
    `postid`,
    `authorid`,
    `forums_posts`.`threadid` as `threadid`,
    `message` AS `topic_text`,
	 `IdContent`,
    `title` AS `topic_title`, `first_postid`, `last_postid`, `IdTitle`,
    `forums_threads`.`continent`,
    `forums_threads`.`geonameid`,
    `forums_threads`.`admincode`,
    `forums_threads`.`countrycode`
FROM `forums_posts`
LEFT JOIN `forums_threads` ON (`forums_posts`.`threadid` = `forums_threads`.`threadid`)
WHERE `postid` = $this->messageId
            "
        ;
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('getEditData :: Could not retrieve Postinfo!');
        }
        $vars =& PPostHandler::getVars($callbackId);
        $vars = $s->fetch(PDB::FETCH_ASSOC);
        $tags = array();
        
        // retrieve tags
        $query =    "
SELECT *
FROM `tags_threads`,`forums_posts`,`forums_threads`,`forums_tags`
WHERE `forums_posts`.`threadid` = `forums_threads`.`id`
AND `tags_threads`.`IdThread` = `forums_threads`.`id` 
AND `forums_posts`.`id` = $this->messageId and `forums_tags`.`id`=`tags_threads`.`IdTag`
            "
        ;
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('getEditData :: Failed to retrieve the tags!');
        }

        $tag=array() ;
        while ($rTag = $s->fetch(PDB::FETCH_OBJ)) {
              if (isset($rTag->IdName)) $tags[]=$words->fTrad($rTag->IdName) ; // Find the name according to current language in associations with this tag
        }
        
        $vars['tags'] = $tags;
        $this->admincode = $vars['admincode'];
        $this->continent = $vars['continent'];
        $this->countrycode = $vars['countrycode'];
        $this->geonameid = $vars['geonameid'];
        $this->threadid = $vars['threadid'];
    } // end of get editedata
    
    public function editProcess() {
        if (!($User = APP_User::login())) {
            return false;
        }
        
        $vars =& PPostHandler::getVars();
        
        $query =
            "
SELECT
    `postid`,
    `authorid`,
    `forums_posts`.`threadid`, 
    `first_postid`,
    `last_postid`
FROM `forums_posts`
LEFT JOIN `forums_threads` ON (`forums_posts`.`threadid` = `forums_threads`.`threadid`)
WHERE `postid` = $this->messageId
            "
        ;
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve Postinfo!');
        }
        $postinfo = $s->fetch(PDB::FETCH_OBJ);
        
        if (HasRight("ForumModerator","Edit") || ($User->hasRight('edit_own@forums') && $postinfo->authorid == $User->getId())) {
            $is_topic = ($postinfo->postid == $postinfo->first_postid);
            
            if ($is_topic) {
                $vars_ok = $this->checkVarsTopic($vars);
            } else {
                $vars_ok = $this->checkVarsReply($vars);
            }
            if ($vars_ok) {
                $this->dao->query("START TRANSACTION");
        
                $this->editPost($vars, $User->getId());
                if ($is_topic) {
                    $this->editTopic($vars, $postinfo->threadid);
                }
        
                $this->dao->query("COMMIT");
                
                PPostHandler::clearVars();
                return PVars::getObj('env')->baseuri.'forums/s'.$postinfo->threadid;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

/**
* the function DofTradUpdate() update a forum translation
* @IdForumTrads is the primary key of the parameter to update
*/	 
	 public function DofTradUpdate($IdForumTrads,$P_Sentence,$P_IdLanguage=0) {
	 	 $id=(int)$IdForumTrads ;
        $s=$this->dao->query("select * from forum_trads where id=".$id);
		 $rBefore=$s->fetch(PDB::FETCH_OBJ) ;
		 
// Save the previous version
		 $this->MakeRevision($id, "forum_trads",$_SESSION["IdMember"], $DoneBy = "DoneByModerator")  ;
		 $IdLanguage=(int)$P_IdLanguage ;
		 $Sentence= mysql_real_escape_string($P_Sentence) ;

        MOD_log::get()->write("Updating data for IdForumTrads=#".$id." Before [".addslashes($rBefore->Sentence)."] IdLanguage=".$rBefore->IdLanguage." <br />\nAfter [".$Sentence."] IdLanguage=".$IdLanguage, "ForumModerator");
		 $sUpdate="update forum_trads set Sentence='".$Sentence."',IdLanguage=".$IdLanguage.",IdTranslator=".$_SESSION["IdMember"]." where id=".$id ;
        $s=$this->dao->query($sUpdate);
        if (!$s) {
            throw new PException('Failed for Update forum_trads.id=#'.$id);
        }
		 
	 	
	 } // end of DofTradUpdate 
    
    private function editPost($vars, $editorid) {
        $query = sprintf("SELECT message,forums_posts.threadid,forums_posts.id,IdWriter,IdContent,forums_threads.IdTitle,forums_threads.first_postid from `forums_posts`,`forums_threads` WHERE forums_posts.threadid=forums_threads.id and forums_posts.id = '%d'",$this->messageId) ;
        $s=$this->dao->query($query);
        $rBefore=$s->fetch(PDB::FETCH_OBJ) ;
        
        $query = sprintf("UPDATE `forums_posts` SET `message` = '%s', `last_edittime` = NOW(), `last_editorid` = '%d', `edit_count` = `edit_count` + 1 WHERE `postid` = '%d'",
        $this->dao->escape($this->cleanupText($vars['topic_text'])), $editorid, $this->messageId);
        $this->dao->query($query);
		 $this->ReplaceInFTrad($this->dao->escape($this->cleanupText($vars['topic_text'])),"forums_posts.IdContent",$rBefore->id, $rBefore->IdContent, $rBefore->IdWriter) ;
		 
		 // If thise is the first post, may be we can update the title
		 if ($rBefore->first_postid==$rBefore->id) {
		 	$this->ReplaceInFTrad($this->dao->escape($this->cleanupText($vars['topic_title'])),"forums_threads.IdTitle",$rBefore->threadid, $rBefore->IdTitle, $rBefore->IdWriter) ;
		 }

        // subscription if any is out of transaction, this is not so important
        if ((isset($vars['NotifyMe'])) and ($vars['NotifyMe']=="on")) {
           if (!$this->IsThreadSubscribed($rBefore->threadid,$_SESSION["IdMember"])) {
                 $this->SubscribeThread($rBefore->threadid,$_SESSION["IdMember"]) ;
           }
        }
        else {
           $vars['NotifyMe']="Not Asked" ;
           if ($this->IsThreadSubscribed($rBefore->threadid,$_SESSION["IdMember"])) {
                 $this->UnsubscribeThreadDirect($rBefore->threadid,$_SESSION["IdMember"]) ;
           }
        }

        $this->prepare_notification($this->messageId,"useredit") ; // Prepare a notification
        MOD_log::get()->write("Editing post #".$this->messageId." Text Before=<i>".addslashes($rBefore->message)."</i> <br /> NotifyMe=[".$vars['NotifyMe']."]", "Forum");
    }

    private function subtractTagCounter($threadid) {
        // in fact now this function does a full update of counters for tags of this thread
    
        $query=" UPDATE `forums_tags` SET `counter` = (select count(*) from `tags_threads` where `forums_tags`.`id`=`tags_threads`.`IdTag`)" ;
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Failed for subtractTagCounter!');
        }
    } // end of subtractTagCounter
    
    private function editTopic($vars, $threadid)     {
        $this->subtractTagCounter($threadid);
        
        $query = sprintf(
            "
UPDATE `forums_threads` 
SET `title` = '%s',`geonameid` = %s, `admincode` = %s, `countrycode` = %s, `continent` = %s
WHERE `threadid` = '%d'
            ", 
            $this->dao->escape(strip_tags($vars['topic_title'])), 
            ($this->geonameid ? "'".(int)$this->geonameid."'" : 'NULL'),
            (isset($this->admincode) && $this->admincode ? "'".$this->dao->escape($this->admincode)."'" : 'NULL'),
            ($this->countrycode ? "'".$this->dao->escape($this->countrycode)."'" : 'NULL'),
            ($this->continent ? "'".$this->dao->escape($this->continent)."'" : 'NULL'),
            $threadid
        );
            
        /*
        $query = sprintf(
            "
UPDATE `forums_threads` 
SET
    `title` = '%s',
    `tag1` = NULL, `tag2` = NULL, `tag3` = NULL, `tag4` = NULL, `tag5` = NULL,
    `geonameid` = %s, `admincode` = %s, `countrycode` = %s, `continent` = %s
WHERE `threadid` = '%d'
            ", 
            $this->dao->escape(strip_tags($vars['topic_title'])), 
            ($this->geonameid ? "'".(int)$this->geonameid."'" : 'NULL'),
            (isset($this->admincode) && $this->admincode ? "'".$this->dao->escape($this->admincode)."'" : 'NULL'),
            ($this->countrycode ? "'".$this->dao->escape($this->countrycode)."'" : 'NULL'),
            ($this->continent ? "'".$this->dao->escape($this->continent)."'" : 'NULL'),
            $threadid
        );
        */
            
        $this->dao->query($query);
		 
        $s=$this->dao->query("select IdWriter,forums_threads.id as IdThread,forums_threads.IdTitle from forums_threads,forums_posts where forums_threads.first_postid=forums_posts.id");
        if (!$s) {
            throw new PException('editTopic:: previous infor for firtst post in the thread!');
        }
        $rBefore = $s->fetch(PDB::FETCH_OBJ);
		 
		 $this->ReplaceInFTrad($this->dao->escape(strip_tags($vars['topic_title'])),"forums_threads.IdTitle",$rBefore->IdThread, $rBefore->IdTitle, $rBefore->IdWriter) ;
        


        $this->updateTags($vars, $threadid);
        MOD_log::get()->write("Editing Topic threadid #".$threadid, "Forum");
    } // end of editTopic
    
    public function replyProcess() {
        if (!($User = APP_User::login())) {
            return false;
        }
        
        $vars =& PPostHandler::getVars();

	     $this->checkVarsReply($vars);
        $this->replyTopic($vars);
    
        PPostHandler::clearVars();
        return PVars::getObj('env')->baseuri.'forums/s'.$this->threadid;
    }
    
    
	 
    public function ModeratorEditPostProcess() {
        if (!($User = APP_User::login())) {
            return false;
        }
       
        $vars =& PPostHandler::getVars();
		 if (isset($vars["submit"]) and ($vars["submit"]=="update thread")) { // if an effective update was chosen for a forum trads
		 	$IdThread=(int)$vars["IdThread"] ;
		 	$expiredate="'".$vars["expiredate"]."'"  ;
		 	$stickyvalue=$vars["stickyvalue"];
			if (empty($expiredate)) {
			   $expiredate="NULL" ;
			}
        	MOD_log::get()->write("Updating thread #".$IdThread." Setting expiredate=[".$expiredate."] stickyvalue=".$stickyvalue,"ForumModerator");
       	$this->dao->query("update forums_threads set stickyvalue=".$stickyvalue.",expiredate=".$expiredate." where id=".$IdThread);
		 }
		 elseif (isset($vars["IdForumTrads"])) { // if an effective update was chosen for a forum trads
		 	$this->DofTradUpdate($vars["IdForumTrads"],$vars["Sentence"],$vars["IdLanguage"]) ; // update the corresponding translations
		 }
			 
	     $IdPost=$vars['IdPost'] ;
        PPostHandler::clearVars();
		 
        return PVars::getObj('env')->baseuri.'forums/modeditpost/'.$IdPost;
    } // end of ModeratorEditPostProcess
    
    public function ModeratorEditTagProcess() {
        if (!($User = APP_User::login())) {
            return false;
        }
       
        $vars =& PPostHandler::getVars();
		 if ($vars["submit"]=="replace tag") { // if an effective update was chosen for a forum trads
		 	$IdTag=$vars["IdTag"] ;
		 	$IdTagToReplace=$vars["IdTagToReplace"] ;
			// first save the list of the thread where the tag is going to be replacec for the logs
        	$s=$this->dao->query("select IdThread from tags_threads where IdTag=".$IdTagToReplace) ;
			$strlogs="" ;
        	while ($row = $s->fetch(PDB::FETCH_OBJ)) {
			  if ($strlogs=="") {
			  	 $strlogs="(".$row->IdThread ;
			  }
			  else {
			  	 $strlogs=$strlogs.",".$row->IdThread ;
			  }
			}
		  	$strlogs.=")" ;
        	MOD_log::get()->write("Replacing tag id #".$IdTagToReplace." with tag id #".$IdTag." for thread ".$strlogs,"ForumModerator");
			$s=$this->dao->query("select * from tags_threads where IdTag=".$IdTagToReplace) ; // replace the tags
			while ($row = $s->fetch(PDB::FETCH_OBJ)) {
				$s2=$this->dao->query("select * from tags_threads where IdTag=".$IdTag." and IdThread=".$row->IdThread) ; // replace the tags
				$row2 = $s2->fetch(PDB::FETCH_OBJ) ;
				if (isset($row2->IdTad)) continue ; // Don't try to recreate an allready associated tag
				$this->dao->query("update tags_threads set IdTag=".$IdTag." where IdTag=".$row->IdTag." and IdThread=".$row->IdThread) ; // replace the tags
				
			}
			$this->dao->query("delete from tags_threads where IdTag=".$IdTagToReplace) ; // delete the one who are still here after replace
			$this->dao->query("delete from forums_tags where id=".$IdTagToReplace) ; // delete the tag
			$this->dao->query("UPDATE `forums_tags` SET `counter` = (select count(*) from `tags_threads` where `forums_tags`.`id`=`tags_threads`.`IdTag`)") ; // update counters			
		 }
		 elseif (isset($vars["IdForumTradsTag"]) and ($vars["submit"]=="update")) { // if an effective update was chosen for a forum trads
		 	$this->DofTradUpdate($vars["IdForumTradsTag"],$vars["SentenceTag"],$vars["IdLanguage"]) ; // update the corresponding translations
		 }
		 elseif (isset($vars["IdForumTradsDescription"]) and ($vars["submit"]=="update")) { // if an effective update was chosen for a forum trads
		 	$this->DofTradUpdate($vars["IdForumTradsDescription"],$vars["SentenceDescription"],$vars["IdLanguage"]) ; // update the corresponding translations
		 }
		 elseif ($vars["submit"]=="delete") { // if an effective update was chosen for a forum trads
		 	if (isset($vars["IdForumTradsTag"])) {
        	   MOD_log::get()->write("Deleting forum_trads #".$vars["IdForumTradsTag"]." for tag #".$vars["IdTag"]." Name=[".$vars["SentenceTag"]."]", "ForumModerator");
        	   $this->dao->query("delete from forum_trads where id=".(int)$vars["IdForumTradsTag"]);
			}
		 	if (isset($vars["IdForumTradsDescription"])) {
        	   MOD_log::get()->write("Deleting forum_trads #".$vars["IdForumTradsDescription"]." for Tag #".$vars["IdTag"]." Description=[".$vars["SentenceDescription"]."]", "ForumModerator");
        	   $this->dao->query("delete from forum_trads where id=".(int)$vars["IdForumTradsDescription"]);
			}
		 }
		 elseif (isset($vars["submit"]) and ($vars["submit"]=="add translation")) {
		 	$SaveIdLanguage=$_SESSION["IdLanguage"] ; // Nasty trick because ReplaceInFTrad will use $_SESSION["IdLanguage"] as a global var
			$_SESSION["IdLanguage"]=$vars["NewIdLanguage"] ;
        	MOD_log::get()->write("Adding a translation for Tag #".$vars["IdTag"]." [".$vars["SentenceTag"]."] <br />Desc [<i>".$vars["SentenceDescription"]."</i>]<br /> in Lang :".$vars["NewIdLanguage"], "ForumModerator");
		 	if (!empty($vars["SentenceTag"])) {
			   $this->ReplaceInFTrad(addslashes($vars["SentenceTag"]),"forums_tags.IdName",$vars["IdTag"],$vars["IdName"])  ;
			} 
		 	if (!empty($vars["SentenceDescription"])) {
			   $this->ReplaceInFTrad(addslashes($vars["SentenceDescription"]),"forums_tags.IdDescription",$vars["IdTag"],$vars["IdDescription"]) ;
			} 
			$_SESSION["IdLanguage"]=$SaveIdLanguage ; // restore the NastyTrick
		 }
	     $IdTag=$vars['IdTag'] ;
        PPostHandler::clearVars();
		 
        return PVars::getObj('env')->baseuri.'forums/modedittag/'.$IdTag;
    } // end of ModeratorEditTagProcess
    
    public function delProcess() {
        if (!($User = APP_User::login())) {
            return false;
        }
        
        if (HasRight("ForumModerator","Delete")) {
            $this->dao->query("START TRANSACTION");
            
            $query = sprintf(
                "
SELECT
    `forums_posts`.`threadid`,
    `forums_threads`.`first_postid`,
    `forums_threads`.`last_postid`,
    `forums_threads`.`expiredate`,
    `forums_threads`.`stickyvalue`
FROM `forums_posts`
LEFT JOIN `forums_threads` ON (`forums_posts`.`threadid` = `forums_threads`.`threadid`)
WHERE `forums_posts`.`postid` = '%d'
                ",
                $this->messageId
            );
            $s = $this->dao->query($query);
            if (!$s) {
                throw new PException('Could not retrieve Threadinfo!');
            }
            $topicinfo = $s->fetch(PDB::FETCH_OBJ);
            
            if ($topicinfo->first_postid == $this->messageId) { // Delete the complete topic
                $this->subtractTagCounter($topicinfo->threadid);
                
                $query =
                    "
UPDATE `forums_threads`
SET `first_postid` = NULL, `last_postid` = NULL
WHERE `threadid` = '$topicinfo->threadid'
                    "    
                ;
                $this->dao->query($query);
                
                $query =
                    "
DELETE FROM `forums_posts`
WHERE `threadid` = '$topicinfo->threadid'
                    "
                ;
                $this->dao->query($query);
                MOD_log::get()->write("deleting posts where threadid #". $topicinfo->threadid, "Forum");
                
                // Prepare a notification (before the delete !)
                $this->prepare_notification($this->messageId,"deletethread") ;

                $query =
                    "
DELETE FROM `forums_threads`
WHERE `threadid` = '$topicinfo->threadid'
                    "
                ;
                $this->dao->query($query);
            
                $redir = 'forums';
            } else { // Delete a single post
                /*
                * Check if we are deleting the very last post of a topic
                * if so, we have to update the `last_postid` field of the `forums_threads` table
                */ 
                if ($topicinfo->last_postid == $this->messageId) {
                    $query =
                        "
UPDATE `forums_threads`
SET `last_postid` = NULL
WHERE `threadid` = '$topicinfo->threadid'
                        "
                    ;
                    $this->dao->query($query);
                }
                MOD_log::get()->write("deleting single post where IdPost #". $this->messageId, "Forum");
                
                $this->prepare_notification($this->messageId,"deletepost") ; // Prepare a notification (before the delete !)

                $query =
                    "
DELETE FROM `forums_posts`
WHERE `postid` = '$this->messageId'
                    "
                ;
                $this->dao->query($query);

                if ($topicinfo->last_postid == $this->messageId) {
                    $query =
                        "
SELECT `postid` 
FROM `forums_posts` 
WHERE `threadid` = '$topicinfo->threadid'
ORDER BY `create_time` DESC LIMIT 1
                        "
                    ;
                    $s = $this->dao->query($query);
                    if (!$s) {
                        throw new PException('Could not retrieve Postinfo!');
                    }
                    $lastpost = $s->fetch(PDB::FETCH_OBJ);
                    
                    $lastpostupdate = sprintf(", `last_postid` = '%d'", $lastpost->postid);
                } else {
                    $lastpostupdate = '';
                }
                
                $query =
                    "
UPDATE `forums_threads`
SET `replies` = (`replies` - 1) $lastpostupdate
WHERE `threadid` = '$topicinfo->threadid'
                    "
                ;
                $this->dao->query($query);
                
                $redir = 'forums/s'.$topicinfo->threadid;
            }
            
            $this->dao->query("COMMIT");
        }
    
        
        header('Location: '.PVars::getObj('env')->baseuri.$redir);
        PPHP::PExit();
    }

    
    private function checkVarsReply(&$vars) {
        $errors = array();
        
        if (!isset($vars['topic_text']) || empty($vars['topic_text'])) {
            $errors[] = 'text';
        }
        
        if ($errors) {
            $vars['errors'] = $errors;
            return false;
        }
        
        return true;
    }
    
    private function checkVarsTopic(&$vars) {
        $errors = array();
        
        if (!isset($vars['topic_title']) || empty($vars['topic_title'])) {
            $errors[] = 'title';
        }
        if (!isset($vars['topic_text']) || empty($vars['topic_text'])) {
            $errors[] = 'text';
        }
        
        if ($errors) {
            $vars['errors'] = $errors;
            return false;
        }
        
        return true;
    }
    
    private function replyTopic(&$vars) {
        if (!($User = APP_User::login())) {
            throw new PException('User gone missing...');
        }
        
        $this->dao->query("START TRANSACTION");
        
        $query = sprintf(
            "
INSERT INTO `forums_posts` (`authorid`, `threadid`, `create_time`, `message`,`IdWriter`)
VALUES ('%d', '%d', NOW(), '%s','%d')
            ",
            $User->getId(),
            $this->threadid,
            $this->dao->escape($this->cleanupText($vars['topic_text'])),
            $_SESSION["IdMember"]
        );
        $result = $this->dao->query($query);
		 
        
        $postid = $result->insertId();
		 
// todo one day, remove this line (aim to manage the redudancy with the new id)
		 $query="update `forums_posts` set `id`=`postid` where id=0" ;		 
        $result = $this->dao->query($query);

		 // Now create the text in forum_trads		 
 		 $this->InsertInFTrad($this->dao->escape($this->cleanupText($vars['topic_text'])),"forums_posts.IdContent",$postid) ;
        
        $query =
            "
UPDATE `forums_threads`
SET `last_postid` = '$postid', `replies` = `replies` + 1
WHERE `threadid` = '$this->threadid'
            "
        ;
        $this->dao->query($query);
        
        $this->dao->query("COMMIT");
        

        // subscription if any is out of transaction, this is not so important
        if ((isset($vars['NotifyMe'])) and ($vars['NotifyMe']=="on")) {
           if (!$this->IsThreadSubscribed($this->threadid,$_SESSION["IdMember"])) {
                 $this->SubscribeThread($this->threadid,$_SESSION["IdMember"]) ;
           }
        }
        else {
           $vars['NotifyMe']="Not Asked" ;
           if ($this->IsThreadSubscribed($this->threadid,$_SESSION["IdMember"])) {
                 $this->UnsubscribeThreadDirect($this->threadid,$_SESSION["IdMember"]) ;
           }
        }
    

        MOD_log::get()->write("Replying new IdPost #". $postid." NotifyMe=[".$vars['NotifyMe']."]", "Forum");
        $this->prepare_notification($postid,"reply") ; // Prepare a notification 
        
        return $postid;
    }
    
    /**
    * Create a new Topic (with initial first post)
    * @return int topicid Id of the newly created topic
    */
    private function newTopic(&$vars) {
        if (!($User = APP_User::login())) {
            throw new PException('User gone missing...');
        }
        
        $this->dao->query("START TRANSACTION");
        
        $query = sprintf(
            "
INSERT INTO `forums_posts` (`authorid`, `create_time`, `message`,`IdWriter`)
VALUES ('%d', NOW(), '%s','%d')
            ",
            $User->getId(),
            $this->dao->escape($this->cleanupText($vars['topic_text'])),
            $_SESSION["IdMember"]
        );
        $result = $this->dao->query($query);
        
        $postid = $result->insertId();

// todo one day, remove this line (aim to manage the redudancy with the new id)
		 $query="update `forums_posts` set `id`=`postid` where id=0" ;		 
        $result = $this->dao->query($query);

 		 $this->InsertInFTrad($this->dao->escape($this->cleanupText($vars['topic_text'])),"forums_posts.IdContent",$postid) ;
        
        $query = sprintf(
            "
INSERT INTO `forums_threads` (`title`, `first_postid`, `last_postid`, `geonameid`, `admincode`, `countrycode`, `continent`)
VALUES ('%s', '%d', '%d', %s, %s, %s, %s)
            ",
            $this->dao->escape(strip_tags($vars['topic_title'])),
            $postid,
            $postid, 
            ($this->geonameid ? "'".(int)$this->geonameid."'" : 'NULL'),
            (isset($this->admincode) && $this->admincode ? "'".$this->dao->escape($this->admincode)."'" : 'NULL'),
            ($this->countrycode ? "'".$this->dao->escape($this->countrycode)."'" : 'NULL'),
            ($this->continent ? "'".$this->dao->escape($this->continent)."'" : 'NULL')
        );
        $result = $this->dao->query($query);
        
        $threadid = $result->insertId();

// todo one day, remove this line (aim to manage the redudancy with the new id)
		 $query="update `forums_threads` set `id`=`threadid` where id=0" ;		 
        $result = $this->dao->query($query);

 		 $this->InsertInFTrad($this->dao->escape($this->dao->escape(strip_tags($vars['topic_title']))),"forums_threads.IdTitle",$threadid) ;
        
        $query = sprintf("UPDATE `forums_posts` SET `threadid` = '%d' WHERE `postid` = '%d'", $threadid, $postid);
        $result = $this->dao->query($query);
        
         // Create the tags
        $this->updateTags($vars, $threadid);
        
        $this->dao->query("COMMIT");


        // subscription if any is out of transaction, this is not so important

        if ((isset($vars['NotifyMe'])) and ($vars['NotifyMe']=="on")) {
                 $this->SubscribeThread($threadid,$_SESSION["IdMember"]) ;
        }
        else {
             $vars['NotifyMe']="Not Asked" ;
        }

        $this->prepare_notification($postid,"newthread") ; // Prepare a notification 
        MOD_log::get()->write("New Thread new IdPost #". $postid." NotifyMe=[".$vars['NotifyMe']."]", "Forum");
        
        return $threadid;
    }
    
    private function updateTags($vars, $threadid) {
		 // Try to find a default language
		 $IdLanguage=0 ;
   	 if (isset($_SESSION['IdLanguage'])) {
	   	 	$IdLanguage=$_SESSION['IdLanguage'] ;
		 }
		 if (isset($_POST['IdLanguage'])) { // This will allow to consider a Language specified in the form
	   	 	$IdLanguage=$_POST['IdLanguage'] ;
	 	 }
        if (isset($vars['tags']) && $vars['tags']) {
            $tags = explode(',', $vars['tags']);
            /** 
            $tags = explode(' ', $vars['tags']);
            separator should better be a blank space, but help text must be changed accordingly
            **/
            $i = 1;
            foreach ($tags as $tag) {
                if ($i > 15) { // 15 is this a reasonable limit ?
                    break;
                }
                
                $tag = trim(strip_tags($tag));
                $tag = $this->dao->escape($tag);

				 
                
                // Check if it already exists in our Database
                $query = "SELECT `tagid` FROM `forums_tags`,`forum_trads` WHERE `forum_trads`.`IdTrad`=`forums_tags`.`IdName` and `forum_trads`.`IdLanguage`=".$IdLanguage." and `forum_trads`.`Sentence` = '$tag' ";
                $s = $this->dao->query($query);
                $taginfo = $s->fetch(PDB::FETCH_OBJ);
                if ($taginfo) {
                    $tagid = $taginfo->tagid;
                } else {
                    // Insert it
                    $query = "INSERT INTO `forums_tags` (`tag`) VALUES ('$tag')  ";
                    $result = $this->dao->query($query);
                    $tagid = $result->insertId();
 		 			 $this->InsertInFTrad($tag,"forums_tags.IdName",$tagid) ;
// todo one day, remove this line (aim to manage the redudancy with the new id)
		 $query="update `forums_tags` set `id`=`tagid` where id=0" ;		 
        $result = $this->dao->query($query);
                }
                if ($tagid) {
                    $query = "UPDATE `forums_tags` SET `counter` = `counter` + 1 WHERE `tagid` = '$tagid' ";
                    $this->dao->query($query);
                    $query = "UPDATE `forums_threads` SET `tag$i` = '$tagid' WHERE `threadid` = '$threadid'"; // todo this tag1, tag2 ... thing is going to become obsolete
                    $this->dao->query($query);
                    $query ="replace INTO `tags_threads` (`IdTag`,`IdThread`) VALUES($tagid, $threadid) ";
                    $this->dao->query($query);
                    
                    $i++;
                }
            }
        }
    } // end of updateTags
     
    private $topic;
/**
* function prepareTopic prepares the detail of a topic for display
* if @$WithDetail is set to true, additional details (available languages and original author are displayed)
 
*/	 
    public function prepareTopic($WithDetail=false) {
        $this->topic = new Topic();
		 
        $this->topic->WithDetail = $WithDetail;
		 
        // Topic Data
        $query = 
            "
SELECT
    `forums_threads`.`title`,
    `forums_threads`.`IdTitle`,
    `forums_threads`.`replies`,
    `forums_threads`.`id` as IdThread,
    `forums_threads`.`views`,
    `forums_threads`.`first_postid`,
    `forums_threads`.`expiredate`,
    `forums_threads`.`stickyvalue`,
    `forums_threads`.`continent`,
    `forums_threads`.`geonameid`, `geonames_cache`.`name` AS `geonames_name`,
    `forums_threads`.`admincode`, `geonames_admincodes`.`name` AS `adminname`,
    `forums_threads`.`countrycode`, `geonames_countries`.`name` AS `countryname`
FROM `forums_threads`
LEFT JOIN `geonames_cache` ON (`forums_threads`.`geonameid` = `geonames_cache`.`geonameid`)
LEFT JOIN `geonames_admincodes` ON (`forums_threads`.`admincode` = `geonames_admincodes`.`admin_code` AND `forums_threads`.`countrycode` = `geonames_admincodes`.`country_code`)
LEFT JOIN `geonames_countries` ON (`forums_threads`.`countrycode` = `geonames_countries`.`iso_alpha2`)
WHERE `threadid` = '$this->threadid'
            "
        ;
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve ThreadId  #".$this->threadid." !');
        }
        $topicinfo = $s->fetch(PDB::FETCH_OBJ);
        
        // Now fetch the tags associated with this thread
        $topicinfo->NbTags=0 ;
        $query2="
SELECT IdTag from tags_threads
WHERE IdThread=$topicinfo->IdThread
        ";
        $s2 = $this->dao->query($query2);
        if (!$s2) {
           throw new PException('Could not retrieve IdTags for Threads!');
        }
        while ($row2 = $s2->fetch(PDB::FETCH_OBJ)) {
            //        echo $row2->IdTag," " ;
            $topicinfo->IdTag[]=$row2->IdTag ;
            $topicinfo->NbTags++ ;
        }
        
        $this->topic->topicinfo = $topicinfo;
        $this->topic->IdThread=$this->threadid ;

        
        $from = Forums::POSTS_PER_PAGE * ($this->getPage() - 1);
        
        $query = sprintf("
SELECT `postid`,UNIX_TIMESTAMP(`create_time`) AS `posttime`,`message`,`IdContent`,`user`.`id` AS `user_id`,`user`.`handle` AS `user_handle`,`geonames_cache`.`fk_countrycode`
FROM `forums_posts`
LEFT JOIN `user` ON (`forums_posts`.`authorid` = `user`.`id`)
LEFT JOIN `geonames_cache` ON (`user`.`location` = `geonames_cache`.`geonameid`)
WHERE `threadid` = '%d'
ORDER BY `posttime` ASC
LIMIT %d, %d",$this->threadid,$from,Forums::POSTS_PER_PAGE);
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve Posts)!');
        }
        while ($row = $s->fetch(PDB::FETCH_OBJ)) {
		   if ($WithDetail) { // if details are required retrieve all thhe Posts of this thread
          	  $sw = $this->dao->query("select forum_trads.Sentence,IdOwner,IdTranslator,languages.ShortCode,languages.EnglishName,members.Username as TranslatorUsername from forum_trads,languages,members 
			                           where languages.id=forum_trads.IdLanguage and forum_trads.IdTrad=".$row->IdContent." and members.id=IdTranslator order by forum_trads.id asc");
        	  while ($roww = $sw->fetch(PDB::FETCH_OBJ)) {
			    $row->Trad[]=$roww ;
			  }
		   }
          $this->topic->posts[] = $row;        
        } // end  // Now retrieve all thhe Posts of this thread
        
        
        // Check if the current user has subscribe to this thread or not (to display the proper option, subscribe or unsubscribe)
        if (isset($_SESSION["IdMember"])) {
            $query = sprintf( "
SELECT
    `members_threads_subscribed`.`id` AS IdSubscribe,
    `members_threads_subscribed`.`UnSubscribeKey` AS IdKey 
FROM members_threads_subscribed
WHERE IdThread=%d
AND IdSubscriber=%d
                ",
                $this->threadid,
                $_SESSION["IdMember"]
            );
            $s = $this->dao->query($query);
            if (!$s) {
                throw new PException('Could if has subscribed to ThreadId  #".$this->threadid." !');
            }
            $row = $s->fetch(PDB::FETCH_OBJ) ;
            if (isset($row->IdSubscribe)) {
                $this->topic->IdSubscribe= $row->IdSubscribe ;
                $this->topic->IdKey= $row->IdKey ;
            }
        }
        
        $query = sprintf(  "
SELECT
    `forums_threads`.`title`,
    `forums_threads`.`IdTitle`,
    `forums_threads`.`replies`,
    `forums_threads`.`views`,
    `forums_threads`.`first_postid`,
    `forums_threads`.`continent`,
    `forums_threads`.`geonameid`, `geonames_cache`.`name` AS `geonames_name`,
    `forums_threads`.`admincode`, `geonames_admincodes`.`name` AS `adminname`,
    `forums_threads`.`countrycode`, `geonames_countries`.`name` AS `countryname`,
    `forums_threads`.`tag1` AS `tag1id`, `tags1`.`tag` AS `tag1`,
    `forums_threads`.`tag2` AS `tag2id`, `tags2`.`tag` AS `tag2`,
    `forums_threads`.`tag3` AS `tag3id`, `tags3`.`tag` AS `tag3`,
    `forums_threads`.`tag4` AS `tag4id`, `tags4`.`tag` AS `tag4`,
    `forums_threads`.`tag5` AS `tag5id`, `tags5`.`tag` AS `tag5`
FROM `forums_threads`
LEFT JOIN `geonames_cache` ON (`forums_threads`.`geonameid` = `geonames_cache`.`geonameid`)
LEFT JOIN `geonames_admincodes` ON (`forums_threads`.`admincode` = `geonames_admincodes`.`admin_code` AND `forums_threads`.`countrycode` = `geonames_admincodes`.`country_code`)
LEFT JOIN `geonames_countries` ON (`forums_threads`.`countrycode` = `geonames_countries`.`iso_alpha2`)
LEFT JOIN `forums_tags` AS `tags1` ON (`forums_threads`.`tag1` = `tags1`.`tagid`)
LEFT JOIN `forums_tags` AS `tags2` ON (`forums_threads`.`tag2` = `tags2`.`tagid`)
LEFT JOIN `forums_tags` AS `tags3` ON (`forums_threads`.`tag3` = `tags3`.`tagid`)
LEFT JOIN `forums_tags` AS `tags4` ON (`forums_threads`.`tag4` = `tags4`.`tagid`)
LEFT JOIN `forums_tags` AS `tags5` ON (`forums_threads`.`tag5` = `tags5`.`tagid`)
WHERE `threadid` = '%d'
            ",
            $this->threadid
        );
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve ThreadId  #".$this->threadid." !');
        }

        // Increase the number of views
        $query = "
UPDATE `forums_threads`
SET `views` = (`views` + 1)
WHERE `threadid` = '$this->threadid' LIMIT 1
            "     ;
        $this->dao->query($query);
        
    } // end of prepareTopic
    
    public function initLastPosts() {
        $query = sprintf(
            "
SELECT
    `postid`,
    UNIX_TIMESTAMP(`create_time`) AS `posttime`,
    `message`,
	 `IdContent`,
    `user`.`id` AS `user_id`,
    `user`.`handle` AS `user_handle`,
    `geonames_cache`.`fk_countrycode`
FROM `forums_posts`
LEFT JOIN `user` ON (`forums_posts`.`authorid` = `user`.`id`)
LEFT JOIN `geonames_cache` ON (`user`.`location` = `geonames_cache`.`geonameid`)
WHERE `threadid` = '%d'
ORDER BY `posttime` DESC
LIMIT %d
            ",
            $this->threadid,
            Forums::NUMBER_LAST_POSTS_PREVIEW
        );
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve Posts!');
        }
        $this->topic->posts = array();
        while ($row = $s->fetch(PDB::FETCH_OBJ)) {
            $this->topic->posts[] = $row;
        }
    }
    
    /**
     * This function retrieve the subscriptions for the member $cid and/or the the thread IdThread and/or theIdTag
     * @$cid : either the IdMember or the username of the member we are searching the subscription
     * this $cid and $IdThread and $IdTag parameters are only used if the current member has moderator rights
     * It returns a $TResults structure
     * Very important  : member who are not moderators cannot see other people subscriptions
     */
    public function searchSubscriptions($cid=0,$IdThread=0,$IdTag=0) {
        $IdMember=0 ;
        
        $TResults->Username="" ;
        $TResults->ThreadTitle="" ;
        $TResults->IdThread=0 ;
        
        if (!empty($_SESSION["IdMember"])) { // By default current members
            $IdMember=$_SESSION["IdMember"];
        }
        if (($cid!=0) and (HasRight("ForumModerator","SeeSubscriptions"))) {
            // Moderators can see the subscriptions of other members
            if (is_numeric($cid)) {
                $IdMember=$cid ;
                $query = sprintf("select id,Username from members where id%d=",$IdMember) ;
                $s = $this->dao->query($query);
                if (!$s) {
                    throw new PException('Could not retrieve members username via id!');
                }
                $row = $s->fetch(PDB::FETCH_OBJ) ;
                if (isset($row->Username)) {
                    $TResults->Username=$row->Username ;
                }
            } else {
                $query = sprintf(
                    "
SELECT id
FROM members
WHERE username='%s'
                    ",
                    $this->dao->escape($cid)
                ); 
                $s = $this->dao->query($query);
                if (!$s) {
                    throw new PException('Could not retrieve members id via username !');
                }
                $row = $s->fetch(PDB::FETCH_OBJ) ;
                if (isset($row->id)) {
                    $IdMember=$row->id ;
                }
            }
        }
      
        if (!empty($IdThread) and (HasRight("ForumModerator","SeeSubscriptions"))) {
            // In this case we will browse all the threads
            $query = sprintf(
                "
SELECT
    `members_threads_subscribed`.`id` as IdSubscribe,
    `members_threads_subscribed`.`created` AS `subscribedtime`, 
    `forums_threads`.`threadid` as IdThread,
    `forums_threads`.`title`,
    `forums_threads`.`IdTitle`,
    `members_threads_subscribed`.`ActionToWatch`,
    `members_threads_subscribed`.`UnSubscribeKey`,
    `members`.`Username` 
FROM `forums_threads`,`members`,`members_threads_subscribed`
WHERE `forums_threads`.`threadid` = `members_threads_subscribed`.`IdThread`
AND `members_threads_subscribed`.`IdThread`=%d
AND `members`.`id`=`members_threads_subscribed`.`IdSubscriber` 
ORDER BY `subscribedtime` DESC
                ",
                $IdThread
            );
        } else {
            $query = sprintf(
                "
SELECT
    `members_threads_subscribed`.`id` as IdSubscribe,
    `members_threads_subscribed`.`created` AS `subscribedtime`, 
    `forums_threads`.`threadid` as IdThread,
    `forums_threads`.`title`,
    `forums_threads`.`IdTitle`,
    `members_threads_subscribed`.`ActionToWatch`,
    `members_threads_subscribed`.`UnSubscribeKey`,
    `members`.`Username` 
FROM `forums_threads`,`members`,`members_threads_subscribed`
WHERE `forums_threads`.`threadid` = `members_threads_subscribed`.`IdThread`
and `members_threads_subscribed`.`IdSubscriber`=%d
and `members`.`id`=`members_threads_subscribed`.`IdSubscriber` 
ORDER BY `subscribedtime` DESC
                ",
                $IdMember
            );
        }
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve members_threads_subscribed sts via searchSubscription !');
        }
        
        if ($IdThread!=0) {
            $TResults->ThreadTitle="Not Yet found Id thread=#".$IdThread ; // Initialize the title in case there is a selected thread
            $TResults->IdThread=$IdThread ;
        }

        $TResults->TData = array();
        while ($row = $s->fetch(PDB::FETCH_OBJ)) {
            if ($IdThread!=0) { // Initialize the title in case there is a selected thread
                $TResults->ThreadTitle=$row->title ;
            }
            $TResults->TData[] = $row;
        }

// now the Tags

        if (!empty($IdTag) and (HasRight("ForumModerator","SeeSubscriptions"))) {
            // In this case we will browse all the tags
            $query = sprintf(
                "
SELECT
    `members_tags_subscribed`.`id` as IdSubscribe,
    `members_tags_subscribed`.`created` AS `subscribedtime`, 
    `forums_tags`.`id` as IdTag,
    `forums_tags`.`IdName`,
    `forums_tags`.`tag` as title,
    `forums_tags`.`IdName`,
    `members_tags_subscribed`.`ActionToWatch`,
    `members_tags_subscribed`.`UnSubscribeKey`,
    `members`.`Username` 
FROM `forums_tags`,`members`,`members_tags_subscribed`
WHERE `forums_tags`.`id` = `members_tags_subscribed`.`IdTag`
AND `members_tags_subscribed`.`IdThread`=%d
AND `members`.`id`=`members_tags_subscribed`.`IdSubscriber` 
ORDER BY `subscribedtime` DESC
                ",
                $IdThread
            );
        } else {
            $query = sprintf(
                "
SELECT
    `members_tags_subscribed`.`id` as IdSubscribe,
    `members_tags_subscribed`.`created` AS `subscribedtime`, 
    `forums_tags`.`id` as IdTag,
    `forums_tags`.`IdName`,
    `forums_tags`.`tag` as title,
    `forums_tags`.`IdName`,
    `members_tags_subscribed`.`ActionToWatch`,
    `members_tags_subscribed`.`UnSubscribeKey`,
    `members`.`Username` 
FROM `forums_tags`,`members`,`members_tags_subscribed`
WHERE `forums_tags`.`id` = `members_tags_subscribed`.`IdTag`
and `members_tags_subscribed`.`IdSubscriber`=%d
and `members`.`id`=`members_tags_subscribed`.`IdSubscriber` 
ORDER BY `subscribedtime` DESC
                ",
                $IdMember
            );
        }
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve members_tags_subscribed sts via searchSubscription !');
        }

        $TResults->TDataTag = array();
        while ($row = $s->fetch(PDB::FETCH_OBJ)) {
            if ($IdTag!=0) { // Initialize the title in case there is a selected thread
                $TResults->TagTitle=$row->title ;
            }
            $TResults->TDataTag[] = $row;
        }

        return $TResults;
    } // end of searchSubscriptions
    

    /**
     * This function remove the subscription marked by IdSubscribe
     * @IdSubscribe is the primary key of the members_threads_subscribed area to remove
     * @Key is  the key to check to be sure it is not an abuse of url
     * It returns a $res=1 if ok
     */
    public function UnsubscribeThread($IdSubscribe=0,$Key="") {
        $query = sprintf(
            "
SELECT
    members_threads_subscribed.id AS IdSubscribe,
    IdThread,
    IdSubscriber,
    Username from members,
    members_threads_subscribed
WHERE members.id=members_threads_subscribed.IdSubscriber
AND members_threads_subscribed.id=%d
AND UnSubscribeKey='%s'
            ",
            $IdSubscribe,$this->dao->escape($Key)
        ); 
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Forum->UnsubscribeThread Could not retrieve the subscription !');
        }
        $row = $s->fetch(PDB::FETCH_OBJ) ;
        if (!isset($row->IdSubscribe)) {
            MOD_log::get()->write("No entry found while Trying to unsubscribe thread  IdSubscribe=#".$IdSubscribe." IdKey=".$Key, "Forum");
            return(false) ;
        }
        $query = sprintf(
            "
DELETE
FROM members_threads_subscribed
WHERE id=%d
AND UnSubscribeKey='%s'
            ",
            $IdSubscribe,
            $this->dao->escape($Key)
        ); 
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Forum->UnsubscribeThread delete failed !');
        }
        if (isset($_SESSION["IdMember"])) {
            MOD_log::get()->write("Unsubscribing member <b>".$row->Username."</b> from thread #".$row->IdThread, "Forum");
            if ($_SESSION["IdMember"]!=$row->IdSubscriber) { // If it is not the member himself, log a forum action in addition
                MOD_log::get()->write("Unsubscribing member <b>".$row->Username."</b> from thread #".$row->IdThread, "ForumModerator");
            }
        }
        else {
            MOD_log::get()->write("Unsubscribing member <b>".$row->Username."</b> from thread #".$row->IdThread." without beeing logged", "Forum");
        }
        return(true) ;
    } // end of UnsubscribeThread

    /**
     * This function remove the subscription without checking the key
     *
     * @param unknown_type $IdThread the id of the thread to unsubscribe to
     * @param unknown_type $ParamIdMember the member to unsubscribe, if 0, the current member will eb used
     * @return unknown
     */
    public function UnsubscribeThreadDirect($IdThread=0,$ParamIdMember=0) {
        $IdMember=$ParamIdMember ;
        if (isset($_SESSION["IdMember"]) and $IdMember==0) {
            $IdMember=$_SESSION["IdMember"] ;
        }
        
        $query = sprintf(
            "
DELETE
FROM members_threads_subscribed
WHERE IdSubscriber=%d
AND IdThread=%d
            ",
            $IdMember,
            $IdThread
        ); 
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Forum->UnsubscribeThreadDirect failed to delete !');
        }
            MOD_log::get()->write("Unsubscribing direct (By NotifyMe) member #".$IdMember." from thread #".$IdThread, "Forum");
        return(true) ;
    } // end of UnsubscribeThreadDirect
    
    
    /**
     * This function allow to subscribe to a thread
     * 
     * @$IdThread : The thread we want the user to subscribe to
     * @$ParamIdMember optional IdMember, by default set to 0 in this case current logged member will be used
     * It also check that member is not yet subscribing to thread
     */
    public function SubscribeThread($IdThread,$ParamIdMember=0) {
       $IdMember=$ParamIdMember ;
       if (isset($_SESSION["IdMember"]) and $IdMember==0) {
                 $IdMember=$_SESSION["IdMember"] ;
       }
       
       // Check if there is a previous Subscription
       if ($this->IsThreadSubscribed($IdThread,$_SESSION["IdMember"])) {
             MOD_log::get()->write("Allready subscribed to thread #".$IdThread, "Forum");
          return(false) ;
       }
       $key=MD5(rand(100000,900000)) ;
       $query = "insert into members_threads_subscribed(IdThread,IdSubscriber,UnSubscribeKey)  values(".$IdThread.",".$_SESSION["IdMember"].",'".$this->dao->escape($key)."')" ; 
       $s = $this->dao->query($query);
       if (!$s) {
              throw new PException('Forum->SubscribeThread failed !');
       }
       $IdSubscribe=mysql_insert_id() ;
         MOD_log::get()->write("Subscribing to thread #".$IdThread." IdSubscribe=#".$IdSubscribe, "Forum");
    } // end of UnsubscribeThread



	 
	 
	 
	 
    /**
     * This function remove the subscription marked by IdSubscribe
     * @IdSubscribe is the primary key of the members_tags_subscribed area to remove
     * @Key is  the key to check to be sure it is not an abuse of url
     * It returns a $res=1 if ok
     */
    public function UnsubscribeTag($IdSubscribe=0,$Key="") {
        $query = sprintf(
            "
SELECT
    members_tags_subscribed.id AS IdSubscribe,
    IdTag,
    IdSubscriber,
    Username from members,
    members_tags_subscribed
WHERE members.id=members_tags_subscribed.IdSubscriber
AND members_tags_subscribed.id=%d
AND UnSubscribeKey='%s'
            ",
            $IdSubscribe,$this->dao->escape($Key)
        ); 
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Forum->UnsubscribeTag Could not retrieve the subscription !');
        }
        $row = $s->fetch(PDB::FETCH_OBJ) ;
        if (!isset($row->IdSubscribe)) {
            MOD_log::get()->write("No entry found while Trying to unsubscribe Tag  IdSubscribe=#".$IdSubscribe." IdKey=".$Key, "Forum");
            return(false) ;
        }
        $query = sprintf(
            "
DELETE
FROM members_tags_subscribed
WHERE id=%d
AND UnSubscribeKey='%s'
            ",
            $IdSubscribe,
            $this->dao->escape($Key)
        ); 
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Forum->UnsubscribeTag delete failed !');
        }
        if (isset($_SESSION["IdMember"])) {
            MOD_log::get()->write("Unsubscribing member <b>".$row->Username."</b> from Tag #".$row->IdTag, "Forum");
            if ($_SESSION["IdMember"]!=$row->IdSubscriber) { // If it is not the member himself, log a forum action in addition
                MOD_log::get()->write("Unsubscribing member <b>".$row->Username."</b> from Tag #".$row->IdTag, "ForumModerator");
            }
        }
        else {
            MOD_log::get()->write("Unsubscribing member <b>".$row->Username."</b> from Tag #".$row->IdTag." without beeing logged", "Forum");
        }
        return(true) ;
    } // end of UnsubscribeTag

    /**
     * This function remove the subscription without checking the key
     *
     * @param unknown_type $IdTag the id of the Tag to unsubscribe to
     * @param unknown_type $ParamIdMember the member to unsubscribe, if 0, the current member will eb used
     * @return unknown
     */
    public function UnsubscribeTagDirect($IdTag=0,$ParamIdMember=0) {
        $IdMember=$ParamIdMember ;
        if (isset($_SESSION["IdMember"]) and $IdMember==0) {
            $IdMember=$_SESSION["IdMember"] ;
        }
        
        $query = sprintf(
            "
DELETE
FROM members_tags_subscribed
WHERE IdSubscriber=%d
AND IdTag=%d
            ",
            $IdMember,
            $IdTag
        ); 
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Forum->UnsubscribeTagDirect failed to delete !');
        }
            MOD_log::get()->write("Unsubscribing direct (By NotifyMe) member #".$IdMember." from Tag #".$IdTag, "Forum");
        return(true) ;
    } // end of UnsubscribeTagDirect
    
    
    /**
     * This function allow to subscribe to a Tag
     * 
     * @$IdTag : The Tag we want the user to subscribe to
     * @$ParamIdMember optional IdMember, by default set to 0 in this case current logged member will be used
     * It also check that member is not yet subscribing to Tag
     */
    public function SubscribeTag($IdTag,$ParamIdMember=0) {
       $IdMember=$ParamIdMember ;
       if (isset($_SESSION["IdMember"]) and $IdMember==0) {
                 $IdMember=$_SESSION["IdMember"] ;
       }
       
       // Check if there is a previous Subscription
       if ($this->IsTagSubscribed($IdTag,$_SESSION["IdMember"])) {
             MOD_log::get()->write("Allready subscribed to Tag #".$IdTag, "Forum");
          return(false) ;
       }
       $key=MD5(rand(100000,900000)) ;
       $query = "insert into members_tags_subscribed(IdTag,IdSubscriber,UnSubscribeKey)  values(".$IdTag.",".$_SESSION["IdMember"].",'".$this->dao->escape($key)."')" ; 
       $s = $this->dao->query($query);
       if (!$s) {
              throw new PException('Forum->SubscribeTag failed !');
       }
       $IdSubscribe=mysql_insert_id() ;
         MOD_log::get()->write("Subscribing to Tag #".$IdTag." IdSubscribe=#".$IdSubscribe, "Forum");
    } // end of UnsubscribeTag

	 

	 
	 
	 
    // This function retrieve search post of the member $cid
    //@$cid : either the IdMember or the username of the member we are searching the post
    public function searchUserposts($cid=0) {
        $IdMember=0 ;
        if (is_numeric($cid)) {
           $IdMember=$cid ;
        }
        else {
           $query = "select id from members where username='".$this->dao->escape($cid)."'" ; 
           $s = $this->dao->query($query);
           if (!$s) {
              throw new PException('Could not retrieve members id via username !');
           }
           $row = $s->fetch(PDB::FETCH_OBJ) ;
           if (isset($row->id)) {
                 $IdMember=$row->id ;
           }
        }

        $query = sprintf(
            "
SELECT
    `postid`,
    UNIX_TIMESTAMP(`create_time`) AS `posttime`,
    `message`,
	 `IdContent`,
    `forums_threads`.`threadid`,
    `forums_threads`.`title`,
    `forums_threads`.`IdTitle`,
    `user`.`id` AS `user_id`,
    `members`.`Username` AS `user_handle`,
    `geonames_cache`.`fk_countrycode`
FROM `forums_posts`,`members`,`forums_threads`,`user`
LEFT JOIN `geonames_cache` ON (`user`.`location` = `geonames_cache`.`geonameid`)
WHERE `forums_posts`.`IdWriter` = %d 
AND `forums_posts`.`IdWriter` = `members`.`id` 
AND `user`.`handle` = `members`.`Username` 
AND `forums_posts`.`threadid` = `forums_threads`.`threadid` 
AND `forums_posts`.`authorid` = `user`.`id` 
ORDER BY `posttime` DESC
            ",
            $IdMember
        );
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve Posts via searchUserposts !');
        }
        $posts = array();
        while ($row = $s->fetch(PDB::FETCH_OBJ)) {
            $posts[] = $row;
        }
        return $posts;
    } // end of searchUserposts
    
    public function getTopic() {
        return $this->topic;
    }
    
    /**
    * Check if it's a topic or a forum
    * @return bool true on topic
    * @return bool false on forum
    */
    public function isTopic() {
        return (bool) $this->threadid;
    }
    
    private $geonameid = 0;
    private $countrycode = 0;
    private $admincode;
    private $threadid = 0;
    private $tags = array();
    private $continent = false;
    private $page = 1;
    private $messageId = 0;
    public function setGeonameid($geonameid) {
        $this->geonameid = (int) $geonameid;
    }
    public function getGeonameid() {
        return $this->geonameid;
    }
    public function setCountryCode($countrycode) {
        $this->countrycode = $countrycode;
    }
    public function getCountryCode() {
        return $this->countrycode;
    }
    public function setAdminCode($admincode) {
        $this->admincode = $admincode;
    }
    public function getAdminCode() {
        return $this->admincode;
    }
    public function addTag($tagid) {
        $this->tags[] = (int) $tagid;
    }
    public function getTags() {
        return $this->tags;
    }
    public function setThreadId($threadid) {
        $this->threadid = (int) $threadid;
    }
    public function getThreadId() {
        return $this->threadid;
    }
    public function setContinent($continent) {
        $this->continent = $continent;
    }
    public function getContinent() {
        return $this->continent;
    }
    public function getPage() {
        return $this->page;
    }
    public function setPage($page) {
        $this->page = (int) $page;
    }
    public function setMessageId($messageid) {
        $this->messageId = (int) $messageid;
    }
    public function getMessageId() {
        return $this->messageId;
    }
    
    public function getTagsNamed() {
        $tags = array();
        if ($this->tags) {
            $query = sprintf("SELECT `tagid`, `tag`,`IdName` FROM `forums_tags` WHERE `tagid` IN (%s) ", implode(',', $this->tags)  );
            $s = $this->dao->query($query);
            if (!$s) {
                throw new PException('Could not retrieve countries!');
            }
            while ($row = $s->fetch(PDB::FETCH_OBJ)) {
                $tags[$row->tagid] = $row->tag;
            }
            
        }
        return $tags;
    }
    
    public function getAllTags() {
        $words = new MOD_words();
        $tags = array();
        
        $query = "SELECT `tag`, `tagid`, `counter`,`IdName` FROM `forums_tags` ORDER BY `counter` DESC LIMIT 50 ";
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve countries!');
        }
        while ($row = $s->fetch(PDB::FETCH_OBJ)) {
		 	 $row->tag=$words->fTrad($row->IdName) ; // Retrieve the real tags content
            $tags[$row->tagid] = $row;
        }
        shuffle($tags);
        return $tags;
    } // end of getAllTags
    
    public function getTagsMaximum() {
        $tagscloud = array();

        $query = "SELECT `tag`, `counter`,`IdName` FROM `forums_tags` ORDER BY `counter` DESC LIMIT 1";
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve countries!');
        }
        while ($row = $s->fetch(PDB::FETCH_OBJ)) {
            $tag = $row->tag;
            $counter = $row->counter;
            $tagscloud[] = array($tag => $counter);
        }
        // Then we want to determine the maximum counter and shuffle the array (unless you want to retain the order from most searched to least searched).

        // extract maximum counter

        $maximum = max($tagscloud);
        $maximum = max($maximum);

        return $maximum;
    }


    public function getTopLevelTags() {
        $tags = array();
        
        $query = "SELECT `tagid`, `tag`, `tag_description`,`IdName`,`IdDescription` FROM `forums_tags` WHERE `Type` ='Category'  ORDER BY `tag_position` ASC, `tag` ASC";
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve TopLevelTags!');
        }
        while ($row = $s->fetch(PDB::FETCH_OBJ)) {
            $tags[$row->tagid] = $row;
        }
        return $tags;    
    }
    
    private function cleanupText($txt) {
        $str = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>'.$txt.'</body></html>'; 
        $doc = DOMDocument::loadHTML($str);
        if ($doc) {
            $sanitize = new PSafeHTML($doc);
            $sanitize->allow('html');
            $sanitize->allow('body');
            $sanitize->allow('p');
            $sanitize->allow('div');
            $sanitize->allow('b');
            $sanitize->allow('i');
            $sanitize->allow('u');
            $sanitize->allow('a');
            $sanitize->allow('em');
            $sanitize->allow('strong');
            $sanitize->allow('hr');
            $sanitize->allow('span');
            $sanitize->allow('ul');
            $sanitize->allow('li');
            $sanitize->allow('font');
            $sanitize->allow('strike');
            $sanitize->allow('br');
            $sanitize->allow('blockquote');
        
            $sanitize->allowAttribute('color');    
            $sanitize->allowAttribute('bgcolor');            
            $sanitize->allowAttribute('href');
            $sanitize->allowAttribute('style');
            $sanitize->allowAttribute('class');
            $sanitize->allowAttribute('width');
            $sanitize->allowAttribute('height');
            $sanitize->allowAttribute('src');
            $sanitize->allowAttribute('alt');
            $sanitize->allowAttribute('title');
            $sanitize->clean();
            $doc = $sanitize->getDoc();
            $nodes = $doc->x->query('/html/body/node()');
            $ret = '';
            foreach ($nodes as $node) {
                $ret .= $doc->saveXML($node);
            }
            return $ret;
        } else {
            // invalid HTML
            return '';
        }
    }
    
    public function suggestTags($search) {
        // Split words
        $words = explode(',', $search);
        $cleaned = array();
        // Clean up
        foreach ($words as $word) {
            $word = trim($word);
            if ($word) {
                $cleaned[] = $word;
            }
        }
        $words = $cleaned;

        // Which word is the person changing?
        $number_words = count($words);
        if ($number_words && isset($_SESSION['prev_tag_content']) && $_SESSION['prev_tag_content']) {
            $search_for = false;
            $pos = false;
            for ($i = 0; $i < $number_words; $i++) {
                if (isset($words[$i]) && (!isset($_SESSION['prev_tag_content'][$i]) || $words[$i] != $_SESSION['prev_tag_content'][$i])) {
                    $search_for = $words[$i];
                    $pos = $i;
                }
            }
            if (!$search_for) {
                return array();
            }
        } else if ($number_words) {
            $search_for = $words[count($words) - 1]; // last word
            $pos = false;
        } else {
            return array();
        }

        if ($search_for) {
    
            $_SESSION['prev_tag_content'] = $words;
        
            $tags = array();
            // look for possible matches (from ALL tags) in current user language
            $query = "SELECT `Sentence` FROM `forums_tags`,`forum_trads` 
			 		   WHERE forum_trads.IdTrad=forums_tags.IdName and `forum_trads`.`Sentence` LIKE '".$this->dao->escape($search_for)."%' and forum_trads.IdLanguage=".$_SESSION["IdLanguage"]." ORDER BY `counter` DESC";
            $s = $this->dao->query($query);
            if (!$s) {
                throw new PException('Could not retrieve tag entries for user language='.$_SESSION["IdLanguage"]);
            }
            while ($row = $s->fetch(PDB::FETCH_OBJ)) {
                $tags[] = $row->Sentence;
            }
            
			 if ($_SESSION["IdLanguage"]!=0) {
            	// look for possible matches (from ALL tags) english
            	$query = "SELECT `Sentence` FROM `forums_tags`,`forum_trads` 
			 		   WHERE forum_trads.IdTrad=forums_tags.IdName and `forum_trads`.`Sentence` LIKE '".$this->dao->escape($search_for)."%' and forum_trads.IdLanguage=0 ORDER BY `counter` DESC";
               $s = $this->dao->query($query);
            	if (!$s) {
                 throw new PException('Could not retrieve tag entries in english');
            	}
            	while ($row = $s->fetch(PDB::FETCH_OBJ)) {
                $tags[] = $row->Sentence;
            	}
			}
            
            if ($tags) {
                $out = array();
                $suggestion_number = 0;
                foreach ($tags as $w) {
                    $out[$suggestion_number] = array();
                    for ($i = 0; $i < count($words); $i++) {
                        if ($i == $pos) {
                            $out[$suggestion_number][] = $w;
                        } else {
                            $out[$suggestion_number][] .= $words[$i];
                        }
                    }
                    $suggestion_number++;
                }
                return $out;
            }
        }
        return array();
    } // end of suggestTags
	 

	 		function GetLanguageName($IdLanguage) {
				$query="select id as IdLanguage,Name,EnglishName,ShortCode,WordCode from languages where id=".$IdLanguage ;
            	$s = $this->dao->query($query);
            	if (!$s) {
                  throw new PException('Could not retrieve IdLanguage in GetLanguageName entries');
            	}
				else {
					 $row = $s->fetch(PDB::FETCH_OBJ) ;
				 	return($row) ;
				}
				return("not Found") ;
				
			} // end of GetLanguageName


    // This finction will prepare a lois pf language to choose
    // @DefIdLanguage : an optional language to use
	 // return an array of object with LanguageName and IdLanguage
	 public function LanguageChoices($DefIdLanguage=-1) {
	 
			
	 		$tt=array() ;
			$allreadyin=array() ;
			$ii=0 ;

// First proposed will deflanguage
			if ($DefIdLanguage>=0) {
			   $row=$this->GetLanguageName($DefIdLanguage) ;
		   	   array_push($allreadyin,$row->IdLanguage) ;
			   array_push($tt,$row) ;
			}
			// Then next will be english (if not allready in the list)
			if (!in_array(0,$allreadyin)) {
			   $row=$this->GetLanguageName(0) ;
		   	   array_push($allreadyin,$row->IdLanguage) ;
			   array_push($tt,$row) ;
			}
			// Then next will the current user language
			if ((isset($_SESSION["IdLanguage"]) and (!in_array($_SESSION["IdLanguage"],$allreadyin)))) {
			   $row=$this->GetLanguageName($_SESSION["IdLanguage"]) ;
		   	   array_push($allreadyin,$row->IdLanguage) ;
			   array_push($tt,$row) ;
			}
			
			// then now all available languages
			$query="select id as IdLanguage,Name,EnglishName,ShortCode,WordCode from languages where id>0 order by FlagSortCriteria asc";
          	$s = $this->dao->query($query);
        	while ($row = $s->fetch(PDB::FETCH_OBJ)) {
			   if (!in_array($row->IdLanguage,$allreadyin)) {
			   	  array_push($allreadyin,$row->IdLanguage) ;
			  	  array_push($tt,$row) ;
			   }
			}
			return($tt) ; // returs the array of structures
			
	 
	 } // end of LanguageChoices 

	 /**	 
    * This will prepare a post for a moderator action
    * @IdPost : Id of the post to process
	 */
    public function prepareModeratorEditPost($IdPost) {
	 	 $DataPost->IdPost=$IdPost ;
		 $DataPost->Error="" ; // This will receive the error sentence if any
        $query = "select forums_posts.*,members.Status as memberstatus,members.UserName as UserNamePoster from forums_posts,members where forums_posts.id=".$IdPost." and IdWriter=members.id" ;
        $s = $this->dao->query($query);
		 $DataPost->Post = $s->fetch(PDB::FETCH_OBJ) ;

		 if (!isset($DataPost->Post)) {
		 	$DataPost->Error="No Post for IdPost=#".$IdPost ;
			return($DataPost) ;
		 }
		 
// retrieve all trads for content
        $query = "select forum_trads.*,EnglishName,ShortCode,forum_trads.id as IdForumTrads from forum_trads,languages where IdLanguage=languages.id and IdTrad=".$DataPost->Post->IdContent." order by forum_trads.created asc" ;
        $s = $this->dao->query($query);
		 $DataPost->Post->Content=array() ;
		 while ($row=$s->fetch(PDB::FETCH_OBJ)) {
		 	   $DataPost->Post->Content[]=$row ;
		 }

		 

        $query = "select * from forums_threads where id=".$DataPost->Post->threadid ;
        $s = $this->dao->query($query);
		 if (!isset($DataPost->Post)) {
		 	$DataPost->Error="No Post for IdThread=#".$DataPost->Post->threadid ;
			return($DataPost) ;
		 }
		 $DataPost->Thread = $s->fetch(PDB::FETCH_OBJ) ;
		 
// retrieve all trads for Title
        $query = "select forum_trads.*,EnglishName,ShortCode,forum_trads.id as IdForumTrads from forum_trads,languages where IdLanguage=languages.id and IdTrad=".$DataPost->Thread->IdTitle." order by forum_trads.created asc" ;
        $s = $this->dao->query($query);
		 $DataPost->Thread->Title=array() ;
		 while ($row=$s->fetch(PDB::FETCH_OBJ)) {
		 	   array_push($DataPost->Thread->Title,$row) ;
		 }
		
// retrieve all tags
        $query = "select forums_tags.*  from forums_tags,tags_threads where tags_threads.IdTag=forums_tags.id and IdThread=".$DataPost->Thread->id ;
        $s = $this->dao->query($query);
		 $DataPost->Tags=array() ;
		 while ($row=$s->fetch(PDB::FETCH_OBJ)) {
		 	   $DataPost->Tags[]=$row ;
		 }
		
		return ($DataPost) ;
	 } // end of prepareModeratorEditPost

	 /**	 
    * This will prepare a post for a moderator action
    * @IdTag : Id of the post to process
	 */
    public function prepareModeratorEditTag($IdTag) {
	 	 $DataTag->IdTag=$IdTag ;
		 $DataTag->Error="" ; // This will receive the error sentence if any
		 
		
// retrieve The tag
//        $query = "select forums_tags.*,count(*) as cnt  from forums_tags,tags_threads where tags_threads.IdTag=forums_tags.id and forums_tags.id=".$DataTag->IdTag." group by  tags_threads.IdThread" ;;
        $query = "select * from forums_tags where forums_tags.id=".$DataTag->IdTag;
        $s = $this->dao->query($query);
		 while ($row=$s->fetch(PDB::FETCH_OBJ)) {
		 	   $DataTag->Tag=$row ;
		 }
		
// Retrieve the count of thread which are using this tag
        $query = "select count(*) as NbThread from tags_threads where IdTag=".$DataTag->IdTag;
        $s = $this->dao->query($query);
		 $row=$s->fetch(PDB::FETCH_OBJ) ;
		 $DataTag->NbThread=$row->NbThread ;

// Retrieve the tags name
        $query = "select forum_trads.*,EnglishName,ShortCode,forum_trads.id as IdForumTrads from forum_trads,languages where IdLanguage=languages.id and IdTrad=".$DataTag->Tag->IdName." order by forum_trads.created asc" ;
		 $DataTag->Names=array() ;
        $s = $this->dao->query($query);
		 while ($row=$s->fetch(PDB::FETCH_OBJ)) {
		 	   array_push($DataTag->Names,$row) ;
		 }

// Retrieve the tags description
        $query = "select forum_trads.*,EnglishName,ShortCode,forum_trads.id as IdForumTrads from forum_trads,languages where IdLanguage=languages.id and IdTrad=".$DataTag->Tag->IdDescription." order by forum_trads.created asc" ;
		 $DataTag->Descriptions=array() ;
        $s = $this->dao->query($query);
		 while ($row=$s->fetch(PDB::FETCH_OBJ)) {
		 	   array_push($DataTag->Descriptions,$row) ;
		 }

		return ($DataTag) ;
	 } // end of prepareModeratorEditTag

    public function getAllContinents() {
        return self::$continents;
    }
    // This will compute the needed notification and will prepare enqueing
    // @IdPost : Id of the post to notify about
    // @Type : Type of notification "newthread", "reply","moderatoraction","deletepost","deletethread","useredit","translation"
    // Nota this private function must not make any transaction since it can be called from within a transaction
    // it is not a very big deal if a notification is lost so no need to worry about transations here
    private function prepare_notification($IdPost,$Type) {
        $alwaynotified = array() ;// This will be the list of people who will be notified about every forum activity

        // retrieve the post data
        $query = sprintf("select forums_posts.threadid as IdThread from forums_posts where  forums_posts.postid=%d",$IdPost) ;
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('prepare_notification Could not retrieve the post data!');
        }
        $rPost = $s->fetch(PDB::FETCH_OBJ) ;



        // retrieve the forummoderator with Scope ALL
        $query = sprintf("
SELECT `rightsvolunteers`.`IdMember` 
FROM `rightsvolunteers` 
WHERE `rightsvolunteers`.`IdRight`=24
AND `rightsvolunteers`.`Scope` = '\"All\"'" 
        );
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve forum moderators!');
        }
        while ($row = $s->fetch(PDB::FETCH_OBJ)) {
            array_push($alwaynotified,$row->IdMember) ;
        }

        for ($ii=0;$ii<count($alwaynotified);$ii++) {
            $query = "INSERT INTO `posts_notificationqueue` (`IdMember`, `IdPost`, `created`, `Type`)
                   VALUES (".$alwaynotified[$ii].",".$IdPost.",now(),'".$Type."')" ;
                   $result = $this->dao->query($query);
                   
            if (!$result) {
               throw new PException('prepare_notification failed : for Type='.$Type);
            }
        } // end of for $ii
        
        
		 // Check the user who have subscribed to one tag of this thread 
        $query = sprintf("select IdSubscriber,id as IdSubscription from members_tags_subscribed,tags_threads where tags_threads.IdTag=members_tags_subscribed.IdTag and tags_threads.IdThread=%d ",$rPost->IdThread) ;
        $s1 = $this->dao->query($query);
        if (!$s1) {
            throw new PException('prepare_notification Could not retrieve the members_tags_subscribed !');
        }
        while ($rSubscribed = $s1->fetch(PDB::FETCH_OBJ)) { // for each subscriber to this thread
            // we are going to check wether there is allready a pending notification for this post to avoid duplicated
//            die ("\$row->IdSubscriber=".$row->IdSubscriber) ;
            $IdMember=$rSubscribed->IdSubscriber ;
            $query = sprintf("select id from posts_notificationqueue where IdPost=%d and IdMember=%d and Status='ToSend'",$IdPost,$IdMember) ;
            $s = $this->dao->query($query);
            if (!$s) {
               throw new PException('prepare_notification Could not retrieve the posts_notificationqueue(1) !');
            }
            $rAllreadySubscribe = $s->fetch(PDB::FETCH_OBJ) ;
            if (isset($rAllreadySubscribe->id)) {
               continue ; // We dont introduce another subscription if there is allready a pending one for this post for this member
            }

            $query = "INSERT INTO `posts_notificationqueue` (`IdMember`, `IdPost`, `created`, `Type`, `TableSubscription`, `IdSubscription`)  VALUES (".$IdMember.",".$IdPost.",now(),'".$Type."','members_tags_subscribed',".$rSubscribed->IdSubscription.")" ;
            $result = $this->dao->query($query);
                   
            if (!$result) {
               throw new PException('prepare_notification  for tag for IdThread #'.$rPost->IdThread.' failed : for Type='.$Type);
            }
        } // end for each subscriber to this tag
		 
		 
		 
        // Check usual members subscription for thread
        // First retrieve the one who are subscribing to this thread
        $query = sprintf("select IdSubscriber,id as IdSubscription from members_threads_subscribed where IdThread=%d",$rPost->IdThread) ;
        $s1 = $this->dao->query($query);
        if (!$s1) {
            throw new PException('prepare_notification Could not retrieve the members_threads_subscribed !');
        }
        while ($rSubscribed = $s1->fetch(PDB::FETCH_OBJ)) { // for each subscriber to this thread
            // we are going to check wether there is allready a pending notification for this post to avoid duplicated
//            die ("\$row->IdSubscriber=".$row->IdSubscriber) ;
            $IdMember=$rSubscribed->IdSubscriber ;
            $query = sprintf("select id from posts_notificationqueue where IdPost=%d and IdMember=%d and Status='ToSend'",$IdPost,$IdMember) ;
            $s = $this->dao->query($query);
            if (!$s) {
               throw new PException('prepare_notification Could not retrieve the posts_notificationqueue(2) !');
            }
            $rAllreadySubscribe = $s->fetch(PDB::FETCH_OBJ) ;
            if (isset($rAllreadySubscribe->id)) {
               continue ; // We dont introduce another subscription if there is allready a pending one for this post for this member
            }

            $query = "INSERT INTO `posts_notificationqueue` (`IdMember`, `IdPost`, `created`, `Type`, `TableSubscription`, `IdSubscription`)  VALUES (".$IdMember.",".$IdPost.",now(),'".$Type."','members_threads_subscribed',".$rSubscribed->IdSubscription.")" ;
            $result = $this->dao->query($query);
                   
            if (!$result) {
               throw new PException('prepare_notification  for thread #'.$rPost->IdThread.' failed : for Type='.$Type);
            }
        } // end for each subscriber to this thread

        
        
    } // end of prepare_notification
    
    
    // This function IsThreadSubscribed return true of the member is subscribing to the IdThread
    // @$IdThread : The thread we want to know if the user is subscribing too
    // @$ParamIdMember optional IdMember, by default set to 0 in this case current logged membver will be used
    public function IsThreadSubscribed($IdThread=0,$ParamIdMember=0) {
       $IdMember=$ParamIdMember ;
       if (isset($_SESSION["IdMember"]) and $IdMember==0) {
                 $IdMember=$_SESSION["IdMember"] ;
       }

       // Check if there is a previous Subscription
       $query = sprintf("select members_threads_subscribed.id as IdSubscribe,IdThread,IdSubscriber from members_threads_subscribed where IdThread=%d and IdSubscriber=%d",$IdThread,$IdMember); 
       $s = $this->dao->query($query);
       if (!$s) {
              throw new PException('IsThreadSubscribed Could not check previous subscription !');
       }
       $row = $s->fetch(PDB::FETCH_OBJ) ;
       return (isset($row->IdSubscribe))  ;
    } // end of IsThreadSubscribed
    
    // This function IsTagSubscribed return true of the member is subscribing to the IdTag
    // @$IdThread : The thread we want to know if the user is subscribing too
    // @$ParamIdMember optional IdMember, by default set to 0 in this case current logged member will be used
    public function IsTagSubscribed($IdTag=0,$ParamIdMember=0) {
       $IdMember=$ParamIdMember ;
       if (isset($_SESSION["IdMember"]) and $IdMember==0) {
                 $IdMember=$_SESSION["IdMember"] ;
       }

       // Check if there is a previous Subscription
       $query = sprintf("select members_tags_subscribed.id as IdSubscribe,IdTag,IdSubscriber from members_tags_subscribed where IdTag=%d and IdSubscriber=%d",$IdTag,$IdMember); 
       $s = $this->dao->query($query);
       if (!$s) {
              throw new PException('IsTagSubscribed Could not check previous subscription !');
       }
       $row = $s->fetch(PDB::FETCH_OBJ) ;
       return (isset($row->IdSubscribe))  ;
    } // end of IsTagSubscribed
    
} // end of class Forums


class Topic {
    public $topicinfo;
    public $posts = array();
}

class Board implements Iterator {
    public function __construct(&$dao, $boardname, $link, $navichain=false, $tags=false, $continent=false, $countrycode=false, $admincode=false, $geonameid=false, $board_description=false) {
        $this->dao =& $dao;
    
        $this->boardname = $boardname;
        $this->board_description = $board_description;
        $this->link = $link;
        $this->continent = $continent;
        $this->countrycode = $countrycode;
        $this->admincode = $admincode;
        $this->geonameid = $geonameid;
        $this->navichain = $navichain;
        $this->tags = $tags;
    }
    
    private $dao;
    private $navichain;
    private $numberOfThreads;
    private $totalThreads;
    
    // This function IsTagSubscribed return true of the member is subscribing to the IdTag
    // @$IdThread : The thread we want to know if the user is subscribing too
    // @$ParamIdMember optional IdMember, by default set to 0 in this case current logged member will be used
    public function IsTagSubscribed($IdTag=0,$ParamIdMember=0) {
       $IdMember=$ParamIdMember ;
       if (isset($_SESSION["IdMember"]) and $IdMember==0) {
                 $IdMember=$_SESSION["IdMember"] ;
       }

       // Check if there is a previous Subscription
       $query = sprintf("select members_tags_subscribed.id as IdSubscribe,IdTag,IdSubscriber from members_tags_subscribed where IdTag=%d and IdSubscriber=%d",$IdTag,$IdMember); 
       $s = $this->dao->query($query);
       if (!$s) {
              throw new PException('IsTagSubscribed Could not check previous subscription !');
       }
       $row = $s->fetch(PDB::FETCH_OBJ) ;
       return (isset($row->IdSubscribe))  ;
    } // end of IsTagSubscribed
    

    public function initThreads($page = 1) {
        
        $where = '';
        
        if ($this->continent) {
            $where .= sprintf("AND `forums_threads`.`continent` = '%s' ", $this->continent);
        }
        if ($this->countrycode) {
            $where .= sprintf("AND `countrycode` = '%s' ", $this->countrycode);
        }
        if ($this->admincode) {
            $where .= sprintf("AND `admincode` = '%s' ", $this->admincode);
        }
        if ($this->geonameid) {
            $where .= sprintf("AND `forums_threads`.`geonameid` = '%s' ", $this->geonameid);
        }
        $wherethread="" ;
        $wherein="" ;
        $tabletagthread="" ;
        if ($this->tags) { // DOes this mean if there is a filter on threads ?
            $ii=0 ;
            foreach ($this->tags as $tag) {
			 	 if ($ii==0) {
//				 echo "\$tag=",$tag ;
			 	 	$this->IdTag=$tag ; // this will cause a subscribe unsubscribe link ot become visible
					if ($this->IsTagSubscribed($this->IdTag,$_SESSION["IdMember"])) $this->IdSubscribe=true ;
				 }
                $tabletagthread.="`tags_threads` as `tags_threads".$ii."`," ;
//                $where .= sprintf("AND (`forums_threads`.`tag1` = '%1\$d' OR `forums_threads`.`tag2` = '%1\$d' OR `forums_threads`.`tag3` = '%1\$d' OR `forums_threads`.`tag4` = '%1\$d' OR `forums_threads`.`tag5` = '%1\$d') ", $tag);
                $wherethread=$wherethread." and `tags_threads".$ii."`.`IdTag`=".$tag." and `tags_threads".$ii."`.`IdThread`=`forums_threads`.`id` "  ;
    //            $where .= sprintf("AND (`forums_threads`.`id` = `tags_threads`.`IdThread` and `tags_threads`.`IdThread`=%d)",$tag);
                $ii++ ;
            }
        }
        
        
        
        
        $query = "SELECT COUNT(*) AS `number` FROM ".$tabletagthread."`forums_threads` WHERE 1 ".$wherethread;
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve Threads!');
        }
        $row = $s->fetch(PDB::FETCH_OBJ);
        $this->numberOfThreads = $row->number;
        
        $from = (Forums::THREADS_PER_PAGE * ($page - 1));
        
        $query = "SELECT SQL_CALC_FOUND_ROWS `forums_threads`.`threadid`,
		 		  `forums_threads`.`id` as IdThread, `forums_threads`.`title`, 
				  `forums_threads`.`IdTitle`, 
				  `forums_threads`.`replies`, 
				  `forums_threads`.`views`, 
				  `forums_threads`.`continent`,
				  `first`.`postid` AS `first_postid`, 
				  `first`.`authorid` AS `first_authorid`, 
				  UNIX_TIMESTAMP(`first`.`create_time`) AS `first_create_time`,
				  `last`.`postid` AS `last_postid`, 
				  `last`.`authorid` AS `last_authorid`, 
				  UNIX_TIMESTAMP(`last`.`create_time`) AS `last_create_time`," ;
        $query .= "`first_user`.`handle` AS `first_author`,`last_user`.`handle` AS `last_author`,`geonames_cache`.`name` AS `geonames_name`, `geonames_cache`.`geonameid`," ;
        $query .= "`geonames_admincodes`.`name` AS `adminname`, `geonames_admincodes`.`admin_code` AS `admincode`,`geonames_countries`.`name` AS `countryname`, `geonames_countries`.`iso_alpha2` AS `countrycode`" ; 
        $query .= "FROM ".$tabletagthread."`forums_threads` LEFT JOIN `forums_posts` AS `first` ON (`forums_threads`.`first_postid` = `first`.`postid`)" ;
        $query .= "LEFT JOIN `forums_posts` AS `last` ON (`forums_threads`.`last_postid` = `last`.`postid`)" ;
        $query .= "LEFT JOIN `user` AS `first_user` ON (`first`.`authorid` = `first_user`.`id`)" ;
        $query .= "LEFT JOIN `user` AS `last_user` ON (`last`.`authorid` = `last_user`.`id`)" ;
        $query .= "LEFT JOIN `geonames_cache` ON (`forums_threads`.`geonameid` = `geonames_cache`.`geonameid`)"; 
        $query .= "LEFT JOIN `geonames_admincodes` ON (`forums_threads`.`admincode` = `geonames_admincodes`.`admin_code` AND `forums_threads`.`countrycode` = `geonames_admincodes`.`country_code`)" ; 
        $query .= "LEFT JOIN `geonames_countries` ON (`forums_threads`.`countrycode` = `geonames_countries`.`iso_alpha2`)" ;
        $query .= " WHERE 1 ".$wherethread." ORDER BY `stickyvalue` asc,`last_create_time` DESC LIMIT ".$from.", ".Forums::THREADS_PER_PAGE ;

//        echo $query,"<hr />" ;

        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve Threads!');
        }
        while ($row = $s->fetch(PDB::FETCH_OBJ)) {
            if (isset($row->continent) && $row->continent) {
                $row->continentid = $row->continent;
                $row->continent = Forums::$continents[$row->continent];
            }

// Now fetch the tags associated with this thread
            $row->NbTags=0 ;
            $query2="select IdTag from tags_threads where IdThread=".$row->IdThread ;
//            echo $query2,"<br />" ;
            $s2 = $this->dao->query($query2);
            if (!$s2) {
               throw new PException('Could not retrieve IdTags for Threads!');
            }
            while ($row2 = $s2->fetch(PDB::FETCH_OBJ)) {
//            echo $row2->IdTag," " ;
                  $row->IdTag[]=$row2->IdTag ;
                  $row->NbTags++ ;
            }
            $this->threads[] = $row;
        }
        
        $query = "SELECT FOUND_ROWS() AS `found_rows`";
        $s = $this->dao->query($query);
        if (!$s) {
            throw new PException('Could not retrieve number of rows!');
        }
        $row = $s->fetch(PDB::FETCH_OBJ);
        $this->totalThreads = $row->found_rows;
    } // end of initThreads
    
    private $threads = array();
    public function getThreads() {
        return $this->threads;
    }
    

    private $continent;
    private $countrycode;
    private $admincode;
    private $geonameid;
    private $tags;

    private $boardname;
    public function getBoardName() {
        return $this->boardname;
    }
    
    private $board_description;
    public function getBoardDescription() {
        return $this->tags;
    }
    
    private $link;
    public function getBoardLink() {
        return $this->link;
    }
    
    public function getNaviChain() {
        return $this->navichain;
    }
    
    public function getNumberOfThreads() {
        return $this->numberOfThreads;
    }
    
    public function getTotalThreads() {
        return $this->totalThreads;
    }
    
    private $subboards = array();
    
    // Add a subboard
    public function add(Board $board) {
        $this->subboards[] = $board;
    }
    
    public function hasSubBoards() {
        return (bool)(count($this->subboards) > 0);
    }
    
    public function rewind() {
        reset($this->subboards);
    }
    
    public function current() {
        $var = current($this->subboards);
        return $var;
    }
    
    public function key() {
        $var = key($this->subboards);
        return $var;
    }
    
    public function next() {
        $var = next($this->subboards);
        return $var;
    }
    
    public function valid() {
        $var = $this->current() !== false;
        return $var;
    }

}


?>