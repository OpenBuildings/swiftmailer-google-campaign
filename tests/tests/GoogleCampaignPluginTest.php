<?php

use Openbuildings\Swiftmailer\GoogleCampaignPlugin;

/**
 * @group   google-campaign-plugin
 */
class GoogleCampaignPluginTest extends PHPUnit_Framework_TestCase {

	/**
	 * @covers Openbuildings\Swiftmailer\GoogleCampaignPlugin::beforeSendPerformed
	 */
	public function test_generic_campaign()
	{
		$mailer = Swift_Mailer::newInstance(Swift_NullTransport::newInstance());

		$mailer->registerPLugin(new GoogleCampaignPlugin(array(
			'utm_campaign' => 'newsletter',
			'utm_source' => 'clippings',
			'utm_medium' => 'email',
			'utm_content' => 'main'
		)));

		$message = Swift_Message::newInstance();

		$message->setFrom('test@example.com');
		$message->setTo('test2@example.com');
		$message->setSubject('Test');
		$message->addPart('<a href="http://example.com">Example link</a>', 'text/html');

		$mailer->send($message);

		$children = $message->getChildren();

		$this->assertContains(
			'<a href="http://example.com?utm_campaign=newsletter&amp;utm_source=clippings&amp;utm_medium=email&amp;utm_content=main">Example link</a>',
			$children[0]->getBody()
		);
	}

	/**
	 * @covers Openbuildings\Swiftmailer\GoogleCampaignPlugin::beforeSendPerformed
	 */
	public function test_additional_campaign()
	{
		$mailer = Swift_Mailer::newInstance(Swift_NullTransport::newInstance());

		$mailer->registerPlugin(new GoogleCampaignPlugin(array(
			'utm_campaign' => 'newsletter',
			'utm_source' => 'clippings',
			'utm_medium' => 'email',
			'utm_content' => 'main'
		), array(
			'share' => array(
				'utm_campaign' => 'newsletter',
				'utm_source' => 'openbuildings',
				'utm_medium' => 'email',
				'utm_content' => 'share'
			)
		)));

		$message = Swift_Message::newInstance();

		$message->setFrom('test@example.com');
		$message->setTo('test2@example.com');
		$message->setSubject('Test');
		$message->addPart(
			'<a href="http://example.com">Example link</a>
			<a href="http://openbuildings.com?google_campaign=share">Openbuildings</a>
			<a href="http://clippings.com?utm_campaign=newsletter&utm_source=manual&utm_medium=email">Clippings</a>', 
			'text/html'
		);

		$mailer->send($message);

		$children = $message->getChildren();

		// generic campaign
		$this->assertContains(
			'<a href="http://example.com?utm_campaign=newsletter&amp;utm_source=clippings&amp;utm_medium=email&amp;utm_content=main">Example link</a>',
			$children[0]->getBody()
		);		

		// additional campaign
		$this->assertContains(
			'<a href="http://openbuildings.com?utm_campaign=newsletter&amp;utm_source=openbuildings&amp;utm_medium=email&amp;utm_content=share">Openbuildings</a>',
			$children[0]->getBody()
		);

		// custom link
		$this->assertContains(
			'<a href="http://clippings.com?utm_campaign=newsletter&amp;utm_source=manual&amp;utm_medium=email">Clippings</a>',
			$children[0]->getBody()
		);
	}

	/**
	 * @covers Openbuildings\Swiftmailer\GoogleCampaignPlugin::beforeSendPerformed
	 */
	public function test_message_body()
	{
		$mailer = Swift_Mailer::newInstance(Swift_NullTransport::newInstance());

		$mailer->registerPlugin(new GoogleCampaignPlugin(array(
			'utm_campaign' => 'newsletter',
			'utm_source' => 'clippings',
			'utm_medium' => 'email',
			'utm_content' => 'main'
		)));

		$message = Swift_Message::newInstance();
		$message->setFrom('test@example.com');
		$message->setTo('test2@example.com');
		$message->setSubject('Test');
		$message->setContentType('text/html');
		$message->setBody('<a href="http://example.com">Example link</a>');

		$mailer->send($message);

		// generic campaign
		$this->assertContains(
			'<a href="http://example.com?utm_campaign=newsletter&amp;utm_source=clippings&amp;utm_medium=email&amp;utm_content=main">Example link</a>',
			$message->getBody()
		);	

	}

	/**
	 * @covers Openbuildings\Swiftmailer\GoogleCampaignPlugin::replaceLink
	 */
	public function test_replace_link()
	{
		$dom = new DOMDocument('1.0', 'utf-8');

		$dom_element = $dom->createElement('a');
		$dom_element->setAttribute('href', 'http://example.com');

		GoogleCampaignPlugin::replaceLink($dom_element, array('utm_source' => 'newsletter', 'utm_campaign' => 'my_campaign'));

		$this->assertEquals('http://example.com?utm_source=newsletter&utm_campaign=my_campaign', $dom_element->getAttribute('href'));

		GoogleCampaignPlugin::replaceLink($dom_element, array('utm_source' => 'newsletter', 'utm_campaign' => 'my_second_campaign'));

		$this->assertEquals('http://example.com?utm_source=newsletter&utm_campaign=my_campaign', $dom_element->getAttribute('href'), 'Should not replace link with existing campaign params');

		$dom_element = $dom->createElement('a');
		$dom_element->setAttribute('href', 'http://example.com?test_param=test_value&google_campaign=share');

		GoogleCampaignPlugin::replaceLink($dom_element, array(
			'utm_source' => 'newsletter', 
			'utm_campaign' => 'my_general_campaign'
		), array(
			'share' => array(
				'utm_source' => 'newsletter', 
				'utm_campaign' => 'my_share_campaign'
			)
		));		

		$this->assertEquals('http://example.com?test_param=test_value&utm_source=newsletter&utm_campaign=my_share_campaign', $dom_element->getAttribute('href'), 'Should replace link with share campaign');
	}

	/**
	 * @covers Openbuildings\Swiftmailer\GoogleCampaignPlugin::embedCampaigns
	 */
	public function test_embed_campaigns()
	{
		$html = <<<HTML
<html><head></head><body><a href="http://example.com">Example.com</a></body></html>
HTML;

		$expected_html = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html><head></head><body><a href="http://example.com?utm_source=my_source">Example.com</a></body></html>

HTML;

		$converted_html = GoogleCampaignPlugin::embedCampaigns($html, array('utm_source' => 'my_source'), array(), 'UTF-8');

		$this->assertEquals($expected_html, $converted_html);
	}

	/**
	 * @covers Openbuildings\Swiftmailer\GoogleCampaignPlugin::getCampaign
	 * @covers Openbuildings\Swiftmailer\GoogleCampaignPlugin::setCampaign
	 * @covers Openbuildings\Swiftmailer\GoogleCampaignPlugin::__construct
	 * @covers Openbuildings\Swiftmailer\GoogleCampaignPlugin::getAdditionalCampaigns
	 * @covers Openbuildings\Swiftmailer\GoogleCampaignPlugin::setAdditionalCampaigns
	 */
	public function test_construct_getters_and_setters($value='')
	{
		$google_campaign = new GoogleCampaignPlugin(array(
			'utm_campaign' => 'newsletter',
			'utm_source' => 'clippings',
			'utm_medium' => 'email',
			'utm_content' => 'main'
		));

		$this->assertEquals(array(
			'utm_campaign' => 'newsletter',
			'utm_source' => 'clippings',
			'utm_medium' => 'email',
			'utm_content' => 'main'
		), $google_campaign->getCampaign());

		$google_campaign->setCampaign(array('utm_source' => 'my_general_source'));

		$this->assertEquals(array('utm_source' => 'my_general_source'), $google_campaign->getCampaign());

		$google_campaign->setAdditionalCampaigns(array(
			'share' => array('utm_source' => 'my_source')
		));

		$this->assertEquals(array('share' => array('utm_source' => 'my_source')), $google_campaign->getAdditionalCampaigns());
	}
}