# Hryvinskyi_SeoRobotsAdminUi

Admin UI module for managing SEO robots meta tags in Magento 2.

> **Part of [hryvinskyi/magento2-seo-robots-pack](https://github.com/hryvinskyi/magento2-seo-robots-pack)** - Complete SEO Robots solution for Magento 2

## Description

This module provides the admin panel interface for configuring robots meta tags in Magento 2. It adds configuration fields to the Magento admin area and provides backend models for data processing.

## Features

- Admin configuration interface
- URL pattern-based robots configuration with priority
- HTTPS-specific robots settings
- 404 page robots configuration (NOINDEX, NOFOLLOW)
- Paginated content robots settings
- X-Robots-Tag HTTP header enable/disable option
- Custom backend models for configuration data processing

## Configuration

Navigate to **Stores > Configuration > Hryvinskyi SEO > Robots** to access the configuration.

### Available Settings

1. **Enabled** - Enable/disable the robots meta tags functionality

2. **Robots Meta Header** - Configure URL pattern-based robots directives:
   - Add multiple patterns with different robots settings
   - Set priority for each pattern (higher priority overrides lower)
   - Support for wildcards in URL patterns

3. **Robots Meta Header for HTTPS** - Specific robots settings for HTTPS pages

4. **Set NOINDEX,NOFOLLOW for 404 page** - Automatically apply NOINDEX,NOFOLLOW to 404 error pages

5. **Add robots to '?p=' to paginated content** - Enable custom robots for paginated content

6. **Robots Meta Header for paginated content** - Choose which robots directive to apply to paginated pages

7. **Enable X-Robots-Tag Header** - Mirror robots meta tag directives in HTTP headers
   - When enabled, adds X-Robots-Tag HTTP header matching the meta tag
   - When disabled, only the meta tag is used

## Available Robots Directives

- INDEX, FOLLOW
- NOINDEX, FOLLOW
- INDEX, NOFOLLOW
- NOINDEX, NOFOLLOW
- INDEX, FOLLOW, NOARCHIVE
- NOINDEX, FOLLOW, NOARCHIVE
- INDEX, NOFOLLOW, NOARCHIVE
- NOINDEX, NOFOLLOW, NOARCHIVE

## Components

- **System Configuration** (`etc/adminhtml/system.xml`) - Admin panel fields
- **Backend Models** - Data serialization and processing
- **Source Models** - Dropdown option providers
- **Frontend Models** - Custom field renderers for complex configuration

## Dependencies

- Magento 2.4+
- hryvinskyi/magento2-seo-admin-ui
- hryvinskyi/magento2-seo-robots

## Installation

This module is typically installed as part of the `hryvinskyi/magento2-seo-robots-pack` metapackage:

```bash
composer require hryvinskyi/magento2-seo-robots-pack
php bin/magento module:enable Hryvinskyi_SeoRobotsAdminUi
php bin/magento setup:upgrade
php bin/magento cache:flush
```

## Author

**Volodymyr Hryvinskyi**
- Email: volodymyr@hryvinskyi.com

## License

MIT
