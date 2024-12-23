# Changelog

## 1.0.1

- BREAKING: correct collection document prefix bug introduced in 1.0.0

## 1.0.0

- BREAKING: rework document_id generation for collections

## 0.6.1

- fix var structure typo

## 0.6.0

- log most recent document push status
- add push status table and logging config

## 0.5.5

- load grid model before using

## 0.5.4

- check date type before formatting

## 0.5.3

- push collections before pushing story
- check whether file extension in image name parts

## 0.5.2

- correct updater selection

## 0.5.1

- correct tag, topic formatting

## 0.5.0

- add height to image field and corresponding db tables/rows
- include image height when pushing or pulling stories
- autofill image height on save
- add square and portrait image type definitions to autofill

## 0.4.0

- push channel title as a topic
- include tags in pushed stories
- include topics and tags on pulled stories
- correct keyword assignment on pull
- correct authorized service check on pull

## 0.3.10

- check for legacy filepath in modern settings

## 0.3.9

- update autofill handling for file manager compatibility

## 0.3.8

- add audio to document layout
- expand checks for restricted media

## 0.3.7

- correct image url generation for NPR's CDN (see also openpublicmedia/npr-cds-wordpress v1.2.5)

## 0.3.6

- check for urlencoded filenames

## 0.3.5

- correct audio duration format on push
- merge status messages on push

## 0.3.4

- correct syntax when calling EE config

## 0.3.3

- add embed code generation for player-video and stream-player-video profiles
- add checks on pulled content

## 0.3.2

- avoid column duplication error due to incorrect module
- use alt text on pushed or pulled stories
- correct video profile processing on push
- correct various profile validation issues on push
- add guard clauses on pulled stories

## 0.3.1.1

- correct module version

## 0.3.1

- correct version check on update
- add missing alt text column to image table

## 0.3.0

- add image alt text field

## 0.2.0

- allow template to use first video as hero image
- polyfill [HLS](https://github.com/video-dev/hls.js/)

## 0.1.0

- hide incompatible publish options
- remove unused dependencies

## 0.0.0

Prototype release.

- Port openpublicmedia/npr-cds-wordpress to ExpressionEngine.
- Expect rough feature parity with [EE story api plugin](willpublicmedia/npr-api-expressionengine) and OpenPublicMedia wordpress plugin.
