<?php

class Addons_surveys_build extends Addons_surveys
{
	private $_template;

	public function __construct($template)	{
		$this->addon_id = 'surveys';
		parent::__construct();
		$this->_template = $template;
	}

	public function checkboxAction()
	{
		$this->_template->Assign('widget', array(
				'name'        => GetLang('Addon_Surveys_WidgetCheckboxDefaultName'),
				'description' => GetLang('Addon_Surveys_WidgetCheckboxDefaultDescription'),
				'is_visible'  => 1
			));
		$this->_template->Assign('widgetFields', array(
				'0' => array(
						'value' => GetLang('Addon_Surveys_WidgetValueField') . '1'
					)
			));

		echo $this->getTemplate('checkbox', true);
		exit;
	}

	public function fileAction()
	{
		$this->_template->Assign('widget', array(
				'name'               => GetLang('Addon_Surveys_WidgetFileDefaultName'),
				'description'        => GetLang('Addon_Surveys_WidgetFileDefaultDescription'),
				'allowed_file_types' => GetLang('Addon_Surveys_WidgetFileValueAllowedFileTypes'),
				'is_visible'         => 1
			));

		echo $this->getTemplate('file');
		exit;
	}

	public function radioAction()
	{
		$this->_template->Assign('widget', array(
				'name'        => GetLang('Addon_Surveys_WidgetRadioDefaultName'),
				'description' => GetLang('Addon_Surveys_WidgetRadioDefaultDescription'),
				'is_visible'  => 1
			));
		$this->_template->Assign('widgetFields', array(
				'0' => array(
						'value' => GetLang('Addon_Surveys_WidgetValueField') . '1'
					)
			));

		echo $this->getTemplate('radio');
		exit;
	}

	public function sectionBreakAction()
	{
		$this->_template->Assign('widget', array(
				'name'        => GetLang('Addon_Surveys_WidgetSectionBreakDefaultName'),
				'description' => GetLang('Addon_Surveys_WidgetSectionBreakDefaultDescription')
			));

		echo $this->getTemplate('section.break');
		exit;
	}

	public function selectAction()
	{
		$this->_template->Assign('widget', array(
				'name'        => GetLang('Addon_Surveys_WidgetSelectDefaultName'),
				'description' => GetLang('Addon_Surveys_WidgetSelectDefaultDescription'),
				'is_visible'  => 1
			));
		$this->_template->Assign('widgetFields', array(
				'0' => array(
						'value' => GetLang('Addon_Surveys_WidgetValueField') . '1'
					)
			));

		echo $this->getTemplate('select');
		exit;
	}

	public function selectCountryAction()
	{
		$this->_template->Assign('widget', array(
				'name'        => GetLang('Addon_Surveys_WidgetSelectDefaultName'),
				'description' => GetLang('Addon_Surveys_WidgetSelectDefaultDescription'),
				'is_visible'  => 1
			));
		$this->_template->Assign('widgetFields', array(
				'0'   => array('value' => 'Afghanistan'),
				'1'   => array('value' => 'Albania'),
				'2'   => array('value' => 'Algeria'),
				'3'   => array('value' => 'Andorra'),
				'4'   => array('value' => 'Angola'),
				'5'   => array('value' => 'Antigua and Barbuda'),
				'6'   => array('value' => 'Argentina'),
				'7'   => array('value' => 'Armenia'),
				'8'   => array('value' => 'Australia'),
				'9'   => array('value' => 'Austria'),
				'10'  => array('value' => 'Azerbaijan'),
				'11'  => array('value' => 'Bahamas'),
				'12'  => array('value' => 'Bahrain'),
				'13'  => array('value' => 'Bangladesh'),
				'14'  => array('value' => 'Barbados'),
				'15'  => array('value' => 'Belarus'),
				'16'  => array('value' => 'Belgium'),
				'17'  => array('value' => 'Belize'),
				'18'  => array('value' => 'Benin'),
				'19'  => array('value' => 'Bhutan'),
				'20'  => array('value' => 'Bolivia'),
				'21'  => array('value' => 'Bosnia and Herzegovina'),
				'22'  => array('value' => 'Botswana'),
				'23'  => array('value' => 'Brazil'),
				'24'  => array('value' => 'Brunei'),
				'25'  => array('value' => 'Bulgaria'),
				'26'  => array('value' => 'Burkina Faso'),
				'27'  => array('value' => 'Burundi'),
				'28'  => array('value' => 'Cambodia'),
				'29'  => array('value' => 'Cameroon'),
				'30'  => array('value' => 'Canada'),
				'31'  => array('value' => 'Cape Verde'),
				'32'  => array('value' => 'Central African Republic'),
				'33'  => array('value' => 'Chad'),
				'34'  => array('value' => 'Chile'),
				'35'  => array('value' => 'China'),
				'36'  => array('value' => 'Colombi'),
				'37'  => array('value' => 'Comoros'),
				'38'  => array('value' => 'Congo (Brazzaville)'),
				'39'  => array('value' => 'Congo'),
				'40'  => array('value' => 'Costa Rica'),
				'41'  => array('value' => 'Cote d\'Ivoire'),
				'42'  => array('value' => 'Croatia'),
				'43'  => array('value' => 'Cuba'),
				'44'  => array('value' => 'Cyprus'),
				'45'  => array('value' => 'Czech Republic'),
				'46'  => array('value' => 'Denmark'),
				'47'  => array('value' => 'Djibouti'),
				'48'  => array('value' => 'Dominica'),
				'49'  => array('value' => 'Dominican Republic'),
				'50'  => array('value' => 'East Timor (Timor Timur)'),
				'51'  => array('value' => 'Ecuador'),
				'52'  => array('value' => 'Egypt'),
				'53'  => array('value' => 'El Salvador'),
				'54'  => array('value' => 'Equatorial Guinea'),
				'55'  => array('value' => 'Eritrea'),
				'56'  => array('value' => 'Estonia'),
				'57'  => array('value' => 'Ethiopia'),
				'58'  => array('value' => 'Fiji'),
				'59'  => array('value' => 'Finland'),
				'60'  => array('value' => 'France'),
				'61'  => array('value' => 'Gabon'),
				'62'  => array('value' => 'Gambia, The'),
				'63'  => array('value' => 'Georgia'),
				'64'  => array('value' => 'Germany'),
				'65'  => array('value' => 'Ghana'),
				'66'  => array('value' => 'Greece'),
				'67'  => array('value' => 'Grenada'),
				'68'  => array('value' => 'Guatemala'),
				'69'  => array('value' => 'Guinea'),
				'70'  => array('value' => 'Guinea-Bissau'),
				'71'  => array('value' => 'Guyana'),
				'72'  => array('value' => 'Haiti'),
				'73'  => array('value' => 'Honduras'),
				'74'  => array('value' => 'Hungary'),
				'75'  => array('value' => 'Iceland'),
				'76'  => array('value' => 'India'),
				'77'  => array('value' => 'Indonesia'),
				'78'  => array('value' => 'Iran'),
				'79'  => array('value' => 'Iraq'),
				'80'  => array('value' => 'Ireland'),
				'81'  => array('value' => 'Israel'),
				'82'  => array('value' => 'Italy'),
				'83'  => array('value' => 'Jamaica'),
				'84'  => array('value' => 'Japan'),
				'85'  => array('value' => 'Jordan'),
				'86'  => array('value' => 'Kazakhstan'),
				'87'  => array('value' => 'Kenya'),
				'88'  => array('value' => 'Kiribati'),
				'89'  => array('value' => 'Korea, North'),
				'90'  => array('value' => 'Korea, South'),
				'91'  => array('value' => 'Kuwait'),
				'92'  => array('value' => 'Kyrgyzstan'),
				'93'  => array('value' => 'Laos'),
				'94'  => array('value' => 'Latvia'),
				'95'  => array('value' => 'Lebanon'),
				'96'  => array('value' => 'Lesotho'),
				'97'  => array('value' => 'Liberia'),
				'98'  => array('value' => 'Libya'),
				'99'  => array('value' => 'Liechtenstein'),
				'100' => array('value' => 'Lithuania'),
				'101' => array('value' => 'Luxembourg'),
				'102' => array('value' => 'Macedonia'),
				'103' => array('value' => 'Madagascar'),
				'104' => array('value' => 'Malawi'),
				'105' => array('value' => 'Malaysia'),
				'106' => array('value' => 'Maldives'),
				'107' => array('value' => 'Mali'),
				'108' => array('value' => 'Malta'),
				'109' => array('value' => 'Marshall Islands'),
				'110' => array('value' => 'Mauritania'),
				'111' => array('value' => 'Mauritius'),
				'112' => array('value' => 'Mexico'),
				'113' => array('value' => 'Micronesia'),
				'114' => array('value' => 'Moldova'),
				'115' => array('value' => 'Monaco'),
				'116' => array('value' => 'Mongolia'),
				'117' => array('value' => 'Morocco'),
				'118' => array('value' => 'Mozambique'),
				'119' => array('value' => 'Myanmar'),
				'120' => array('value' => 'Namibia'),
				'121' => array('value' => 'Nauru'),
				'122' => array('value' => 'Nepa'),
				'123' => array('value' => 'Netherlands'),
				'124' => array('value' => 'New Zealand'),
				'125' => array('value' => 'Nicaragua'),
				'126' => array('value' => 'Niger'),
				'127' => array('value' => 'Nigeria'),
				'128' => array('value' => 'Norway'),
				'129' => array('value' => 'Oman'),
				'130' => array('value' => 'Pakistan'),
				'131' => array('value' => 'Palau'),
				'132' => array('value' => 'Panama'),
				'133' => array('value' => 'Papua New Guinea'),
				'134' => array('value' => 'Paraguay'),
				'135' => array('value' => 'Peru'),
				'136' => array('value' => 'Philippines'),
				'137' => array('value' => 'Poland'),
				'138' => array('value' => 'Portugal'),
				'139' => array('value' => 'Qatar'),
				'140' => array('value' => 'Romania'),
				'141' => array('value' => 'Russia'),
				'142' => array('value' => 'Rwanda'),
				'143' => array('value' => 'Saint Kitts and Nevis'),
				'144' => array('value' => 'Saint Lucia'),
				'145' => array('value' => 'Saint Vincent'),
				'146' => array('value' => 'Samoa'),
				'147' => array('value' => 'San Marino'),
				'148' => array('value' => 'Sao Tome and Principe'),
				'149' => array('value' => 'Saudi Arabia'),
				'150' => array('value' => 'Senegal'),
				'151' => array('value' => 'Serbia and Montenegro'),
				'152' => array('value' => 'Seychelles'),
				'153' => array('value' => 'Sierra Leone'),
				'154' => array('value' => 'Singapore'),
				'155' => array('value' => 'Slovakia'),
				'156' => array('value' => 'Slovenia'),
				'157' => array('value' => 'Solomon Islands'),
				'158' => array('value' => 'Somalia'),
				'159' => array('value' => 'South Africa'),
				'160' => array('value' => 'Spain'),
				'161' => array('value' => 'Sri Lanka'),
				'162' => array('value' => 'Sudan'),
				'163' => array('value' => 'Suriname'),
				'164' => array('value' => 'Swaziland'),
				'165' => array('value' => 'Sweden'),
				'166' => array('value' => 'Switzerland'),
				'167' => array('value' => 'Syria'),
				'168' => array('value' => 'Taiwan'),
				'169' => array('value' => 'Tajikistan'),
				'170' => array('value' => 'Tanzania'),
				'171' => array('value' => 'Thailand'),
				'172' => array('value' => 'Togo'),
				'173' => array('value' => 'Tonga'),
				'174' => array('value' => 'Trinidad and Tobago'),
				'175' => array('value' => 'Tunisia'),
				'176' => array('value' => 'Turkey'),
				'177' => array('value' => 'Turkmenistan'),
				'178' => array('value' => 'Tuvalu'),
				'179' => array('value' => 'Uganda'),
				'180' => array('value' => 'Ukraine'),
				'181' => array('value' => 'United Arab Emirates'),
				'182' => array('value' => 'United Kingdom'),
				'183' => array('value' => 'United States'),
				'184' => array('value' => 'Uruguay'),
				'185' => array('value' => 'Uzbekistan'),
				'186' => array('value' => 'Vanuatu'),
				'187' => array('value' => 'Vatican City'),
				'188' => array('value' => 'Venezuela'),
				'189' => array('value' => 'Vietnam'),
				'190' => array('value' => 'Yemen'),
				'191' => array('value' => 'Zambia'),
				'192' => array('value' => 'Zimbabwe')
			));

		echo $this->getTemplate('select');
		exit;
	}

	public function textAction()
	{
		$this->_template->Assign('widget', array(
				'name'        => GetLang('Addon_Surveys_WidgetTextDefaultName'),
				'description' => GetLang('Addon_Surveys_WidgetTextDefaultDescription'),
				'is_visible'  => 1
			));

		echo $this->getTemplate('text');
		exit;
	}

	public function textareaAction()
	{
		$this->_template->Assign('widget', array(
				'name'        => GetLang('Addon_Surveys_WidgetTextareaDefaultName'),
				'description' => GetLang('Addon_Surveys_WidgetTextareaDefaultDescription'),
				'is_visible'  => 1
			));

		echo $this->getTemplate('textarea');
		exit;
	}



	public function getTemplate($type)
	{
		$this->_template->Assign('randomId', 'widget_' . md5(microtime()));

		$arr = array(
				//'id'   => iwp_engine::getParam('id'),
				'html' => $this->_template->ParseTemplate('widget.' . $type, true)
			);

		return json_encode($arr);
	}
}