ALTER TABLE `calendar` ADD `color_background`  VARCHAR (10) NOT NULL DEFAULT '' AFTER `label`,
ADD `color_text`  VARCHAR (10) NOT NULL DEFAULT '' AFTER `color_background`,
ADD `type`  VARCHAR (20) NOT NULL DEFAULT '' AFTER `color_text`;
