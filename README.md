# Swiftmailer GoogleCampaign Plugin 

[![Build Status](https://travis-ci.org/OpenBuildings/swiftmailer-google-campaign.png?branch=master)](https://travis-ci.org/OpenBuildings/swiftmailer-google-campaign)
[![Coverage Status](https://coveralls.io/repos/OpenBuildings/swiftmailer-google-campaign/badge.png?branch=master)](https://coveralls.io/r/OpenBuildings/swiftmailer-google-campaign?branch=master)
[![Latest Stable Version](https://poser.pugx.org/openbuildings/swiftmailer-google-campaign/v/stable.png)](https://packagist.org/packages/openbuildings/swiftmailer-google-campaign)

A swiftmailer plugin that appends automatically to all email links google campaign parameters from a config file or a custom one to the links you want

## Usage

```php
$mailer = Swift_Mailer::newInstance();

$mailer->registerPLugin(new GoogleCampaignPlugin(array('utm_source' => 'source', 'utm_campaign' => 'email', 'utm_medium' => 'email'));
```


## License

Copyright (c) 2013, OpenBuildings Ltd. Developed by Yasen Yanev as part of [clippings.com](http://clippings.com)

Under BSD-3-Clause license, read LICENSE file.