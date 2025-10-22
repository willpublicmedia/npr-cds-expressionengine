# NPR Content Distribution System

An ExpressionEngine addon for publishing to and from NPR's Content Distribution System (CDS).

Ported from Open Public Media's [NPR CDS wordpress](https://github.com/openpublicmedia/npr-cds-wordpress) plugin.

## Description

The [NPR Content Distribution System (CDS)](https://npr.github.io/content-distribution-service/) Plugin provides push and pull functionality with the NPR CDS along with a user-friendly administrative interface.

NPR's CDS is a content API, which essentially provides a structured way for other computer applications to get NPR stories in a predictable, flexible and powerful way. The content that is available includes audio from most NPR programs dating back to 1995 as well as text, images and other web-only content from NPR and NPR member stations. This archive consists of over 250,000 stories that are grouped into more than 5,000 different aggregations.

This plugin also allows you to push your content to the NPR CDS, so that it can be republished by NPR or other NPR member stations.

Access to the NPR CDS requires a bearer token, provided by NPR. If you are an NPR member station or are working with an NPR member station and do not know your key, please [ask NPR station relations for help](https://studio.npr.org).

Usage of this plugin is governed by [NPR's Terms of Use](https://www.npr.org/about-npr/179876898/terms-of-use), and more specifically their [API Usage terms](https://www.npr.org/about-npr/179876898/terms-of-use#APIContent).

The WordPress plugin was originally developed as an Open Source plugin by NPR and is now supported by developers with NPR member stations working within the Open Public Media group. If you would like to suggest features or bug fixes, or better yet if you would like to contribute new features or bug fixes please visit our [GitHub repository](https://github.com/OpenPublicMedia/npr-cds-wordpress) and post an issue or contribute a pull request.

## Installation & Configuration

### Dependencies

- Composer: <https://getcomposer.org/>

###

1. Install Composer.
2. Copy addon files to `{system_dir}/user/addons/npr_cds/`.
3. Install required composer packages (`php composer.phar install`).
4. Activate plugin from control panel Addons screen.
5. From the CDS settings screen, configure the following:
    - CDS token as provided by NPR
    - document prefix as provided by NPR
    - org/service ID as provided by NPR
    - and your push and pull urls.
6. Select "Theme uses featured image" if your page templates pull a hero image from the story's image field.
7. Select a suitable file storage location for pulled images.
8. Select channels that may be used by the CDS addon. See channel mapping rules below.

### Mapped Channels

Only mapped channels will be processed for CDS content.

In order to be mapped, a channel must meet the following requirements:

- Mapped channels must have access to all fields required by the story api (NPR CDS field group).
- Mapped channel `channel_name` must use the prefix "npr_stories" (e.g., "npr_stories_local_programming"). The `channel_prefix` field does not need to follow any naming conventions.
- Mapped channels must be configured as mapped in the addon settings.

### Migrating from Story API

If the [Story API addon](https://github.org/willpublicmedia/npr-api-expressionengine) is already installed, this addon will attempt to migrate existing settings and make a clean switch from the Story API to CDS.

0. Upgrade to the latest version of the story api addon to prevent data loss.
1. Follow installation instructions 1 and 2, above.
    - The installer will attempt to convert your Org ID to a Service ID if possible.
    - Channels mapped for the story api will be used in CDS.
    - CDS will use the same file storage location as CDS.
    - The story api extensions will be deactivated.
2. Follow CDS configuration instructions as above.
3. Remove the story api addon when convenient.

## Usage

WIP

## Frequently Asked Questions

**Can anyone get an NPR CDS Token?**

If you are an NPR member station or are working with an NPR member station and do not know your key, please [ask NPR station relations for help](https://studio.npr.org).

**Can anyone push content into the NPR CDS using this plugin?**

Push requires an Organization ID in the NPR CDS, which is typically given out to only NPR stations and approved content providers. If that's you, you probably already have an Organization ID.

**Where can I find NPR's documentation on the NPR CDS?**

There is documentation in the NPR's [Github site](https://npr.github.io/content-distribution-service/).

## Resources

- [NPR CDS documentation](https://npr.github.io/content-distribution-service/)
- [Open Public Media plugin](https://github.com/OpenPublicMedia/npr-cds-wordpress)
