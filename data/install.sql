--
-- Base Table
--
CREATE TABLE `calendar` (
  `Calendar_ID` int(11) NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL,
  `modified_by` int(11) NOT NULL,
  `modified_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `calendar`
  ADD PRIMARY KEY (`Calendar_ID`);

ALTER TABLE `calendar`
  MODIFY `Calendar_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Permissions
--
INSERT INTO `permission` (`permission_key`, `module`, `label`, `nav_label`, `nav_href`, `show_in_menu`) VALUES
('add', 'OnePlace\\Calendar\\Controller\\CalendarController', 'Add', '', '', 0),
('edit', 'OnePlace\\Calendar\\Controller\\CalendarController', 'Edit', '', '', 0),
('index', 'OnePlace\\Calendar\\Controller\\CalendarController', 'Index', 'Calendars', '/calendar', 1),
('list', 'OnePlace\\Calendar\\Controller\\ApiController', 'List', '', '', 1),
('view', 'OnePlace\\Calendar\\Controller\\CalendarController', 'View', '', '', 0);

--
-- Form
--
INSERT INTO `core_form` (`form_key`, `label`) VALUES ('calendar-single', 'Calendar');

--
-- Index List
--
INSERT INTO `core_index_table` (`table_name`, `form`, `label`) VALUES
('calendar-index', 'calendar-single', 'Calendar Index');

--
-- Tabs
--
INSERT INTO `core_form_tab` (`Tab_ID`, `form`, `title`, `subtitle`, `icon`, `counter`, `sort_id`, `filter_check`, `filter_value`) VALUES ('calendar-base', 'calendar-single', 'Calendar', 'Base', 'fas fa-cogs', '', '0', '', '');

--
-- Buttons
--
INSERT INTO `core_form_button` (`Button_ID`, `label`, `icon`, `title`, `href`, `class`, `append`, `form`, `mode`, `filter_check`, `filter_value`) VALUES
(NULL, 'Save Calendar', 'fas fa-save', 'Save Calendar', '#', 'primary saveForm', '', 'calendar-single', 'link', '', ''),
(NULL, 'Edit Calendar', 'fas fa-edit', 'Edit Calendar', '/calendar/edit/##ID##', 'primary', '', 'calendar-view', 'link', '', ''),
(NULL, 'Add Calendar', 'fas fa-plus', 'Add Calendar', '/calendar/add', 'primary', '', 'calendar-index', 'link', '', '');

--
-- Fields
--
INSERT INTO `core_form_field` (`Field_ID`, `type`, `label`, `fieldkey`, `tab`, `form`, `class`, `url_view`, `url_ist`, `show_widget_left`, `allow_clear`, `readonly`, `tbl_cached_name`, `tbl_class`, `tbl_permission`) VALUES
(NULL, 'text', 'Name', 'label', 'calendar-base', 'calendar-single', 'col-md-3', '/calendar/view/##ID##', '', 0, 1, 0, '', '', '');

COMMIT;