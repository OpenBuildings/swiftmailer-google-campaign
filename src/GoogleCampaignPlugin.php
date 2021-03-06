<?php

namespace Openbuildings\Swiftmailer;

use Swift_Events_SendListener;
use Swift_Events_SendEvent;

/**
 * @author     Yasen Yanev <yasen@openbuildings.com>
 * @copyright  (c) 2014 Clippings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class GoogleCampaignPlugin implements Swift_Events_SendListener
{
    const CAMPAIGN_QUERY_PARAM = 'google_campaign';

    /**
     * Embed campaigns into the newsletter and return the updated html
     *
     * @param  string $html                 the email content
     * @param  array  $campaign             the general campaign for the newsletter
     * @param  array  $additionalCampaigns  additional campaigns to be replaced
     * @return string $html                 html with updated hrefs
     */
    public static function embedCampaigns(
        $html,
        $campaign = [],
        $additionalCampaigns = []
    ) {
        $pattern = '/<a(\s[^>]*)href="([^"]*)"([^>]*)>/si';

        $html = preg_replace_callback($pattern, function($matches) use ($campaign, $additionalCampaigns) {
            $href = GoogleCampaignPlugin::replaceLink($matches[2], $campaign, $additionalCampaigns);
            return "<a{$matches[1]}href=\"{$href}\"{$matches[3]}>";
        }, $html);

        return $html;
    }

    /**
     * Append campaign parameters to the href attribute of $element object
     * or replace `google_campaign` parameter with the correct campaign params
     *
     * @param  string  $href                  the href which needs to be replaced
     * @param  array   $campaign              the general campaign parameters
     * @param  array   $additionalCampaigns  additional campaigns for the newsletter
     * @return DomNode the $element with replaced href attribute
     */
    public static function replaceLink($href, $campaign = array(), $additionalCampaigns = array())
    {
        $href = html_entity_decode($href);
        $params = array();
        $parts = explode('?', $href);
        $uri = $parts[0];

        if (isset($parts[1])) {
            parse_str($parts[1], $params);
        }

        if ( ! count(array_intersect_key($campaign, $params)) and ! array_key_exists(GoogleCampaignPlugin::CAMPAIGN_QUERY_PARAM, $params)) {
            $params = array_merge($params, $campaign);
        } elseif (
            array_key_exists(GoogleCampaignPlugin::CAMPAIGN_QUERY_PARAM, $params)
            and $campaign_name = $params[GoogleCampaignPlugin::CAMPAIGN_QUERY_PARAM]
            and isset($additionalCampaigns[$campaign_name])
        ) {
            unset($params[GoogleCampaignPlugin::CAMPAIGN_QUERY_PARAM]);
            $params = array_merge($params, $additionalCampaigns[$campaign_name]);
        }

        if (count($params)) {
            $uri .= '?'.urldecode(http_build_query($params));
        }

        return $uri;
    }

    private $campaign;
    private $additionalCampaigns;

    public function __construct(array $campaign, $additionalCampaigns = [])
    {
        $this->campaign = $campaign;
        $this->additionalCampaigns = $additionalCampaigns;
    }

    public function getCampaign()
    {
        return $this->campaign;
    }

    public function setCampaign(array $campaign)
    {
        $this->campaign = $campaign;
    }

    public function getAdditionalCampaigns()
    {
        return $this->additionalCampaigns;
    }

    public function setAdditionalCampaigns(array $campaigns)
    {
        $this->additionalCampaigns = $campaigns;
    }

    /**
     *
     * @param Swift_Events_SendEvent $event
     */
    public function beforeSendPerformed(Swift_Events_SendEvent $event)
    {
        $message = $event->getMessage();

        if ($message->getContentType() === 'text/html') {
            $html = GoogleCampaignPlugin::embedCampaigns(
                $message->getBody(),
                $this->getCampaign(),
                $this->getAdditionalCampaigns()
            );

            $message->setBody($html);
        }

        foreach ($message->getChildren() as $part) {
            if (strpos($part->getContentType(), 'text/html') !== false) {
                $html = GoogleCampaignPlugin::embedCampaigns(
                    $part->getBody(),
                    $this->getCampaign(),
                    $this->getAdditionalCampaigns()
                );

                $part->setBody($html);
            }
        }
    }

    /**
     * Do nothing
     * @codeCoverageIgnore
     * @param Swift_Events_SendEvent $event
     */
    public function sendPerformed(Swift_Events_SendEvent $event)
    {
        // Do Nothing
    }
}
