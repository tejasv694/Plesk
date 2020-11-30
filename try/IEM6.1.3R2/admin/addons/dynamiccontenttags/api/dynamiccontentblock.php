<?php
/**
 * This is the dynamic content tags' blocks api class.
 * It handles all of the database stuff (creating/loading/updating).
 *
 * @uses IEM::getDatabase()
 *
 * @package SendStudio
 * @subpackage DynamicContentTags
 *
 */
class DynamicContentTag_Api_Block {

    /**
     * The ID of the content block
     *
     * @var int
     */
    private $blockId = 0;

    /**
     * The Tag ID of the content block
     *
     * @var int
     */
    private $tagId = 0;

    /**
     * The Name of the content block
     *
     * @var string
     */
    private $name = null;

    /**
     * A set of Rules of the content block
     *
     * @var string
     */
    private $rules = null;

    /**
     * The defaulted status of the content block
     * This should be either 0 or 1
     *
     * @var int
     */
    private $activated = null;

    /**
     * The sort order of the content block
     *
     * @var int
     */
    private $sortorder = null;

    /**
     * A local database connection
     *
     * @var resource
     */
    private $db = null;

    /**
     * __construct
     * Constructor for block init
     *
     * @param int $blockId              The ID of block
     * @param string $name              The Name of block
     * @param string $rules             The Rules Set of block
     * @param int $activated            The Default Status of block
     * @param int $sortorder            The Sort order of block
     * @param int $tagId                The Tag Id of block
     *
     * @return void                     This create a new instance of the block object
     *
     */
    public function __construct($blockId, $name, $rules, $activated, $sortorder, $tagId) {
        $this->setBlockId($blockId);
        $this->setName($name);
        $this->setRules($rules);
        $this->setActivated($activated);
        $this->setSortOrder($sortorder);
        $this->setTagId($tagId);
        $this->db = IEM::getDatabase();
    }

    /**
     * getBlockId
     * This return the id of the block
     *
     * @return int                      Return the Block Id
     */
    public function getBlockId() {
        return $this->blockId;
    }

    /**
     * getName
     * This return the name of the block
     *
     * @return string Return the Block Name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * getRules
     * This return a set of block rules in json format
     *
     * @return string Return the Block Rules
     */
    public function getRules() {
        return $this->rules;
    }

    /**
     * getDecodedRules
     * This return the decode version of the block rules
     *
     * @return array Return the decode block rules
     */
    public function getDecodedRules() {
        $pattern = '/[\t]+/i';
        $replacement = '    ';
        $rule = preg_replace($pattern, $replacement, $this->rules);
        $myTempVar = json_decode($rule);
        return json_decode($rule);
    }

    /**
     * getTagId
     * This return the Tag Id of the block
     *
     * @return int Return the Block's Tag Id
     */
    public function getTagId() {
        return $this->tagId;
    }

    /**
     * isActivated
     * This return the default set of the block id
     *
     * @return int Return 1 is this is a default blocd. Otherwise, return 0
     */
    public function isActivated() {
        return $this->activated;
    }

    /**
     * getSortOrder
     * This return the sort order of the block
     *
     * @return int Return the block's soft order
     */
    public function getSortOrder() {
        return $this->sortorder;
    }

    /**
     * setBlockId
     * This set the block it
     *
     * @return void This function only set the block id
     */
    public function setBlockId($newVal) {
        $this->blockId=$newVal;
    }

    /**
     * setTagId
     * This set the block's tag id
     *
     * @return void This function only set the block's tag id
     */
    public function setTagId($newVal) {
        $this->tagId=$newVal;
    }

    /**
     * setName
     * This set the block name
     *
     * @return void This function only set the block name
     */
    public function setName($newVal) {
        $this->name=$newVal;
    }

    /**
     * setSortOrder
     * This set the block's sort order
     *
     * @return void This function only set the block's sort order
     */
    public function setSortOrder($newVal) {
        $this->sortorder=$newVal;
    }

    /**
     * setRules
     * This set the block's rules set
     *
     * @return void This function only set the block rules set
     */
    public function setRules($newVal) {
        $this->rules=str_ireplace(array('<html><head></head><body>', '</body></html>'), array(), $newVal);
    }

    /**
     * setActivated
     * This set the block's default set
     *
     * @return void This function only set the block default set
     */
    public function setActivated($status=1) {
        $this->activated=$status;
    }

    /**
     * save
     * This will save the values of the block object to database. It will create a new one if the block id is 0. Otherwise, it will update it instead
     *
     * @return Boolean Return true if data update/created successfully. Otherwise return false
     */
    public function save() {
        if ($this->blockId == 0) {
            if (!$this->tagId) {
                return false;
            }

            $query = "INSERT INTO [|PREFIX|]dynamic_content_block";
            $query .= " (tagid, name, rules, activated, sortorder)";
            $query .= " VALUES";
            $query .= " ('" . intval($this->tagId) . "', '" . $this->db->Quote($this->name) . "', '" . $this->db->Quote($this->rules) . "', " . intval($this->activated) . ", " . intval($this->sortorder) . ")";

            $result = $this->db->Query($query);
            if (!$result) {
                return false;
            }
            $this->blockId = $this->db->LastId('[|PREFIX|]dynamic_content_block');
        } else {
            $query = "UPDATE [|PREFIX|]dynamic_content_block SET ";
            $query .= " name='" . $this->db->Quote($this->name) . "', ";
            $query .= " rules='" . $this->db->Quote($this->rules) . "', ";
            $query .= " activated='" . intval($this->activated) . "', ";
            $query .= " sortorder='" . intval($this->sortorder) . "' ";
            $query .= " WHERE blockid=" . $this->blockId;
            $result = $this->db->Query($query);
            if (!$result) {
                return false;
            }

        }
        return $this->blockId;
    }
}
