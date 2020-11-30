<?php

/**
 * xml
 *
 * This is a class designed to format an XML response for an AJAX request, but can be used to build other XML documents.
 */
class xml {

	var $charset;
        var $tags;
        var $current_tag;
        
	/**
	 * xml::xml()
	 *
	 * This is the constructor for the class. It sets up the character set. It defaults to 'utf-8'.
	 *
	 * @param string $charset This is the character set you want to set the XML document to use.
	 *
	 * @return void
	 */
	function xml($charset=null){
		if($charset === null){
			if(isset($GLOBALS['AL_CFG']['charset'])){
				$this->charset = $GLOBALS['AL_CFG']['charset'];
			}else{
				$this->charset = 'utf-8';
			}
		}else{
			$this->charset = $charset;
		}
		
		$this->tags = array();
		$this->current_tag = array(&$this->tags);
	}

	/**
	 * xml::MakeXMLTag()
	 *
	 * This is the function that generates XML tags.
	 *
	 * @param string $tagname This is the name of the XML tag. e.g. <tagname></tagname>
	 * @param string $text This is the value of the XML tag e.g. <tagname>$text</tagname>
	 * @param bool $cdata This sets whether or not to surround $text with cdata tags
	 * @param array $attributes This is an array of attributes to add to the tag.
	 *
	 * @return string
	 */
	function MakeXMLTag($tagname,$text,$cdata=false,$attributes=null){
		$tag = "<".$tagname;

		// check for any attributes
		if(is_array($attributes)){
			foreach ($attributes as $name=>$value) {
				$tag .= " ".$name."=\"".htmlspecialchars($value)."\"";
			}
		}

		// we can set the text to be false if we want a single tag
		if($text !== false){

			$tag .= ">";

			// we get javascript on the other end if there is no text/data
			if(strlen(trim($text)) == 0){
				$tag .= "false";
			}elseif($cdata == true){
				$tag .= "<![CDATA[".$text."]]>";
			}else{
				$tag .= $text;
			}
			$tag .= "</".$tagname.">";

		}else{
			$tag .= " />";
		}
		return $tag . "\n";
	}

	/**
	 * xml::SendXMLResponse()
	 *
	 * This outputs or returns the final XML document
	 *
	 * @param mixed $tags Can be an array or a single string. This is the XML tags to be output.
	 * @param bool $echo This is whether to directly echo out the output (default) or to return it.
	 *
	 * @return mixed
	 */
	function SendXMLResponse($tags,$echo=true){

		// needs to be an array
		if(!is_array($tags)){
			$tags = array($tags);
		}

		// load and format
		foreach ($tags as $key=>$value) {
			$XMLTags.= "\t".$value."\n";
		}
		$charset = $this->charset;
		$response = '<?xml version="1.0" encoding="'.$charset .'"?>'."\n".'<response>'."\n".$XMLTags."\n".'</response>';
		if($echo == true){
			echo $response;
		}else{
			return $response;
		}

	}


	/**
	 * xml::SendXMLHeader()
	 *
	 * This send the content type and charset header to the browser
	 *
	 * @return void
	 */
	function SendXMLHeader(){
		header('Content-Type: text/xml; charset='. $this->charset);
	}
	
	/*
	The following functions are for creating nested XML documents
	*/
	
	/**
	 * xml::OpenXMLTag()
	 *
	 * This opens an XML tag inside the currently opened tag
	 *
	 * @param string $tagname This is the name of the XML tag. e.g. <tagname></tagname>
	 * @param array $attributes This is an array of attributes to add to the tag.
	 *
	 * @return void
	 */
	function OpenXMLTag($tagname,$attributes = null) {
         $c = &$this->GetTag();
	 $c[] = array('name' => $tagname, 'attributes' => $attributes, 'value' => '', 'cdata' => null);
	 $this->current_tag[] = &$c[count($c) - 1]['tags'];
	}
	
        /**
	 * xml::CloseXMLTag()
	 *
	 * This closes the most recently opened tag
	 *
	 * @param string $tagname This is the name of the XML tag. e.g. <tagname></tagname>
	 * @param array $attributes This is an array of attributes to add to the tag.
	 *
	 * @return void
	 */
	function CloseXMLTag() {
	 array_pop($this->current_tag);
	}
	
	/**
	 * xml::AddXMLTag()
	 *
	 * Adds an XML tag to the current open tag
	 *
	 * @param string $tagname This is the name of the XML tag. e.g. <tagname></tagname>
	 * @param string $text This is the value of the XML tag e.g. <tagname>$text</tagname>
	 * @param array $attributes This is an array of attributes to add to the tag.
	 * @param bool $cdata This sets whether or not to surround $text with cdata tags
	 *
	 * @return void
	 */
	function AddXMLTag($tagname,$text,$attributes=null,$cdata=false) {
	  $c = &$this->GetTag();
          $c[] = array('name' => $tagname,'value' => $text,'cdata' => $cdata,'attributes' => $attributes);
	}
	
	/**
	 * xml::GetXML()
	 *
	 * Returns the full XML document
	 *
	 * @return string
	 */
	function GetXML($root = null) {
	   if ($root === null) {
	     $response = '<?xml version="1.0" encoding="'.$this->charset .'"?>';
	     $response .= $this->GetXML($this->tags);
	     return $response;
	   }
	   
          $response = '';
          if (is_array($root)) foreach ($root as $tag) {
            if (isset($tag['tags']) && is_array($tag['tags'])) {
              $value = $this->GetXML($tag['tags']);
            } elseif (isset($tag['value'])) {
              $value = $tag['value'];
            } else {
              $value = '';
            }

            $response .= $this->MakeXMLTag($tag['name'],$value,$tag['cdata'],$tag['attributes']);
          }
          
          if (isset($root['name'])) {
            $response = $this->MakeXMLTag($root['name'],$response,$root['attributes']);
          }
          
          return $response;
	}
      	
      
      /**
        * xml::GetTag()
        *
        * Returns a reference to the currently opened tag
        *
        * @return array
        */
      function &GetTag() {
        end($this->current_tag);
        return $this->current_tag[key($this->current_tag)];
      }
}
?>