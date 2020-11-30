<?php
/**
 * This is the dynamic content tags api class.
 * It handles all of the database stuff (creating/loading/updating).
 *
 * @uses IEM::getDatabase()
 *
 * @package SendStudio
 * @subpackage DynamicContentTags
 *
 */
class DynamicContentTag_Api_Tag {

    /**
     * The ID of the dynamic content tag id
     *
     * @var int
     */
    private $tagId = 0;

    /**
     * The Name of the dynamic content tag id
     *
     * @var string
     */
    private $name = null;

    /**
     * The Creation date/time of the dynamic content tag id
     *
     * @var int
     */
    private $createdDate = null;

    /**
     * The Owner id of the dynamic content tag id
     *
     * @var int
     */
    private $ownerId = null;

    /**
     * This hold the blocks of the dynamic content tag id
     *
     * @var array
     */
    private $blocks = array();

    /**
     * The Name of the dynamic content tag id
     *
     * @var int
     */
    private $lists = array();

    /**
     * A local database connection
     *
     * @var resource
     */
    private $db = null;

    /**
     * __construct
     * Constructor for tag init
     *
     * @param int $tagId                The dynamic content tag id
     * @param string $name              The name of dynamic content tag
     * @param int $createDate           The creation date of dynamic content tag
     * @param int $ownerId			    The owner id of dynamic content tag
     * @param array $blocks             A list of blocks objects of the dynamic content tag
     * @param array $lists              A list of contact list ids of the dynamic content tag
     *
     * @return void                     This create a new instance of the dynamic content tag object
     *
     */
    public function __construct($tagId, $name ='', $createDate = 0, $ownerId = 0, $blocks = array(), $lists = array()) {
        $this->db = IEM::getDatabase();
        if (func_num_args() == 1) {
            $this->load($tagId);
        } else {
            $this->setTagId($tagId);
            $this->setName($name);
            $this->setCreatedDate($createDate);
            $this->setBlocks($blocks);
            $this->setLists($lists);
            $this->setOwnerId($ownerId);
        }
    }

    /**
     * load
     * This load the tag into memory
     *
     * @return void  This only load the tags from database to memory
     */
    public function load($tagId) {
        $query = "SELECT * FROM [|PREFIX|]dynamic_content_tags dct"
            . " WHERE dct.tagid = '". $tagId . "'"
        ;

        $user = GetUser();
        if (!$user->isAdmin()) {
        	$query .= " AND dct.ownerid = '{$user->Get('userid')}' ";
        }

        $result = $this->db->Query($query);
        while ($row = $this->db->Fetch($result)) {
            $this->setTagId($row['tagid']);
            $this->setName($row['name']);
            $this->setCreatedDate($row['createdate']);
            $this->setOwnerId($row['ownerid']);
        }
    }

    /**
     * getTagId
     * This return the id of the dynamic content tag
     *
     * @return int  Return the dynamic content tag Id
     */
    public function getTagId() {
        return $this->tagId;
    }

    /**
     * getOwnerId
     * This return the id of the dynamic content tag owner
     *
     * @return int  Return the dynamic content tag owner id
     */
    public function getOwnerId() {
        return $this->ownerId;
    }

    /**
     * setOwnerId
     * This set the id of the dynamic content tag owner
     *
     * @return void Return none, it only sets the owner id
     */
    public function setOwnerId($newVal) {
        $this->ownerId = $newVal;
    }

    /**
     * getName
     * This return the name of the dynamic content tag
     *
     * @return string Return the dynamic content tag Id
     */
    public function getName() {
        return $this->name;
    }

    /**
     * getCreatedDate
     * This return the timestamp of the dynamic content tag
     *
     * @return int  Return the creation timestamp of dynamic content tag
     */
    public function getCreatedDate() {
        return $this->createdDate;
    }

    /**
     * getBlocks
     * This return the blocks of dynamic content tag
     *
     * @return array  Return the Blocks objects of dynamic content tag
     */
    public function getBlocks() {
        return $this->blocks;
    }

    /**
     * getLists
     * This return the list ids of the dynamic content tag
     *
     * @return array  Return the list ids of dynamic content tag
     */
    public function getLists() {
        return $this->lists;
    }

    /**
     * setTagId
     * This set the dynamic content tag id
     *
     * @return void  This function only set dynamic content tag id
     */
    public function setTagId($newVal) {
        $this->tagId=$newVal;
    }

    /**
     * setName
     * This set the dynamic content tag name
     *
     * @return void  This function only set dynamic content tag name
     */
    public function setName($newVal) {
        $this->name=$newVal;
    }

    /**
     * setCreatedDate
     * This set the dynamic content tag creation date
     *
     * @return void  This function only set dynamic content tag creation date
     */
    public function setCreatedDate($newVal) {
        $this->createdDate=$newVal;
    }

    /**
     * setBlocks
     * This set the dynamic content tag's blocks
     *
     * @return void  This function only set dynamic content tag's blocks
     */
    public function setBlocks($newVal) {
        $this->blocks=$newVal;
    }

    /**
     * setLists
     * This set the dynamic content tag id
     *
     * @return void  This function only set dynamic content tag id
     */
    public function setLists($newVal) {
        if (is_array($newVal)) {
            $this->lists=$newVal;
        }
    }

    /**
     * loadBlocks
     * This load the blocks to this dynamic content tag
     *
     * @return void  This function only load the dynamic content tag's blocks
     */
    public function loadBlocks () {
        $tmpBlock = array();
        $query = "SELECT * FROM [|PREFIX|]dynamic_content_block dcb"
            . " WHERE dcb.tagid = '". $this->getTagId() . "'"
            . " ORDER BY dcb.sortorder ASC"
        ;

        $result = $this->db->Query($query);
        while ($row = $this->db->Fetch($result)) {
            $tmpBlock[] = new DynamicContentTag_Api_Block($row['blockid'], $row['name'], $row['rules'], $row['activated'], $row['sortorder'], $this->getTagId());
        }
        $this->setBlocks($tmpBlock);
    }

    /**
     * loadLists
     * This load the lists to this dynamic content tag
     *
     * @return void  This function only load the dynamic content tag's list
     */
    public function loadLists() {
        $tmpList = array();
        $query = "SELECT * FROM [|PREFIX|]list_tags list"
            . " WHERE list.tagid = '". $this->getTagId() . "'"
        ;

        $result = $this->db->Query($query);
        while ($row = $this->db->Fetch($result)) {
            $tmpList[] = $row['listid'];
        }
        $this->setLists($tmpList);
    }

    /**
     * isListExist
     * This check if a list of contact lists are exist in the list of this dynamic content list
     *
     * @return void  Return true if list exists. Otherwise, return false;
     */
    public function isListExist($lists) {
        return array_intersect($this->lists, $lists);
    }

    /**
     * save
     * This will save all the items related to dynamic content tags. E.g. Lists and content blocks.
     *
     * @return void  Return true if everything are saved. Otherwise return false
     */
    public function save() {
        if (!sizeof($this->blocks)) {
            return false;
        }

        // tag saving
        $this->db->StartTransaction();
        if ($this->tagId == 0) {
            $query = "INSERT INTO [|PREFIX|]dynamic_content_tags";
            $query .= " (name, createdate, ownerid)";
            $query .= " VALUES";
            $query .= " ('" . $this->db->Quote(trim($this->name)) . "', '" . intval($this->createdDate) . "', '" . intval($this->ownerId) . "')";

            $result = $this->db->Query($query);
            if (!$result) {
                $this->db->RollBackTransaction();
                return false;
            }
            $this->tagId = $this->db->LastId('[|PREFIX|]dynamic_content_tags');
        } else {
            $query = "UPDATE [|PREFIX|]dynamic_content_tags SET ";
            $query .= " name='" . $this->db->Quote(trim($this->name)). "', ";
            $query .= " createdate='" . intval($this->createdDate) . "' ";
            $query .= " WHERE tagid=" . $this->tagId;
            $result = $this->db->Query($query);
            if (!$result) {
                $this->db->RollBackTransaction();
                return false;
            }
        }

        // list tag saving
        if (is_array($this->lists) && sizeof($this->lists)) {
            $query = "DELETE FROM [|PREFIX|]list_tags WHERE tagid=" . $this->tagId;
            $result = $this->db->Query($query);
            if (!$result) {
                $this->db->RollBackTransaction();
                return false;
            }

            foreach ($this->lists as $eachList) {
                $query = "INSERT INTO [|PREFIX|]list_tags";
                $query .= " (tagid, listid)";
                $query .= " VALUES";
                $query .= " ('" . intval($this->tagId) . "', '" . intval($eachList) . "')";
                $result = $this->db->Query($query);
                if (!$result) {
                    $this->db->RollBackTransaction();
                    return false;
                }
            }
        }

        // block saving
        foreach ($this->blocks as $eachBlock) {
            $eachBlock->setTagId($this->tagId);
            if(!$eachBlock->save()) {
                $this->db->RollBackTransaction();
                return false;
            }
        }

        $this->db->CommitTransaction();
        return $this->tagId;
    }
}
