<?php

use Openbuildings\Swiftmailer\GoogleCampaignPlugin;

/**
 * @group   google-campaign-plugin
 */
class GoogleCampaignPluginTest extends PHPUnit_Framework_TestCase {

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

		$this->assertContains(
			'<a href="http://example.com?utm_campaign=newsletter&amp;utm_source=clippings&amp;utm_medium=email&amp;utm_content=main">Example link</a>',
			$message->getChildren()[0]->getBody()
		);
	}

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

		// generic campaign
		$this->assertContains(
			'<a href="http://example.com?utm_campaign=newsletter&amp;utm_source=clippings&amp;utm_medium=email&amp;utm_content=main">Example link</a>',
			$message->getChildren()[0]->getBody()
		);		

		// additional campaign
		$this->assertContains(
			'<a href="http://openbuildings.com?utm_campaign=newsletter&amp;utm_source=openbuildings&amp;utm_medium=email&amp;utm_content=share">Openbuildings</a>',
			$message->getChildren()[0]->getBody()
		);

		// custom link
		$this->assertContains(
			'<a href="http://clippings.com?utm_campaign=newsletter&amp;utm_source=manual&amp;utm_medium=email">Clippings</a>',
			$message->getChildren()[0]->getBody()
		);
	}

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
}