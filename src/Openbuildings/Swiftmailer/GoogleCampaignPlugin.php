<?php

namespace Openbuildings\Swiftmailer;

/**
 * @package    openbuildings\swiftmailer-google-campaign
 * @author     Yasen Yanev <yasen@openbuildings.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class GoogleCampaignPlugin implements \Swift_Events_SendListener
{
	const CAMPAIGN_QUERY_PARAM = 'google_campaign';

	protected $_campaign;
	protected $_additional_campaigns;

	public function __construct(array $campaign, $additional_campaigns = array())
	{
		$this->_campaign = $campaign;
		$this->_additional_campaigns = $additional_campaigns;	
	}

	public function getCampaign()
	{
		return $this->_campaign;
	}

	public function setCampaign(array $campaign)
	{
		$this->_campaign = $campaign;
	}

	public function getAdditionalCampaigns()
	{
		return $this->_additional_campaigns;
	}

	public function setAdditionalCampaigns(array $campaigns)
	{
		$this->_additional_campaigns = $campaigns;
	}

	/**
	 *
	 * @param Swift_Events_SendEvent $evt
	 */
	public function beforeSendPerformed(\Swift_Events_SendEvent $evt)
	{
		$message = $evt->getMessage();

		if ($message->getContentType() === 'text/html') 
		{
			$html = GoogleCampaignPlugin::embedCampaigns($message->getBody(), $message->getCharset(), $this->getCampaign(), $this->getAdditionalCampaigns());
			$message->setBody($html);
		}

		foreach ($message->getChildren() as $part) 
		{
			if (strpos($part->getContentType(), 'text/html') !== FALSE)
			{
				$html = GoogleCampaignPlugin::embedCampaigns($part->getBody(), $message->getCharset(), $this->getCampaign(), $this->getAdditionalCampaigns());
				$part->setBody($html);
			}
		}
	}

	/**
	 * Embed campaigns into the newsletter and return the updated html
	 * @param  string $html                 the email content
	 * @param  string $encoding             email encoding
	 * @param  array  $campaign             the general campaign for the newsletter
	 * @param  array  $additional_campaigns additional campaigns to be replaced
	 * @return string $html                 html with updated hrefs
	 */
	public static function embedCampaigns($html, $encoding = 'UTF-8', $campaign = array(), $additional_campaigns = array())
	{
		// create new DOMDocument
		$document = new \DOMDocument('1.0', $encoding);

		// set error level
		libxml_use_internal_errors(TRUE);

		// load HTML
		$document->loadHTML($html);

		// create new XPath
		$xPath = new \DOMXPath($document);

		// search elements
		$elements = $xPath->query('//a');

		foreach ($elements as $element)
		{
			GoogleCampaignPlugin::replaceLink($element, $campaign, $additional_campaigns);
		}

		return $document->saveHTML();
	}

	/**
	 * Append campaign parameters to the href attribute of $element object
	 * or replace `google_campaign` parameter with the correct campaign params
	 * @param  DomNode $element               the anchor element to be replaced
	 * @param  array   $campaign              the general campaign parameters 
	 * @param  array   $additional_campaigns  additional campaigns for the newsletter
	 * @return DomNode the $element with replaced href attribute
	 */
	public static function replaceLink(\DomElement $element, $campaign = array(), $additional_campaigns = array())
	{
		$href = $element->attributes->getNamedItem('href')->nodeValue;
		$params = array();
		$parts = explode('?', $href);
		$uri = $parts[0];

		if (isset($parts[1]))
		{
			parse_str($parts[1], $params);
		}

		if ( ! count(array_intersect_key($campaign, $params)) AND ! array_key_exists(GoogleCampaignPlugin::CAMPAIGN_QUERY_PARAM, $params))
		{
			$params = array_merge($params, $campaign);
		}
		elseif (array_key_exists(GoogleCampaignPlugin::CAMPAIGN_QUERY_PARAM, $params) AND 
					  $campaign_name = $params[GoogleCampaignPlugin::CAMPAIGN_QUERY_PARAM] AND
					  isset($additional_campaigns[$campaign_name]))
		{
			unset($params[GoogleCampaignPlugin::CAMPAIGN_QUERY_PARAM]);
			$params = array_merge($params, $additional_campaigns[$campaign_name]);
		}

		if (count($params))
		{
			$uri .= '?'.urldecode(http_build_query($params));
		}

		$element->setAttribute('href', $uri);

		return $element;
	}

	/**
	 * Do nothing
	 * @codeCoverageIgnore
	 * @param Swift_Events_SendEvent $evt
	 */
	public function sendPerformed(\Swift_Events_SendEvent $evt)
	{
		// Do Nothing
	}
}
