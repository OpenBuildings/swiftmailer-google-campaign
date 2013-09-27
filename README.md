# Swiftmailer GoogleCampaign Plugin 

[![Build Status](https://travis-ci.org/OpenBuildings/swiftmailer-google-campaign.png?branch=master)](https://travis-ci.org/OpenBuildings/swiftmailer-google-campaign)
[![Coverage Status](https://coveralls.io/repos/OpenBuildings/swiftmailer-google-campaign/badge.png?branch=master)](https://coveralls.io/r/OpenBuildings/swiftmailer-google-campaign?branch=master)
[![Latest Stable Version](https://poser.pugx.org/openbuildings/swiftmailer-google-campaign/v/stable.png)](https://packagist.org/packages/openbuildings/swiftmailer-google-campaign)

A swiftmailer plugin that appends automatically to all email links google campaign parameters from a config file or a custom one to the links you want

## Usage

```php
$mailer = Swift_Mailer::newInstance();

$mailer->registerPLugin(new GoogleCampaignPlugin(array(
   'utm_source' => 'source', 
   'utm_campaign' => 'email', 
   'utm_medium' => 'email'
));
```

Before sending email the plugin will append to all links the campaign parameters:

```html
<html>
	<body>
	 <a href="http://example.com">Example.com</a>
	</body>
</html>
```

Will be converted to 

```html
<html>
	<body>
	 <a href="http://example.com?utm_source=source&amp;utm_campaign=email&amp;utm_medium=email">Example.com</a>
	</body>
</html>
```

The plugin supports also embeding additional campaigns to your email:

```php
$mailer = Swift_Mailer::newInstance();

$mailer->registerPLugin(new GoogleCampaignPlugin(array(
   'utm_source' => 'source', 
   'utm_campaign' => 'email', 
   'utm_medium' => 'email'
  ), array(
    'your_campaign' => array(
       'utm_source' => 'my_custom_source', 
       'utm_campaign' => 'my_custom_campaign'
    )
  )
);
```

To embed a custom campaign to your email simply add the `google_campaign` query parameter to your link with value - the name of your campaign:

```html
<html>
	<body>
	 <a href="http://example.com?google_campaign=your_campaign">Example.com</a>
	</body>
</html>
```

Will output:

```html
<html>
	<body>
	 <a href="http://example.com?utm_source=my_custom_source&amp;utm_campaign=my_custom_campaign">Example.com</a>
	</body>
</html>
```

## License

Copyright (c) 2013, OpenBuildings Ltd. Developed by Yasen Yanev as part of [clippings.com](http://clippings.com)

Under BSD-3-Clause license, read LICENSE file.
