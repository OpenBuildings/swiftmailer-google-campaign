<?php

namespace Openbuildings\Swiftmailer\GoogleCampaignPlugin\Test;

use Openbuildings\Swiftmailer\GoogleCampaignPlugin;
use PHPUnit\Framework\TestCase;
use Swift_Mailer;
use Swift_NullTransport;
use Swift_Message;

/**
 * @coversDefaultClass \Openbuildings\Swiftmailer\GoogleCampaignPlugin
 */
class GoogleCampaignPluginTest extends TestCase
{
    /**
     * @covers ::beforeSendPerformed
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
            '<a href="http://example.com?utm_campaign=newsletter&utm_source=clippings&utm_medium=email&utm_content=main">Example link</a>',
            $children[0]->getBody()
        );
    }

    /**
     * @covers ::beforeSendPerformed
     */
    public function test_additional_campaign()
    {
        $mailer = Swift_Mailer::newInstance(Swift_NullTransport::newInstance());

        $mailer->registerPlugin(
            new GoogleCampaignPlugin(
                [
                    'utm_campaign' => 'newsletter',
                    'utm_source' => 'clippings',
                    'utm_medium' => 'email',
                    'utm_content' => 'main'
                ],
                [
                    'share' => [
                        'utm_campaign' => 'newsletter',
                        'utm_source' => 'openbuildings',
                        'utm_medium' => 'email',
                        'utm_content' => 'share'
                    ]
                ]
            )
        );

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
            '<a href="http://example.com?utm_campaign=newsletter&utm_source=clippings&utm_medium=email&utm_content=main">Example link</a>',
            $children[0]->getBody()
        );

        // additional campaign
        $this->assertContains(
            '<a href="http://openbuildings.com?utm_campaign=newsletter&utm_source=openbuildings&utm_medium=email&utm_content=share">Openbuildings</a>',
            $children[0]->getBody()
        );

        // custom link
        $this->assertContains(
            '<a href="http://clippings.com?utm_campaign=newsletter&utm_source=manual&utm_medium=email">Clippings</a>',
            $children[0]->getBody()
        );
    }

    /**
     * @covers Openbuildings\Swiftmailer\GoogleCampaignPlugin::beforeSendPerformed
     */
    public function test_message_body()
    {
        $mailer = Swift_Mailer::newInstance(Swift_NullTransport::newInstance());

        $mailer->registerPlugin(
            new GoogleCampaignPlugin([
                'utm_campaign' => 'newsletter',
                'utm_source' => 'clippings',
                'utm_medium' => 'email',
                'utm_content' => 'main'
            ])
        );

        $message = Swift_Message::newInstance();
        $message->setFrom('test@example.com');
        $message->setTo('test2@example.com');
        $message->setSubject('Test');
        $message->setContentType('text/html');
        $message->setBody('<a href="http://example.com">Example link</a>');

        $mailer->send($message);

        // generic campaign
        $this->assertContains(
            '<a href="http://example.com?utm_campaign=newsletter&utm_source=clippings&utm_medium=email&utm_content=main">Example link</a>',
            $message->getBody()
        );

    }

    /**
     * @covers Openbuildings\Swiftmailer\GoogleCampaignPlugin::replaceLink
     */
    public function test_replace_link()
    {
        $href = 'http://example.com';

        $href = GoogleCampaignPlugin::replaceLink($href, ['utm_source' => 'newsletter', 'utm_campaign' => 'my_campaign']);

        $this->assertEquals('http://example.com?utm_source=newsletter&utm_campaign=my_campaign', $href);

        $href = GoogleCampaignPlugin::replaceLink($href, ['utm_source' => 'newsletter', 'utm_campaign' => 'my_second_campaign']);

        $this->assertEquals(
            'http://example.com?utm_source=newsletter&utm_campaign=my_campaign',
            $href,
            'Should not replace link with existing campaign params'
        );

        $href = 'http://example.com?test_param=test_value&google_campaign=share';

        $href = GoogleCampaignPlugin::replaceLink(
            $href,
            [
                'utm_source' => 'newsletter',
                'utm_campaign' => 'my_general_campaign'
            ],
            [
                'share' => [
                    'utm_source' => 'newsletter',
                    'utm_campaign' => 'my_share_campaign'
                ]
            ]
        );

        $this->assertEquals(
            'http://example.com?test_param=test_value&utm_source=newsletter&utm_campaign=my_share_campaign',
            $href,
            'Should replace link with share campaign'
        );
    }

    /**
     * @covers Openbuildings\Swiftmailer\GoogleCampaignPlugin::embedCampaigns
     */
    public function test_embed_campaigns()
    {
        $html = <<<HTML
<html><head></head><body><a class="some-class" href="http://example.com" data-some-attribute="test">Example.com
and some text <img src="http://example.com/image.png"/></a></body></html>
HTML;

        $expected_html = <<<HTML
<html><head></head><body><a class="some-class" href="http://example.com?utm_source=my_source" data-some-attribute="test">Example.com
and some text <img src="http://example.com/image.png"/></a></body></html>
HTML;

        $converted_html = GoogleCampaignPlugin::embedCampaigns($html, array('utm_source' => 'my_source'), array(), 'UTF-8');

        $this->assertEquals($expected_html, $converted_html);
    }

    /**
     * @covers ::getCampaign
     * @covers ::setCampaign
     * @covers ::__construct
     * @covers ::getAdditionalCampaigns
     * @covers ::setAdditionalCampaigns
     */
    public function test_construct_getters_and_setters($value='')
    {
        $google_campaign = new GoogleCampaignPlugin([
            'utm_campaign' => 'newsletter',
            'utm_source' => 'clippings',
            'utm_medium' => 'email',
            'utm_content' => 'main'
        ]);

        $this->assertEquals(
            [
                'utm_campaign' => 'newsletter',
                'utm_source' => 'clippings',
                'utm_medium' => 'email',
                'utm_content' => 'main'
            ],
            $google_campaign->getCampaign()
        );

        $google_campaign->setCampaign(['utm_source' => 'my_general_source']);

        $this->assertEquals(['utm_source' => 'my_general_source'], $google_campaign->getCampaign());

        $google_campaign->setAdditionalCampaigns([
            'share' => ['utm_source' => 'my_source']
        ]);

        $this->assertEquals(['share' => ['utm_source' => 'my_source']], $google_campaign->getAdditionalCampaigns());
    }
}
