<?php

/**
 * Include the base API class if we haven't already.
 */
require_once(dirname(__FILE__) . '/api.php');

/**
 * Class for checking the spam rating of an email campaign.
 */
class Spam_Check_API extends API {

	/**
	 * Spam Ratings
	 * The maximum spam score an email can have before it's classed as spam.
	 */
	const RATING_NOT_SPAM = 0;
	const RATING_ALERT = 4;
	const RATING_SPAM = 5;

	/**
	 * rules
	 * These are the spam rules used to compare text against.
	 * They are of the form ['$category' => ['$position' => [regex, description, score]]].
	 *
	 * @var Array
	 */
	private $rules = array();

	/**
	 * Constructor
	 * Loads the spam rules from the admin/resources/spam_rules directory.
	 *
	 * @return Void Does not return anything.
	 */
	public function __construct() {
		$spam_rule_files = list_files(SENDSTUDIO_RESOURCES_DIRECTORY . '/spam_rules');

		foreach ($spam_rule_files as $spam_rule) {
			$filename_parts = pathinfo($spam_rule);
			if (isset($filename_parts['extension']) && $filename_parts['extension'] == 'php') {
				require(SENDSTUDIO_RESOURCES_DIRECTORY . '/spam_rules/' . $spam_rule);
			}
		}

		$this->rules = &$GLOBALS['Spam_Rules'];
	}

	/**
	 * Check
	 * It will use the regexps rules loaded to find and determine any rules that have been broken.
	 *
	 * @param string $content The contents to check.
	 *
	 * @return array Results of the form ['score' => float, 'broken_rules' => array].
	 */
	private function Check($content)
	{
		$score = 0;
		$broken_rules = array();

		foreach ($this->rules as $category) {
			// We only have support for 'body' rules at the moment.
			foreach ($category['body'] as $rule) {
				if (preg_match($rule[0], $content)) {
					$score += $rule[2];
					// Include the score with the broken rule.
					$broken_rules[] = array($rule[1], $rule[2]);
				}
			}
		}

		return array(
			'score' => $score,
			'broken_rules' => $broken_rules,
		);
	}

	/**
	 * Process
	 * This will scan the email's text and html content.
	 * It keeps score so far and also remembers which rules you have broken so we can get a list of them.
	 *
	 * @uses Check
	 *
	 * @return array Results of the form ['type' => ['rating' => int, 'score' => float, 'rules_broken' => array]].
	 */
	public function Process($text, $html=false)
	{
		$broken_rules = array();
		$score = array();

		foreach (array('text', 'html') as $type) {
			$broken_rules[$type] = array();
			$score[$type] = 0;

			$content = $$type;
			if (!$content) {
				continue;
			}

			$result = $this->Check($content);
			$score[$type] = $result['score'];
			$broken_rules[$type] = $result['broken_rules'];
		}


		if (empty($text)) {
			// Spamassasin has a rule where emails must always contains "text" part.
			$score['text'] += 1.2;
			$broken_rules['text'][] = array('Message only has text/html MIME parts', '1.2');
		}

		// Spamassasin has a rule where HTML and TEXT contents should be similar.
		if (!empty($html) && !empty($text)) {
			$similarity = 0;
			similar_text(strip_tags($html), $text, $similarity);
			if ($similarity < 50) {
				$rule = array('HTML and text parts are different', '1.5');
				$broken_rules['text'][] = $rule;
				$broken_rules['html'][] = $rule;
				$score['text'] += 1.5;
				$score['html'] += 1.5;
			}
		}

		// Determine final spam rating based on the score
		$rating = array();
		$rating_list = array(self::RATING_NOT_SPAM, self::RATING_ALERT, self::RATING_SPAM);
		foreach (array('text', 'html') as $type) {
			foreach ($rating_list as $rating_score) {
				if ($score[$type] >= $rating_score) {
					$rating[$type] = $rating_score;
				}
			}
		}

		// Return the appropriate results.
		$result = array('text' => array(), 'html' => array());

		foreach (array('text', 'html') as $type) {
			if (empty($$type) && $score[$type] == 0) {
				continue;
			}
			$result[$type] = array(
				'rating' => $rating[$type],
				'score' => $score[$type],
				'broken_rules' => $broken_rules[$type],

			);
		}

		return $result;
	}
}
